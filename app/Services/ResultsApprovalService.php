<?php

namespace App\Services;

use App\Models\CourseResultRecord;
use App\Models\CourseResultSheet;
use App\Models\ResultApproval;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultsApprovalService
{
    protected ResultCalculationService $calculationService;

    public function __construct(ResultCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Sheets visible to HOD for their department.
     */
    public function sheetsForHodDepartment(int $departmentId, ?string $status = null): Collection
    {
        $query = CourseResultSheet::with(['course.program', 'lecturer', 'records'])
            ->whereHas('course.program', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at');

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['submitted', 'hod_approved', 'approved', 'published', 'returned']);
        }

        return $query->get();
    }

    /**
     * Sheets for Registrar (institution-wide).
     */
    public function sheetsForRegistrar(?string $status = null): Collection
    {
        $query = CourseResultSheet::with(['course.program.department', 'lecturer', 'records'])
            ->orderByDesc('hod_approved_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('updated_at');

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['hod_approved', 'approved', 'published', 'returned', 'submitted']);
        }

        return $query->get();
    }

    public function assertHodCanAccess(CourseResultSheet $sheet, int $departmentId): void
    {
        $sheet->loadMissing('course.program');
        if (!$sheet->course || !$sheet->course->program
            || (int) $sheet->course->program->department_id !== (int) $departmentId) {
            abort(403, 'This result sheet is not in your department.');
        }
    }

    public function hodApprove(CourseResultSheet $sheet, ?string $comment = null): CourseResultSheet
    {
        if ($sheet->status !== 'submitted') {
            throw ValidationException::withMessages([
                'status' => 'Only submitted sheets can be approved by HOD.',
            ]);
        }

        $sheet->update([
            'status' => 'hod_approved',
            'hod_approved_at' => now(),
            'return_comments' => null,
        ]);

        ResultApproval::create([
            'course_result_sheet_id' => $sheet->id,
            'stage' => 'hod',
            'action' => 'approved',
            'user_id' => Auth::id(),
            'comment' => $comment ?: 'HOD approved',
        ]);

        return $sheet->fresh();
    }

    public function hodReturn(CourseResultSheet $sheet, string $comment): CourseResultSheet
    {
        if (!in_array($sheet->status, ['submitted', 'hod_approved'], true)) {
            throw ValidationException::withMessages([
                'status' => 'This sheet cannot be returned in its current status.',
            ]);
        }

        $comment = trim($comment);
        if ($comment === '') {
            throw ValidationException::withMessages([
                'comment' => 'Return comments are required.',
            ]);
        }

        $sheet->update([
            'status' => 'returned',
            'return_comments' => $comment,
            'hod_approved_at' => null,
        ]);

        ResultApproval::create([
            'course_result_sheet_id' => $sheet->id,
            'stage' => 'hod',
            'action' => 'returned',
            'user_id' => Auth::id(),
            'comment' => $comment,
        ]);

        return $sheet->fresh();
    }

    public function registrarApprove(CourseResultSheet $sheet, ?string $comment = null): CourseResultSheet
    {
        if ($sheet->status !== 'hod_approved') {
            throw ValidationException::withMessages([
                'status' => 'Only HOD-approved sheets can receive final approval.',
            ]);
        }

        $sheet->update([
            'status' => 'approved',
            'approved_at' => now(),
            'return_comments' => null,
        ]);

        ResultApproval::create([
            'course_result_sheet_id' => $sheet->id,
            'stage' => 'registrar',
            'action' => 'approved',
            'user_id' => Auth::id(),
            'comment' => $comment ?: 'Registrar final approval',
        ]);

        return $sheet->fresh();
    }

    public function registrarReturn(CourseResultSheet $sheet, string $comment): CourseResultSheet
    {
        if (!in_array($sheet->status, ['hod_approved', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => 'This sheet cannot be returned in its current status.',
            ]);
        }

        $comment = trim($comment);
        if ($comment === '') {
            throw ValidationException::withMessages([
                'comment' => 'Return comments are required.',
            ]);
        }

        $sheet->update([
            'status' => 'returned',
            'return_comments' => $comment,
            'approved_at' => null,
            'hod_approved_at' => null,
        ]);

        ResultApproval::create([
            'course_result_sheet_id' => $sheet->id,
            'stage' => 'registrar',
            'action' => 'returned',
            'user_id' => Auth::id(),
            'comment' => $comment,
        ]);

        return $sheet->fresh();
    }

    public function publish(CourseResultSheet $sheet): CourseResultSheet
    {
        if (!in_array($sheet->status, ['approved', 'published'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only finally approved sheets can be published.',
            ]);
        }

        DB::transaction(function () use ($sheet) {
            $sheet->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            ResultApproval::create([
                'course_result_sheet_id' => $sheet->id,
                'stage' => 'publish',
                'action' => 'published',
                'user_id' => Auth::id(),
                'comment' => 'Published to SIP',
            ]);

            $this->syncStudentAcademicRecordsForSheet($sheet->fresh(['course', 'records.student']));
        });

        return $sheet->fresh();
    }

    /**
     * Publish all approved sheets for a semester/year.
     */
    public function publishSemester(string $academicYear, string $semester): int
    {
        $sheets = CourseResultSheet::where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->where('status', 'approved')
            ->get();

        $count = 0;
        foreach ($sheets as $sheet) {
            $this->publish($sheet);
            $count++;
        }

        return $count;
    }

    /**
     * Published semester slip data for a student.
     *
     * @return array{semesters: array, cumulative: array}
     */
    public function studentPublishedResults(Student $student): array
    {
        $records = CourseResultRecord::with(['sheet.course'])
            ->where('student_id', $student->id)
            ->whereHas('sheet', function ($q) {
                $q->where('status', 'published');
            })
            ->get();

        $grouped = $records->groupBy(function (CourseResultRecord $record) {
            return ($record->sheet->academic_year ?? '') . '||' . ($record->sheet->semester ?? '');
        });

        $semesters = [];
        $allRecords = collect();

        foreach ($grouped as $key => $semesterRecords) {
            [$year, $sem] = array_pad(explode('||', $key, 2), 2, '');
            $courses = [];
            $creditTotal = 0.0;
            $creditObtained = 0.0;
            $weightedMarks = 0.0;

            foreach ($semesterRecords as $record) {
                $credits = (float) ($record->credit_hours ?? 0);
                $final = $record->final_mark !== null ? (float) $record->final_mark : null;
                $creditTotal += $credits;
                if ($final !== null && $record->grade_point !== null && (float) $record->grade_point > 0) {
                    $creditObtained += $credits;
                }
                if ($final !== null) {
                    $weightedMarks += $final * $credits;
                }

                $courses[] = [
                    'course_code' => optional($record->sheet->course)->course_code,
                    'course_title' => optional($record->sheet->course)->course_title,
                    'credits' => $credits,
                    'class_mark' => $record->class_mark,
                    'exam_mark' => $record->exam_mark,
                    'final_mark' => $record->final_mark,
                    'grade' => $record->grade,
                    'grade_point' => $record->grade_point,
                ];
            }

            $gpa = $this->calculationService->gpaFromRecords($semesterRecords);
            $weightedAverage = $creditTotal > 0 ? round($weightedMarks / $creditTotal, 2) : null;

            $semesters[] = [
                'academic_year' => $year,
                'semester' => $sem,
                'courses' => $courses,
                'total_credit' => $creditTotal,
                'credit_obtained' => $creditObtained,
                'weighted_marks' => round($weightedMarks, 2),
                'weighted_average' => $weightedAverage,
                'gpa' => $gpa,
            ];

            $allRecords = $allRecords->merge($semesterRecords);
        }

        // Newest first
        usort($semesters, function ($a, $b) {
            $cmp = strcmp($b['academic_year'], $a['academic_year']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp($b['semester'], $a['semester']);
        });

        $cgpa = $this->calculationService->gpaFromRecords($allRecords);
        $classification = $cgpa !== null
            ? $this->calculationService->classificationForCgpa($cgpa)
            : null;

        $cumCredits = $allRecords->sum(function ($r) {
            return (float) ($r->credit_hours ?? 0);
        });
        $cumWeighted = $allRecords->sum(function ($r) {
            if ($r->final_mark === null) {
                return 0;
            }
            return (float) $r->final_mark * (float) ($r->credit_hours ?? 0);
        });

        return [
            'semesters' => $semesters,
            'cumulative' => [
                'total_credit' => round($cumCredits, 2),
                'weighted_marks' => round($cumWeighted, 2),
                'weighted_average' => $cumCredits > 0 ? round($cumWeighted / $cumCredits, 2) : null,
                'cgpa' => $cgpa,
                'classification' => $classification,
            ],
        ];
    }

    /**
     * Keep legacy StudentAcademicRecord in sync for published semester.
     */
    protected function syncStudentAcademicRecordsForSheet(CourseResultSheet $sheet): void
    {
        $studentIds = $sheet->records->pluck('student_id')->unique();

        foreach ($studentIds as $studentId) {
            $publishedRecords = CourseResultRecord::with('sheet.course')
                ->where('student_id', $studentId)
                ->whereHas('sheet', function ($q) use ($sheet) {
                    $q->where('status', 'published')
                        ->where('academic_year', $sheet->academic_year)
                        ->where('semester', $sheet->semester);
                })
                ->get();

            if ($publishedRecords->isEmpty()) {
                continue;
            }

            $registered = [];
            $results = [];
            foreach ($publishedRecords as $record) {
                $code = optional($record->sheet->course)->course_code;
                $title = optional($record->sheet->course)->course_title;
                $credits = (float) ($record->credit_hours ?? 0);
                $registered[] = [
                    'code' => $code,
                    'name' => $title,
                    'credits' => $credits,
                ];
                $results[] = [
                    'course' => trim($code . ' ' . $title),
                    'grade' => $record->grade,
                    'credits' => $credits,
                    'class_mark' => $record->class_mark,
                    'exam_mark' => $record->exam_mark,
                    'final_mark' => $record->final_mark,
                    'grade_point' => $record->grade_point,
                ];
            }

            StudentAcademicRecord::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'academic_year' => $sheet->academic_year,
                    'semester' => $sheet->semester,
                ],
                [
                    'registered_courses' => $registered,
                    'results' => $results,
                    'gpa' => $this->calculationService->gpaFromRecords($publishedRecords),
                    'is_approved' => true,
                    'approved_at' => now(),
                ]
            );
        }
    }
}
