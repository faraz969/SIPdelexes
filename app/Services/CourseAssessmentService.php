<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessmentComponent;
use App\Models\GradingScheme;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseAssessmentService
{
    public function defaultComponents(): array
    {
        return config('results.default_components', []);
    }

    public function classMarkTotal(): float
    {
        return (float) config('results.class_mark_total', 30);
    }

    public function examMarkTotal(): float
    {
        return (float) config('results.exam_mark_total', 70);
    }

    /**
     * Validate and sync assessment components for a course.
     * Expected input rows: code, name, category, max_mark, contribution, sequence?, is_active?
     *
     * @param  array<int, array>  $rows
     */
    public function syncForCourse(Course $course, array $rows, bool $allowEmpty = false): void
    {
        $normalized = $this->normalizeRows($rows);

        if (empty($normalized)) {
            if ($allowEmpty) {
                $course->assessmentComponents()->delete();
                return;
            }
            throw ValidationException::withMessages([
                'components' => 'Add at least one assessment component (Class components totaling '
                    . $this->classMarkTotal() . ' and Exam totaling ' . $this->examMarkTotal() . ').',
            ]);
        }

        $this->assertTotals($normalized);

        DB::transaction(function () use ($course, $normalized) {
            $keepIds = [];

            foreach ($normalized as $index => $row) {
                $component = $course->assessmentComponents()
                    ->where('code', $row['code'])
                    ->first();

                $payload = [
                    'name' => $row['name'],
                    'category' => $row['category'],
                    'max_mark' => $row['max_mark'],
                    'contribution' => $row['contribution'],
                    'sequence' => $row['sequence'] ?? ($index + 1),
                    'is_active' => $row['is_active'] ?? true,
                ];

                if ($component) {
                    $component->update($payload);
                } else {
                    $component = $course->assessmentComponents()->create(array_merge($payload, [
                        'code' => $row['code'],
                    ]));
                }

                $keepIds[] = $component->id;
            }

            $course->assessmentComponents()
                ->whereNotIn('id', $keepIds)
                ->delete();
        });
    }

    /**
     * @param  array<int, array>  $rows
     * @return array<int, array>
     */
    public function normalizeRows(array $rows): array
    {
        $out = [];
        $seenCodes = [];

        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            $name = trim((string) ($row['name'] ?? ''));
            $category = strtolower(trim((string) ($row['category'] ?? '')));
            $maxMark = $row['max_mark'] ?? null;
            $contribution = $row['contribution'] ?? null;

            // Skip fully blank rows
            if ($code === '' && $name === '' && ($maxMark === null || $maxMark === '') && ($contribution === null || $contribution === '')) {
                continue;
            }

            if ($code === '' || $name === '') {
                throw ValidationException::withMessages([
                    "components.{$index}" => 'Each component needs a code and name.',
                ]);
            }

            if (!in_array($category, CourseAssessmentComponent::CATEGORIES, true)) {
                throw ValidationException::withMessages([
                    "components.{$index}" => 'Category must be class or exam.',
                ]);
            }

            if (!is_numeric($maxMark) || (float) $maxMark <= 0) {
                throw ValidationException::withMessages([
                    "components.{$index}" => 'Max mark must be greater than 0.',
                ]);
            }

            if (!is_numeric($contribution) || (float) $contribution <= 0) {
                throw ValidationException::withMessages([
                    "components.{$index}" => 'Contribution must be greater than 0.',
                ]);
            }

            if (isset($seenCodes[$code])) {
                throw ValidationException::withMessages([
                    "components.{$index}" => "Duplicate component code: {$code}.",
                ]);
            }
            $seenCodes[$code] = true;

            $out[] = [
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'max_mark' => round((float) $maxMark, 2),
                'contribution' => round((float) $contribution, 2),
                'sequence' => isset($row['sequence']) ? (int) $row['sequence'] : ($index + 1),
                'is_active' => !isset($row['is_active']) || (bool) $row['is_active'],
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, array>  $rows
     */
    public function assertTotals(array $rows): void
    {
        $classTotal = 0.0;
        $examTotal = 0.0;

        foreach ($rows as $row) {
            if (($row['category'] ?? '') === 'class') {
                $classTotal += (float) $row['contribution'];
            } elseif (($row['category'] ?? '') === 'exam') {
                $examTotal += (float) $row['contribution'];
            }
        }

        $expectedClass = $this->classMarkTotal();
        $expectedExam = $this->examMarkTotal();

        if (abs($classTotal - $expectedClass) > 0.01) {
            throw ValidationException::withMessages([
                'components' => "Class component contributions must total {$expectedClass} (currently {$classTotal}).",
            ]);
        }

        if (abs($examTotal - $expectedExam) > 0.01) {
            throw ValidationException::withMessages([
                'components' => "Exam component contributions must total {$expectedExam} (currently {$examTotal}).",
            ]);
        }
    }

    /**
     * Contribution mark = (raw / max) * contribution
     */
    public function contributionFromRaw(float $rawMark, float $maxMark, float $contribution): float
    {
        if ($maxMark <= 0) {
            return 0.0;
        }

        return round(($rawMark / $maxMark) * $contribution, 2);
    }
}
