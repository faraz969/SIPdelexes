<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessmentComponent;
use App\Models\CourseResultRecord;
use App\Models\CourseResultSheet;
use App\Models\GradingScheme;
use App\Models\Student;
use Illuminate\Support\Collection;

class ResultCalculationService
{
    protected CourseAssessmentService $assessmentService;

    public function __construct(CourseAssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    /**
     * Resolve grade + grade point for a final mark using the default (or given) scheme.
     *
     * @return array{grade: string, grade_point: float}|null
     */
    public function gradeForMark(float $finalMark, ?GradingScheme $scheme = null): ?array
    {
        $scheme = $scheme ?: GradingScheme::defaultScheme();
        if (!$scheme) {
            return null;
        }

        $scheme->loadMissing('lines');

        foreach ($scheme->lines as $line) {
            if ($finalMark >= (float) $line->min_mark && $finalMark <= (float) $line->max_mark) {
                return [
                    'grade' => $line->grade,
                    'grade_point' => (float) $line->grade_point,
                ];
            }
        }

        return null;
    }

    public function classificationForCgpa(float $cgpa, ?GradingScheme $scheme = null): ?string
    {
        $scheme = $scheme ?: GradingScheme::defaultScheme();
        if (!$scheme) {
            return null;
        }

        $scheme->loadMissing('classifications');

        foreach ($scheme->classifications as $row) {
            if ($cgpa >= (float) $row->min_cgpa && $cgpa <= (float) $row->max_cgpa) {
                return $row->name;
            }
        }

        return null;
    }

    /**
     * Calculate Class/Exam/Final for one student from component scores.
     *
     * @param  Collection<int, CourseAssessmentComponent>  $components
     * @param  array<int, float|null>  $rawByComponentId  component_id => raw_mark
     * @return array{class_mark: float|null, exam_mark: float|null, final_mark: float|null, grade: ?string, grade_point: ?float, quality_points: ?float, incomplete: bool}
     */
    public function calculateStudentResult(Collection $components, array $rawByComponentId, float $creditHours): array
    {
        $classMark = 0.0;
        $examMark = 0.0;
        $classHasAny = false;
        $examHasAny = false;
        $incomplete = false;

        foreach ($components as $component) {
            if (!$component->is_active) {
                continue;
            }

            $raw = $rawByComponentId[$component->id] ?? null;
            if ($raw === null || $raw === '') {
                $incomplete = true;
                continue;
            }

            $contrib = $this->assessmentService->contributionFromRaw(
                (float) $raw,
                (float) $component->max_mark,
                (float) $component->contribution
            );

            if ($component->category === 'class') {
                $classMark += $contrib;
                $classHasAny = true;
            } else {
                $examMark += $contrib;
                $examHasAny = true;
            }
        }

        $classMark = $classHasAny ? round($classMark, 2) : null;
        $examMark = $examHasAny ? round($examMark, 2) : null;
        $finalMark = ($classMark !== null && $examMark !== null)
            ? round($classMark + $examMark, 2)
            : null;

        $grade = null;
        $gradePoint = null;
        $qualityPoints = null;

        if ($finalMark !== null) {
            $resolved = $this->gradeForMark($finalMark);
            if ($resolved) {
                $grade = $resolved['grade'];
                $gradePoint = $resolved['grade_point'];
                $qualityPoints = round($creditHours * $gradePoint, 2);
            }
        }

        return [
            'class_mark' => $classMark,
            'exam_mark' => $examMark,
            'final_mark' => $finalMark,
            'grade' => $grade,
            'grade_point' => $gradePoint,
            'quality_points' => $qualityPoints,
            'incomplete' => $incomplete,
        ];
    }

    /**
     * Recalculate and upsert CourseResultRecord rows for an entire sheet.
     */
    public function recalculateSheet(CourseResultSheet $sheet): void
    {
        $sheet->loadMissing(['course.assessmentComponents', 'scores']);
        $components = $sheet->course->assessmentComponents->where('is_active', true)->values();
        $creditHours = (float) ($sheet->course->total_credit_units ?? $sheet->course->credit_units ?? 0);

        $scoresByStudent = $sheet->scores->groupBy('student_id');

        foreach ($scoresByStudent as $studentId => $scores) {
            $rawByComponent = [];
            foreach ($scores as $score) {
                $rawByComponent[$score->course_assessment_component_id] = $score->raw_mark;
            }

            $calc = $this->calculateStudentResult($components, $rawByComponent, $creditHours);

            CourseResultRecord::updateOrCreate(
                [
                    'course_result_sheet_id' => $sheet->id,
                    'student_id' => $studentId,
                    'version' => $sheet->version,
                ],
                [
                    'class_mark' => $calc['class_mark'],
                    'exam_mark' => $calc['exam_mark'],
                    'final_mark' => $calc['final_mark'],
                    'grade' => $calc['grade'],
                    'grade_point' => $calc['grade_point'],
                    'credit_hours' => $creditHours,
                    'quality_points' => $calc['quality_points'],
                    'special_status' => $calc['incomplete'] ? 'INCOMPLETE' : null,
                ]
            );
        }
    }

    /**
     * GPA for a set of result records: Σ QP / Σ credits.
     *
     * @param  Collection<int, CourseResultRecord>  $records
     */
    public function gpaFromRecords(Collection $records): ?float
    {
        $credits = 0.0;
        $qp = 0.0;

        foreach ($records as $record) {
            if ($record->grade_point === null || $record->credit_hours === null) {
                continue;
            }
            $credits += (float) $record->credit_hours;
            $qp += (float) ($record->quality_points ?? ((float) $record->credit_hours * (float) $record->grade_point));
        }

        if ($credits <= 0) {
            return null;
        }

        return round($qp / $credits, 2);
    }
}
