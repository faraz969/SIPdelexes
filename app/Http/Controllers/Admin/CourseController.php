<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('program');
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('type') && $request->type !== 'all') {
            if ($request->type === 'core') {
                $query->where('is_elective', false);
            } else {
                $query->where('is_elective', true);
            }
        }
        $courses = $query->orderBy('program_id')->orderBy('sort_order')->orderBy('course_code')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        return view('admin.courses.index', compact('courses', 'programs'));
    }

    public function create()
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        return view('admin.courses.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_code' => 'required|string|max:50|unique:courses,course_code',
            'course_title' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
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
        Course::create($validated);
        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function show(Course $course)
    {
        $course->load('program');
        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        return view('admin.courses.edit', compact('course', 'programs'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'course_code' => 'required|string|max:50|unique:courses,course_code,' . $course->id,
            'course_title' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
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
        $course->update($validated);
        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
