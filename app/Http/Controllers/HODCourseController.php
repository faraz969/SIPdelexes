<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Services\CourseEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class HODCourseController extends Controller
{
    public function index(Request $request)
    {
        $department = $this->requireDepartment();

        $query = Course::with('program')
            ->whereHas('program', function ($q) use ($department) {
                $q->where('department_id', $department->id);
            });

        if ($request->filled('program_id')) {
            $programId = (int) $request->program_id;
            $this->assertProgramBelongsToDepartment($programId, $department->id);
            $query->where('program_id', $programId);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('is_elective', $request->type === 'elective');
        }

        $courses = $query->orderBy('program_id')->orderBy('sort_order')->orderBy('course_code')->get();
        $programs = Program::where('department_id', $department->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hod.courses.index', compact('courses', 'programs', 'department'));
    }

    public function create()
    {
        $department = $this->requireDepartment();
        $programs = Program::where('department_id', $department->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $defaultAcademicYear = SiteSetting::currentAcademicYear();

        return view('hod.courses.create', compact('programs', 'department', 'defaultAcademicYear'));
    }

    public function store(Request $request)
    {
        $department = $this->requireDepartment();
        $validated = $this->validateCourse($request, $department->id);
        Course::create($validated);

        return redirect()->route('hod.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function show(Course $course)
    {
        $department = $this->requireDepartment();
        $this->assertCourseBelongsToDepartment($course, $department->id);
        $course->load('program');

        return view('hod.courses.show', compact('course', 'department'));
    }

    public function edit(Course $course)
    {
        $department = $this->requireDepartment();
        $this->assertCourseBelongsToDepartment($course, $department->id);
        $programs = Program::where('department_id', $department->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hod.courses.edit', compact('course', 'programs', 'department'));
    }

    public function update(Request $request, Course $course)
    {
        $department = $this->requireDepartment();
        $this->assertCourseBelongsToDepartment($course, $department->id);
        $validated = $this->validateCourse($request, $department->id, $course->id);
        $course->update($validated);

        return redirect()->route('hod.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $department = $this->requireDepartment();
        $this->assertCourseBelongsToDepartment($course, $department->id);
        $course->delete();

        return redirect()->route('hod.courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    public function enrollments(Request $request, CourseEnrollmentService $enrollmentService)
    {
        $department = $this->requireDepartment();

        $filters = [
            'department_id' => $department->id,
            'program_id' => $request->filled('program_id') ? (int) $request->program_id : null,
            'semester' => $request->get('semester'),
            'academic_year' => $request->get('academic_year', SiteSetting::currentAcademicYear()),
        ];

        if (!empty($filters['program_id'])) {
            $this->assertProgramBelongsToDepartment($filters['program_id'], $department->id);
        }

        $rows = $enrollmentService->getEnrollmentRows($filters);
        $options = $enrollmentService->filterOptions($department->id);
        $programs = Program::where('department_id', $department->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('shared.course-enrollments.index', [
            'department' => $department,
            'rows' => $rows,
            'programs' => $programs,
            'semesters' => $options['semesters'],
            'academicYears' => $options['academicYears'],
            'semester' => $filters['semester'] ?? '',
            'academicYear' => $filters['academic_year'] ?? '',
            'programId' => $filters['program_id'],
            'showStudentsRoute' => 'hod.course-enrollments.students',
            'filterRoute' => 'hod.course-enrollments',
            'pageTitle' => 'Course Enrollments — ' . $department->name,
        ]);
    }

    public function enrollmentStudents(Request $request, Course $course, CourseEnrollmentService $enrollmentService)
    {
        $department = $this->requireDepartment();
        $this->assertCourseBelongsToDepartment($course, $department->id);

        $semester = trim((string) $request->get('semester', ''));
        $academicYear = trim((string) $request->get('academic_year', ''));

        if ($semester === '' || $academicYear === '') {
            return redirect()->route('hod.course-enrollments')
                ->with('error', 'Semester and academic year are required.');
        }

        $students = $enrollmentService->getRegisteredStudents($course, $semester, $academicYear);
        $course->load(['program.department']);

        return view('shared.course-enrollments.students', [
            'course' => $course,
            'students' => $students,
            'semester' => $semester,
            'academicYear' => $academicYear,
            'backRoute' => 'hod.course-enrollments',
        ]);
    }

    private function validateCourse(Request $request, int $departmentId, ?int $courseId = null): array
    {
        $validated = $request->validate([
            'course_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses', 'course_code')->ignore($courseId),
            ],
            'course_title' => 'required|string|max:255',
            'program_id' => [
                'required',
                Rule::exists('programs', 'id')->where(fn ($q) => $q->where('department_id', $departmentId)),
            ],
            'academic_year' => 'nullable|string|max:50',
            'semester' => 'nullable|string|max:100',
            'credit_units' => 'required|numeric|min:0|max:99',
            'total_credit_units' => 'nullable|numeric|min:0|max:99',
            'assessment_split' => 'nullable|string|max:255',
            'is_elective' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_elective'] = $request->boolean('is_elective');
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }

    private function requireDepartment()
    {
        $department = Auth::user()->department;
        if (!$department) {
            abort(403, 'No department assigned to your account.');
        }

        return $department;
    }

    private function assertProgramBelongsToDepartment(int $programId, int $departmentId): void
    {
        $exists = Program::where('id', $programId)->where('department_id', $departmentId)->exists();
        if (!$exists) {
            abort(403, 'Program does not belong to your department.');
        }
    }

    private function assertCourseBelongsToDepartment(Course $course, int $departmentId): void
    {
        $course->loadMissing('program');
        if (!$course->program || (int) $course->program->department_id !== (int) $departmentId) {
            abort(403, 'Course does not belong to your department.');
        }
    }
}
