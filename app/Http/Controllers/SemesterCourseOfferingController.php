<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Program;
use App\Models\SemesterCourseOffering;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SemesterCourseOfferingController extends Controller
{
    public function index(Request $request)
    {
        $context = $this->panelContext();

        $query = SemesterCourseOffering::with(['program.department', 'creator'])
            ->orderByDesc('academic_year')
            ->orderBy('semester')
            ->orderBy('program_id');

        if ($context['department_id']) {
            $query->whereHas('program', function ($q) use ($context) {
                $q->where('department_id', $context['department_id']);
            });
        }

        if ($request->filled('program_id')) {
            $this->assertProgramAllowed((int) $request->program_id);
            $query->where('program_id', (int) $request->program_id);
        }

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        $offerings = $query->get();
        $programs = $this->allowedPrograms();

        return view('shared.semester-offerings.index', [
            'offerings' => $offerings,
            'programs' => $programs,
            'routePrefix' => $context['route_prefix'],
            'pageTitle' => $context['page_title'] . ' — Semester Course Packages',
            'department' => $context['department'],
        ]);
    }

    public function create()
    {
        $context = $this->panelContext();
        $programs = $this->allowedPrograms();

        return view('shared.semester-offerings.create', [
            'programs' => $programs,
            'coursesByProgram' => $this->coursesGroupedByProgram($programs->pluck('id')->all()),
            'defaultAcademicYear' => SiteSetting::currentAcademicYear(),
            'routePrefix' => $context['route_prefix'],
            'pageTitle' => 'Create Semester Course Package',
            'department' => $context['department'],
        ]);
    }

    public function store(Request $request)
    {
        $context = $this->panelContext();
        $validated = $this->validateOffering($request);

        $exists = SemesterCourseOffering::where('program_id', $validated['program_id'])
            ->where('academic_year', $validated['academic_year'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A course package already exists for this program, academic year, and semester.');
        }

        SemesterCourseOffering::create([
            'program_id' => $validated['program_id'],
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'course_ids' => array_map('intval', $validated['course_ids']),
            'is_published' => $request->boolean('is_published'),
            'created_by' => Auth::id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route($context['route_prefix'] . '.semester-offerings.index')
            ->with('success', 'Semester course package created successfully.');
    }

    public function show(SemesterCourseOffering $semester_offering)
    {
        $context = $this->panelContext();
        $this->assertOfferingAllowed($semester_offering);
        $semester_offering->load(['program.department', 'creator']);
        $courses = $semester_offering->courses();
        $registrationCount = $semester_offering->registrations()->count();

        return view('shared.semester-offerings.show', [
            'offering' => $semester_offering,
            'courses' => $courses,
            'registrationCount' => $registrationCount,
            'routePrefix' => $context['route_prefix'],
            'pageTitle' => 'Semester Course Package',
        ]);
    }

    public function edit(SemesterCourseOffering $semester_offering)
    {
        $context = $this->panelContext();
        $this->assertOfferingAllowed($semester_offering);
        $programs = $this->allowedPrograms();

        return view('shared.semester-offerings.edit', [
            'offering' => $semester_offering,
            'programs' => $programs,
            'coursesByProgram' => $this->coursesGroupedByProgram($programs->pluck('id')->all()),
            'routePrefix' => $context['route_prefix'],
            'pageTitle' => 'Edit Semester Course Package',
            'department' => $context['department'],
        ]);
    }

    public function update(Request $request, SemesterCourseOffering $semester_offering)
    {
        $context = $this->panelContext();
        $this->assertOfferingAllowed($semester_offering);
        $validated = $this->validateOffering($request, $semester_offering->id);

        $duplicate = SemesterCourseOffering::where('program_id', $validated['program_id'])
            ->where('academic_year', $validated['academic_year'])
            ->where('semester', $validated['semester'])
            ->where('id', '!=', $semester_offering->id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Another package already exists for this program, academic year, and semester.');
        }

        $semester_offering->update([
            'program_id' => $validated['program_id'],
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'course_ids' => array_map('intval', $validated['course_ids']),
            'is_published' => $request->boolean('is_published'),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route($context['route_prefix'] . '.semester-offerings.index')
            ->with('success', 'Semester course package updated successfully.');
    }

    public function destroy(SemesterCourseOffering $semester_offering)
    {
        $context = $this->panelContext();
        $this->assertOfferingAllowed($semester_offering);

        if ($semester_offering->registrations()->exists()) {
            return back()->with('error', 'Cannot delete this package because students have already registered against it.');
        }

        $semester_offering->delete();

        return redirect()->route($context['route_prefix'] . '.semester-offerings.index')
            ->with('success', 'Semester course package deleted.');
    }

    public function togglePublish(SemesterCourseOffering $semester_offering)
    {
        $context = $this->panelContext();
        $this->assertOfferingAllowed($semester_offering);

        if (!$semester_offering->is_published && empty($semester_offering->course_ids)) {
            return back()->with('error', 'Add at least one course before publishing.');
        }

        $semester_offering->update(['is_published' => !$semester_offering->is_published]);

        $msg = $semester_offering->is_published
            ? 'Package published. Students in this program can now confirm registration.'
            : 'Package unpublished. Students can no longer start new registrations for it.';

        return redirect()->route($context['route_prefix'] . '.semester-offerings.index')->with('success', $msg);
    }

    private function validateOffering(Request $request, ?int $ignoreId = null): array
    {
        $programIds = $this->allowedPrograms()->pluck('id')->all();

        $validated = $request->validate([
            'program_id' => ['required', 'integer', Rule::in($programIds)],
            'academic_year' => 'required|string|max:50',
            'semester' => 'required|string|max:100',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'integer|exists:courses,id',
            'notes' => 'nullable|string|max:2000',
            'is_published' => 'boolean',
        ]);

        $invalid = Course::whereIn('id', $validated['course_ids'])
            ->where('program_id', '!=', $validated['program_id'])
            ->exists();

        if ($invalid) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'course_ids' => 'All selected courses must belong to the chosen program.',
            ]);
        }

        return $validated;
    }

    private function panelContext(): array
    {
        $user = Auth::user();

        if ($user->isHOD()) {
            $department = $user->department;
            if (!$department) {
                abort(403, 'No department assigned to your account.');
            }

            return [
                'role' => 'hod',
                'route_prefix' => 'hod',
                'department_id' => $department->id,
                'department' => $department,
                'page_title' => $department->name,
            ];
        }

        if ($user->isRegistrar()) {
            return [
                'role' => 'registrar',
                'route_prefix' => 'registrar',
                'department_id' => null,
                'department' => null,
                'page_title' => 'Registrar',
            ];
        }

        abort(403);
    }

    private function allowedPrograms()
    {
        $context = $this->panelContext();
        $query = Program::where('is_active', true)->orderBy('name');

        if ($context['department_id']) {
            $query->where('department_id', $context['department_id']);
        }

        return $query->get();
    }

    private function coursesGroupedByProgram(array $programIds): array
    {
        if (empty($programIds)) {
            return [];
        }

        $courses = Course::whereIn('program_id', $programIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('course_code')
            ->get();

        $grouped = [];
        foreach ($courses as $course) {
            $grouped[$course->program_id][] = [
                'id' => $course->id,
                'label' => $course->course_code . ' — ' . $course->course_title
                    . ' (' . (float) ($course->total_credit_units ?? $course->credit_units) . ' cr)'
                    . ($course->is_elective ? ' [Elective]' : ' [Core]'),
                'credits' => (float) ($course->total_credit_units ?? $course->credit_units),
            ];
        }

        return $grouped;
    }

    private function assertProgramAllowed(int $programId): void
    {
        if (!$this->allowedPrograms()->contains('id', $programId)) {
            abort(403, 'Program is not available for your account.');
        }
    }

    private function assertOfferingAllowed(SemesterCourseOffering $offering): void
    {
        $offering->loadMissing('program');
        $context = $this->panelContext();

        if ($context['department_id'] && (int) optional($offering->program)->department_id !== (int) $context['department_id']) {
            abort(403, 'This package does not belong to your department.');
        }
    }
}
