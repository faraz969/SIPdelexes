<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Lecturer;

class LecturerController extends Controller
{
    /**
     * Lecturer dashboard - lists assignments and assigned students
     */
    public function dashboard()
    {
        $user = Auth::user();
        $assignments = Lecturer::with(['course', 'session'])
            ->where('user_id', $user->id)
            ->orderBy('course_id')
            ->get();

        $assignmentsWithStudents = $assignments->map(function ($lecturer) {
            $students = $lecturer->getAssignedStudents();
            return [
                'lecturer' => $lecturer,
                'students' => $students,
            ];
        });

        return view('lecturer.dashboard', compact('assignmentsWithStudents'));
    }

    /**
     * View students for a specific lecturer assignment (course + session)
     */
    public function students(Lecturer $lecturer)
    {
        $user = Auth::user();
        if ($lecturer->user_id !== $user->id) {
            abort(403, 'Unauthorized.');
        }

        $students = $lecturer->getAssignedStudents();
        $lecturer->load(['course', 'session']);

        return view('lecturer.students', compact('lecturer', 'students'));
    }
}
