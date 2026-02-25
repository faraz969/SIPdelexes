<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Models\User;
use App\Models\Course;
use App\Models\Session;
use Illuminate\Http\Request;

class LecturerController extends Controller
{
    public function index(Request $request)
    {
        $query = Lecturer::with(['user', 'course', 'session']);
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        $lecturers = $query->orderBy('created_at', 'desc')->get();
        $courses = Course::with('program')->where('is_active', true)->orderBy('course_code')->get();
        $sessions = Session::active()->ordered()->get();
        return view('admin.lecturers.index', compact('lecturers', 'courses', 'sessions'));
    }

    public function create()
    {
        $users = User::where('role', 'lecturer')->orderBy('name')->get();
        $courses = Course::with('program')->where('is_active', true)->orderBy('course_code')->get();
        $sessions = Session::active()->ordered()->get();
        return view('admin.lecturers.create', compact('users', 'courses', 'sessions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'in:' . User::where('role', 'lecturer')->pluck('id')->implode(',')],
            'course_id' => 'required|exists:courses,id',
            'session_id' => 'required|exists:sessions,id',
        ], [
            'user_id.in' => 'The selected user must have the Lecturer role.',
        ]);

        if (Lecturer::where('user_id', $validated['user_id'])
            ->where('course_id', $validated['course_id'])
            ->where('session_id', $validated['session_id'])
            ->exists()) {
            return back()->withInput()->with('error', 'This lecturer is already assigned to this course and session.');
        }

        Lecturer::create($validated);
        return redirect()->route('admin.lecturers.index')
            ->with('success', 'Lecturer assigned successfully.');
    }

    public function show(Lecturer $lecturer)
    {
        $lecturer->load(['user', 'course.program', 'session']);
        return view('admin.lecturers.show', compact('lecturer'));
    }

    public function edit(Lecturer $lecturer)
    {
        $users = User::where('role', 'lecturer')
            ->orWhere('id', $lecturer->user_id)
            ->orderBy('name')
            ->get();
        $courses = Course::with('program')->where('is_active', true)->orderBy('course_code')->get();
        $sessions = Session::active()->ordered()->get();
        return view('admin.lecturers.edit', compact('lecturer', 'users', 'courses', 'sessions'));
    }

    public function update(Request $request, Lecturer $lecturer)
    {
        $allowedUserIds = User::where('role', 'lecturer')->orWhere('id', $lecturer->user_id)->pluck('id')->unique()->implode(',');
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', "in:{$allowedUserIds}"],
            'course_id' => 'required|exists:courses,id',
            'session_id' => 'required|exists:sessions,id',
        ], [
            'user_id.in' => 'The selected user must have the Lecturer role.',
        ]);

        $exists = Lecturer::where('user_id', $validated['user_id'])
            ->where('course_id', $validated['course_id'])
            ->where('session_id', $validated['session_id'])
            ->where('id', '!=', $lecturer->id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'This lecturer is already assigned to this course and session.');
        }

        $lecturer->update($validated);
        return redirect()->route('admin.lecturers.index')
            ->with('success', 'Lecturer updated successfully.');
    }

    public function destroy(Lecturer $lecturer)
    {
        $lecturer->delete();
        return redirect()->route('admin.lecturers.index')
            ->with('success', 'Lecturer assignment removed.');
    }
}
