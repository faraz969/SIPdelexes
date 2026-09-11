<?php

namespace App\Services;

use App\Models\AssessmentScore;
use App\Models\Course;
use App\Models\CourseAssessmentComponent;
use App\Models\CourseResultSheet;
use App\Models\Lecturer;
use App\Models\ResultApproval;
use App\Models\ResultImportBatch;
use App\Models\ResultImportError;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LecturerResultsService
{
    protected CourseEnrollmentService $enrollmentService;
    protected CourseAssessmentService $assessmentService;
    protected ResultCalculationService $calculationService;

    public function __construct(
        CourseEnrollmentService $enrollmentService,
        CourseAssessmentService $assessmentService,
        ResultCalculationService $calculationService
    ) {
        $this->enrollmentService = $enrollmentService;
        $this->assessmentService = $assessmentService;
        $this->calculationService = $calculationService;
    }

    public function assertOwnsAssignment(Lecturer $assignment, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if ((int) $assignment->user_id !== (int) $userId) {
            abort(403, 'Unauthorized lecturer assignment.');
        }
    }

    public function getOrCreateSheet(Lecturer $assignment, string $academicYear, string $semester): CourseResultSheet
    {
        $this->assertOwnsAssignment($assignment);
        $assignment->loadMissing('course.assessmentComponents');

        if ($assignment->course->assessmentComponents->where('is_active', true)->isEmpty()) {
            throw ValidationException::withMessages([
                'components' => 'This course has no assessment components. Ask Admin/HOD to configure them on the course first.',
            ]);
        }

        $sheet = CourseResultSheet::firstOrCreate(
            [
                'course_id' => $assignment->course_id,
                'academic_year' => $academicYear,
                'semester' => $semester,
                'version' => 1,
            ],
            [
                'lecturer_user_id' => $assignment->user_id,
                'status' => 'draft',
            ]
        );

        if (!$sheet->lecturer_user_id) {
            $sheet->update(['lecturer_user_id' => $assignment->user_id]);
        }

        return $sheet->fresh(['course.assessmentComponents', 'scores', 'records']);
    }

    /**
     * Registered students for this lecturer's course/semester, optionally filtered by session.
     */
    public function getRoster(Lecturer $assignment, string $academicYear, string $semester): Collection
    {
        $this->assertOwnsAssignment($assignment);
        $assignment->loadMissing('course');

        $students = $this->enrollmentService->getRegisteredStudents(
            $assignment->course,
            $semester,
            $academicYear
        );

        if ($assignment->session_id) {
            $students = $students->filter(function (Student $student) use ($assignment) {
                return (int) $student->preferred_session_id === (int) $assignment->session_id;
            })->values();
        }

        return $students;
    }

    /**
     * Progress stats for a sheet.
     *
     * @return array{students:int,components:int,expected:int,entered:int,missing:int,percent:float,incomplete_students:int}
     */
    public function progress(CourseResultSheet $sheet, Collection $roster): array
    {
        $sheet->loadMissing(['course.assessmentComponents', 'scores']);
        $components = $sheet->course->assessmentComponents->where('is_active', true);
        $studentIds = $roster->pluck('id');
        $expected = $studentIds->count() * $components->count();

        $entered = $sheet->scores
            ->whereIn('student_id', $studentIds->all())
            ->whereIn('course_assessment_component_id', $components->pluck('id')->all())
            ->filter(function ($score) {
                return $score->raw_mark !== null && $score->raw_mark !== '';
            })
            ->count();

        $incompleteStudents = 0;
        foreach ($studentIds as $studentId) {
            foreach ($components as $component) {
                $score = $sheet->scores->first(function ($s) use ($studentId, $component) {
                    return (int) $s->student_id === (int) $studentId
                        && (int) $s->course_assessment_component_id === (int) $component->id
                        && $s->raw_mark !== null
                        && $s->raw_mark !== '';
                });
                if (!$score) {
                    $incompleteStudents++;
                    break;
                }
            }
        }

        return [
            'students' => $studentIds->count(),
            'components' => $components->count(),
            'expected' => $expected,
            'entered' => $entered,
            'missing' => max(0, $expected - $entered),
            'percent' => $expected > 0 ? round(($entered / $expected) * 100, 1) : 0.0,
            'incomplete_students' => $incompleteStudents,
        ];
    }

    /**
     * Save marks for one component. $marks = [student_id => raw_mark|null]
     */
    public function saveComponentMarks(
        CourseResultSheet $sheet,
        CourseAssessmentComponent $component,
        array $marks,
        string $source = 'manual'
    ): int {
        if (!$sheet->isEditableByLecturer()) {
            throw ValidationException::withMessages([
                'status' => 'This result sheet is locked (status: ' . $sheet->status . ').',
            ]);
        }

        if ((int) $component->course_id !== (int) $sheet->course_id || !$component->is_active) {
            throw ValidationException::withMessages([
                'component' => 'Invalid assessment component for this course.',
            ]);
        }

        $saved = 0;

        DB::transaction(function () use ($sheet, $component, $marks, $source, &$saved) {
            foreach ($marks as $studentId => $raw) {
                $studentId = (int) $studentId;
                if ($studentId <= 0) {
                    continue;
                }

                if ($raw === null || $raw === '') {
                    AssessmentScore::where('course_result_sheet_id', $sheet->id)
                        ->where('course_assessment_component_id', $component->id)
                        ->where('student_id', $studentId)
                        ->delete();
                    continue;
                }

                if (!is_numeric($raw)) {
                    throw ValidationException::withMessages([
                        "marks.{$studentId}" => 'Mark must be numeric.',
                    ]);
                }

                $raw = round((float) $raw, 2);
                if ($raw < 0 || $raw > (float) $component->max_mark) {
                    throw ValidationException::withMessages([
                        "marks.{$studentId}" => "Mark must be between 0 and {$component->max_mark}.",
                    ]);
                }

                $contribution = $this->assessmentService->contributionFromRaw(
                    $raw,
                    (float) $component->max_mark,
                    (float) $component->contribution
                );

                AssessmentScore::updateOrCreate(
                    [
                        'course_result_sheet_id' => $sheet->id,
                        'course_assessment_component_id' => $component->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'raw_mark' => $raw,
                        'contribution_mark' => $contribution,
                        'source' => $source,
                        'entered_by' => Auth::id(),
                        'special_status' => null,
                    ]
                );
                $saved++;
            }

            $this->calculationService->recalculateSheet($sheet->fresh(['course.assessmentComponents', 'scores']));
        });

        return $saved;
    }

    public function generateCsvTemplate(
        CourseResultSheet $sheet,
        CourseAssessmentComponent $component,
        Collection $roster
    ): string {
        $sheet->loadMissing('course');
        $lines = [];
        $lines[] = [
            'student_id',
            'index_no',
            'student_name',
            'academic_year',
            'semester',
            'course_code',
            'assessment_type',
            'assessment_code',
            'mark',
            'max_mark',
            'remarks',
        ];

        foreach ($roster as $student) {
            $lines[] = [
                $student->student_id,
                $student->student_id,
                optional($student->user)->name ?? '',
                $sheet->academic_year,
                $sheet->semester,
                $sheet->course->course_code,
                strtoupper($component->category),
                $component->code,
                '',
                (string) $component->max_mark,
                '',
            ];
        }

        $fh = fopen('php://temp', 'r+');
        foreach ($lines as $line) {
            fputcsv($fh, $line);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }

    /**
     * Validate CSV without saving. Returns preview payload.
     *
     * @return array{valid: array, invalid: array, duplicates: array, summary: array}
     */
    public function validateCsv(
        CourseResultSheet $sheet,
        CourseAssessmentComponent $component,
        Collection $roster,
        string $csvContents
    ): array {
        $rows = $this->parseCsv($csvContents);
        $rosterByStudentId = $roster->keyBy(function (Student $s) {
            return strtoupper(trim((string) $s->student_id));
        });

        $existingScores = AssessmentScore::where('course_result_sheet_id', $sheet->id)
            ->where('course_assessment_component_id', $component->id)
            ->whereNotNull('raw_mark')
            ->pluck('student_id')
            ->all();
        $existingSet = array_fill_keys($existingScores, true);

        $valid = [];
        $invalid = [];
        $duplicates = [];
        $seenInFile = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // header is row 1
            $studentIdValue = strtoupper(trim((string) ($row['student_id'] ?? '')));
            $indexNo = strtoupper(trim((string) ($row['index_no'] ?? '')));
            $markRaw = trim((string) ($row['mark'] ?? ''));

            $errors = [];

            if ($studentIdValue === '') {
                $errors[] = 'student_id is required';
            }

            $student = $rosterByStudentId->get($studentIdValue);
            if ($studentIdValue !== '' && !$student) {
                $errors[] = 'Student not registered for this course/session';
            }

            if ($indexNo !== '' && $studentIdValue !== '' && $indexNo !== $studentIdValue) {
                $errors[] = 'index_no must match student_id';
            }

            $ay = trim((string) ($row['academic_year'] ?? ''));
            $sem = trim((string) ($row['semester'] ?? ''));
            $courseCode = trim((string) ($row['course_code'] ?? ''));
            $assessmentCode = strtoupper(trim((string) ($row['assessment_code'] ?? '')));
            $maxMark = trim((string) ($row['max_mark'] ?? ''));

            if ($ay !== '' && $ay !== $sheet->academic_year) {
                $errors[] = 'academic_year mismatch';
            }
            if ($sem !== '' && strcasecmp($sem, $sheet->semester) !== 0) {
                $errors[] = 'semester mismatch';
            }
            if ($courseCode !== '' && strcasecmp($courseCode, $sheet->course->course_code) !== 0) {
                $errors[] = 'course_code mismatch';
            }
            if ($assessmentCode !== '' && $assessmentCode !== strtoupper($component->code)) {
                $errors[] = 'assessment_code mismatch';
            }
            if ($maxMark !== '' && is_numeric($maxMark) && abs((float) $maxMark - (float) $component->max_mark) > 0.01) {
                $errors[] = 'max_mark mismatch';
            }

            if ($markRaw === '') {
                $errors[] = 'mark is blank (blank marks are not imported as zero)';
            } elseif (!is_numeric($markRaw)) {
                $errors[] = 'mark must be numeric';
            } else {
                $mark = (float) $markRaw;
                if ($mark < 0) {
                    $errors[] = 'mark below zero';
                }
                if ($mark > (float) $component->max_mark) {
                    $errors[] = 'mark above maximum';
                }
            }

            if ($student && isset($seenInFile[$student->id])) {
                $duplicates[] = [
                    'row' => $rowNum,
                    'student_id' => $studentIdValue,
                    'message' => 'Duplicate student in CSV',
                ];
                $errors[] = 'Duplicate student in CSV';
            }

            if ($student && isset($existingSet[$student->id])) {
                $errors[] = 'Score already exists — use correction overwrite explicitly';
            }

            if (!empty($errors)) {
                $invalid[] = [
                    'row' => $rowNum,
                    'student_id' => $studentIdValue,
                    'mark' => $markRaw,
                    'errors' => $errors,
                ];
                continue;
            }

            $seenInFile[$student->id] = true;
            $valid[] = [
                'row' => $rowNum,
                'student_id' => $student->id,
                'student_number' => $student->student_id,
                'student_name' => optional($student->user)->name,
                'mark' => round((float) $markRaw, 2),
                'remarks' => trim((string) ($row['remarks'] ?? '')),
            ];
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'duplicates' => $duplicates,
            'summary' => [
                'total' => count($rows),
                'valid' => count($valid),
                'invalid' => count($invalid),
                'duplicates' => count($duplicates),
            ],
        ];
    }

    /**
     * Commit previously validated rows (or re-validate with allowOverwrite).
     */
    public function importCsv(
        CourseResultSheet $sheet,
        CourseAssessmentComponent $component,
        Collection $roster,
        string $csvContents,
        string $filename,
        bool $allowOverwrite = false
    ): ResultImportBatch {
        if (!$sheet->isEditableByLecturer()) {
            throw ValidationException::withMessages([
                'status' => 'This result sheet is locked.',
            ]);
        }

        $preview = $this->validateCsv($sheet, $component, $roster, $csvContents);

        // If overwrite allowed, re-filter invalid that ONLY failed for existing score
        $toImport = $preview['valid'];
        if ($allowOverwrite) {
            $extra = $this->extractOverwriteCandidates($sheet, $component, $roster, $csvContents);
            $toImport = array_merge($toImport, $extra);
        }

        if (empty($toImport) && !empty($preview['invalid'])) {
            throw ValidationException::withMessages([
                'csv' => 'No valid rows to import. Fix errors and try again.',
            ]);
        }

        return DB::transaction(function () use ($sheet, $component, $csvContents, $filename, $preview, $toImport, $allowOverwrite) {
            $batch = ResultImportBatch::create([
                'course_result_sheet_id' => $sheet->id,
                'course_assessment_component_id' => $component->id,
                'filename' => $filename,
                'uploaded_by' => Auth::id(),
                'total_rows' => $preview['summary']['total'],
                'valid_rows' => count($toImport),
                'invalid_rows' => $preview['summary']['invalid'],
                'imported_rows' => 0,
                'status' => 'preview',
            ]);

            foreach ($preview['invalid'] as $err) {
                // Skip pure overwrite blockers when allowOverwrite — those become imports
                if ($allowOverwrite && count($err['errors']) === 1 && strpos($err['errors'][0], 'already exists') !== false) {
                    continue;
                }
                ResultImportError::create([
                    'result_import_batch_id' => $batch->id,
                    'row_number' => $err['row'],
                    'student_id_value' => $err['student_id'],
                    'error_code' => 'VALIDATION',
                    'message' => implode('; ', $err['errors']),
                ]);
            }

            $marks = [];
            foreach ($toImport as $row) {
                $marks[$row['student_id']] = $row['mark'];
            }

            $imported = $this->saveComponentMarks($sheet, $component, $marks, 'csv');

            $batch->update([
                'imported_rows' => $imported,
                'status' => 'committed',
            ]);

            return $batch->fresh('errors');
        });
    }

    public function submitSheet(CourseResultSheet $sheet): CourseResultSheet
    {
        if (!$sheet->isEditableByLecturer()) {
            throw ValidationException::withMessages([
                'status' => 'Only draft or returned sheets can be submitted.',
            ]);
        }

        $sheet->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'return_comments' => null,
        ]);

        ResultApproval::create([
            'course_result_sheet_id' => $sheet->id,
            'stage' => 'lecturer',
            'action' => 'submitted',
            'user_id' => Auth::id(),
            'comment' => 'Submitted for HOD approval',
        ]);

        return $sheet->fresh();
    }

    /**
     * Build result grid rows for the sheet view.
     */
    public function buildResultGrid(CourseResultSheet $sheet, Collection $roster): array
    {
        $sheet->loadMissing(['course.assessmentComponents', 'scores', 'records']);
        $components = $sheet->course->assessmentComponents->where('is_active', true)->values();
        $scores = $sheet->scores->groupBy('student_id');
        $records = $sheet->records->where('version', $sheet->version)->keyBy('student_id');

        $rows = [];
        foreach ($roster as $student) {
            $studentScores = $scores->get($student->id, collect());
            $byComponent = [];
            foreach ($components as $component) {
                $score = $studentScores->firstWhere('course_assessment_component_id', $component->id);
                $byComponent[$component->id] = $score ? $score->raw_mark : null;
            }
            $record = $records->get($student->id);
            $rows[] = [
                'student' => $student,
                'scores' => $byComponent,
                'class_mark' => $record->class_mark ?? null,
                'exam_mark' => $record->exam_mark ?? null,
                'final_mark' => $record->final_mark ?? null,
                'grade' => $record->grade ?? null,
                'grade_point' => $record->grade_point ?? null,
                'special_status' => $record->special_status ?? null,
            ];
        }

        return [
            'components' => $components,
            'rows' => $rows,
        ];
    }

    protected function parseCsv(string $contents): array
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $contents);
        rewind($fh);

        $header = fgetcsv($fh);
        if ($header === false) {
            fclose($fh);
            throw ValidationException::withMessages(['csv' => 'CSV file is empty.']);
        }

        $header = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $header);

        $required = ['student_id', 'mark'];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                fclose($fh);
                throw ValidationException::withMessages([
                    'csv' => "CSV is missing required column: {$col}",
                ]);
            }
        }

        $rows = [];
        while (($data = fgetcsv($fh)) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? '';
            }
            $rows[] = $row;
        }
        fclose($fh);

        return $rows;
    }

    protected function extractOverwriteCandidates(
        CourseResultSheet $sheet,
        CourseAssessmentComponent $component,
        Collection $roster,
        string $csvContents
    ): array {
        // Re-run a softer validation that allows existing scores
        $rows = $this->parseCsv($csvContents);
        $rosterByStudentId = $roster->keyBy(fn (Student $s) => strtoupper(trim((string) $s->student_id)));
        $out = [];

        foreach ($rows as $row) {
            $studentIdValue = strtoupper(trim((string) ($row['student_id'] ?? '')));
            $markRaw = trim((string) ($row['mark'] ?? ''));
            $student = $rosterByStudentId->get($studentIdValue);
            if (!$student || $markRaw === '' || !is_numeric($markRaw)) {
                continue;
            }
            $mark = (float) $markRaw;
            if ($mark < 0 || $mark > (float) $component->max_mark) {
                continue;
            }
            $out[] = [
                'student_id' => $student->id,
                'mark' => round($mark, 2),
            ];
        }

        return $out;
    }
}
