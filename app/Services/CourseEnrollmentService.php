<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseRegistration;
use Illuminate\Support\Collection;

class CourseEnrollmentService
{
    /**
     * Build enrollment rows: one row per course (+ semester/academic year) with student counts.
     *
     * @param  array{department_id?: int|null, program_id?: int|null, semester?: string|null, academic_year?: string|null}  $filters
     */
    public function getEnrollmentRows(array $filters = []): Collection
    {
        $departmentId = $filters['department_id'] ?? null;
        $programId = $filters['program_id'] ?? null;
        $semester = trim((string) ($filters['semester'] ?? ''));
        $academicYear = trim((string) ($filters['academic_year'] ?? ''));

        $coursesQuery = Course::with(['program.department'])
            ->orderBy('program_id')
            ->orderBy('sort_order')
            ->orderBy('course_code');

        if (!empty($departmentId)) {
            $coursesQuery->whereHas('program', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            });
        }

        if (!empty($programId)) {
            $coursesQuery->where('program_id', $programId);
        }

        $courses = $coursesQuery->get();
        $courseIds = $courses->pluck('id')->all();

        $registrationsQuery = CourseRegistration::query()
            ->whereIn('status', ['registered', 'late', 'pending']);

        if ($semester !== '') {
            $registrationsQuery->where('semester', $semester);
        }

        if ($academicYear !== '') {
            $registrationsQuery->where('academic_year', $academicYear);
        }

        $registrations = $registrationsQuery->get(['id', 'student_id', 'semester', 'academic_year', 'courses', 'status']);

        /** @var array<string, array{count: int, student_ids: array<int, true>}> $counts */
        $counts = [];

        foreach ($registrations as $registration) {
            $regCourses = is_array($registration->courses) ? $registration->courses : [];
            foreach ($regCourses as $item) {
                $courseId = isset($item['id']) ? (int) $item['id'] : 0;
                if ($courseId <= 0 || !in_array($courseId, $courseIds, true)) {
                    continue;
                }

                $key = $this->rowKey($courseId, $registration->semester, $registration->academic_year);
                if (!isset($counts[$key])) {
                    $counts[$key] = ['count' => 0, 'student_ids' => []];
                }

                // Count unique students per course/semester/year
                if (!isset($counts[$key]['student_ids'][$registration->student_id])) {
                    $counts[$key]['student_ids'][$registration->student_id] = true;
                    $counts[$key]['count']++;
                }
            }
        }

        $rows = collect();

        if ($semester !== '' && $academicYear !== '') {
            foreach ($courses as $course) {
                $key = $this->rowKey($course->id, $semester, $academicYear);
                $rows->push($this->makeRow($course, $semester, $academicYear, $counts[$key]['count'] ?? 0));
            }

            return $rows;
        }

        // Without full filters: show every course/semester/year that has registrations,
        // plus courses with no registrations (using course's own semester/year if set).
        $seenCourseIds = [];

        foreach ($counts as $key => $data) {
            [$courseId, $rowSemester, $rowYear] = explode('|', $key, 3);
            $course = $courses->firstWhere('id', (int) $courseId);
            if (!$course) {
                continue;
            }
            $seenCourseIds[(int) $courseId] = true;
            $rows->push($this->makeRow($course, $rowSemester, $rowYear, $data['count']));
        }

        foreach ($courses as $course) {
            if (isset($seenCourseIds[$course->id])) {
                continue;
            }
            $rows->push($this->makeRow(
                $course,
                $course->semester ?: ($semester ?: '—'),
                $course->academic_year ?: ($academicYear ?: '—'),
                0
            ));
        }

        return $rows->sortBy([
            ['academic_year', 'desc'],
            ['semester', 'asc'],
            ['course_code', 'asc'],
        ])->values();
    }

    /**
     * Students registered for a course in a given semester/academic year.
     */
    public function getRegisteredStudents(Course $course, string $semester, string $academicYear): Collection
    {
        $registrations = CourseRegistration::with(['student.user', 'student.program'])
            ->whereIn('status', ['registered', 'late', 'pending'])
            ->where('semester', $semester)
            ->where('academic_year', $academicYear)
            ->get();

        $students = collect();

        foreach ($registrations as $registration) {
            $regCourses = is_array($registration->courses) ? $registration->courses : [];
            foreach ($regCourses as $item) {
                if ((int) ($item['id'] ?? 0) !== (int) $course->id) {
                    continue;
                }
                if ($registration->student && !$students->contains('id', $registration->student->id)) {
                    $students->push($registration->student);
                }
                break;
            }
        }

        return $students->sortBy(fn ($s) => $s->user->name ?? $s->student_id)->values();
    }

    public function filterOptions(?int $departmentId = null): array
    {
        $courseQuery = Course::query();
        $regQuery = CourseRegistration::query()->whereIn('status', ['registered', 'late', 'pending']);

        if ($departmentId) {
            $courseQuery->whereHas('program', function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId);
            });
        }

        $semestersFromCourses = (clone $courseQuery)
            ->whereNotNull('semester')
            ->where('semester', '!=', '')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester');

        $semestersFromRegs = (clone $regQuery)
            ->whereNotNull('semester')
            ->where('semester', '!=', '')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester');

        $yearsFromCourses = (clone $courseQuery)
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        $yearsFromRegs = (clone $regQuery)
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');

        $semesters = $semestersFromCourses->merge($semestersFromRegs)->unique()->sort()->values();
        if ($semesters->isEmpty()) {
            $semesters = collect(['First Semester', 'Second Semester']);
        }

        $academicYears = $yearsFromCourses->merge($yearsFromRegs)->unique()->sortDesc()->values();

        return [
            'semesters' => $semesters,
            'academicYears' => $academicYears,
        ];
    }

    private function rowKey(int $courseId, string $semester, string $academicYear): string
    {
        return $courseId . '|' . $semester . '|' . $academicYear;
    }

    private function makeRow(Course $course, string $semester, string $academicYear, int $count): object
    {
        return (object) [
            'course_id' => $course->id,
            'course_code' => $course->course_code,
            'course_title' => $course->course_title,
            'program_name' => $course->program->name ?? '—',
            'department_name' => $course->program->department->name ?? '—',
            'semester' => $semester,
            'academic_year' => $academicYear,
            'registered_count' => $count,
            'is_elective' => (bool) $course->is_elective,
            'is_active' => (bool) $course->is_active,
        ];
    }
}
