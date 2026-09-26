<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseRegistration;
use App\Models\Student;
use App\Services\CourseMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SIPCourseMaterialController extends Controller
{
    protected CourseMaterialService $materialService;

    public function __construct(CourseMaterialService $materialService)
    {
        $this->middleware('auth');
        $this->materialService = $materialService;
    }

    protected function getStudent(): Student
    {
        $student = Student::where('user_id', Auth::id())->first();
        if (!$student) {
            abort(403, 'SIP account not found.');
        }

        return $student;
    }

    public function index()
    {
        $student = $this->getStudent();

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->whereIn('status', ['registered', 'late', 'pending'])
            ->orderByDesc('academic_year')
            ->orderByDesc('registered_at')
            ->get();

        $groups = [];
        foreach ($registrations as $registration) {
            foreach (($registration->courses ?? []) as $item) {
                $courseId = (int) ($item['id'] ?? 0);
                if ($courseId <= 0) {
                    continue;
                }

                $key = $courseId . '|' . $registration->academic_year . '|' . $registration->semester;
                if (isset($groups[$key])) {
                    continue;
                }

                $materials = CourseMaterial::published()
                    ->where('course_id', $courseId)
                    ->where('academic_year', $registration->academic_year)
                    ->where('semester', $registration->semester)
                    ->ordered()
                    ->get();

                $groups[$key] = [
                    'course_id' => $courseId,
                    'course_code' => $item['course_code'] ?? $item['code'] ?? 'N/A',
                    'course_title' => $item['course_title'] ?? $item['name'] ?? 'N/A',
                    'academic_year' => $registration->academic_year,
                    'semester' => $registration->semester,
                    'materials' => $materials,
                ];
            }
        }

        return view('sip.course-materials.index', [
            'student' => $student,
            'groups' => array_values($groups),
        ]);
    }

    public function course(Request $request, Course $course)
    {
        $student = $this->getStudent();

        $academicYear = trim((string) $request->get('academic_year', ''));
        $semester = trim((string) $request->get('semester', ''));

        if ($academicYear === '' || $semester === '') {
            return redirect()->route('sip.course-materials.index')
                ->with('error', 'Academic year and semester are required.');
        }

        if (!$this->materialService->studentIsRegisteredForCourse(
            $student,
            $course->id,
            $academicYear,
            $semester
        )) {
            abort(403, 'You are not registered for this course.');
        }

        $materials = CourseMaterial::published()
            ->where('course_id', $course->id)
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->with('uploader')
            ->ordered()
            ->get();

        return view('sip.course-materials.course', [
            'student' => $student,
            'course' => $course,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'materials' => $materials,
        ]);
    }

    public function download(CourseMaterial $material)
    {
        $student = $this->getStudent();
        $this->materialService->assertStudentCanAccess($student, $material);

        return $this->materialService->downloadResponse($material);
    }
}
