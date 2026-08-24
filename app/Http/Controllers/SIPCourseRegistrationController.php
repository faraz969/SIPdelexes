<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\CourseRegistration;
use App\Models\RegistrationRule;
use App\Models\SemesterCourseOffering;
use App\Models\SiteSetting;
use App\Services\ERPIntegrationService;
use App\Services\ActivityLogService;

class SIPCourseRegistrationController extends Controller
{
    protected $erpService;
    protected $activityLogService;

    public function __construct(ERPIntegrationService $erpService, ActivityLogService $activityLogService)
    {
        $this->middleware('auth');
        $this->erpService = $erpService;
        $this->activityLogService = $activityLogService;
    }

    protected function getStudent()
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            abort(403, 'SIP account not found.');
        }

        return $student;
    }

    /**
     * Show course registration confirmation form (package defined by HOD/Registrar).
     */
    public function showRegistrationForm(Request $request)
    {
        $student = $this->getStudent();

        if (!$student->canRegisterForCourses()) {
            $rule = RegistrationRule::getActiveRule();
            $requiredPercentage = $rule ? $rule->minimum_payment_percentage : 70;
            $currentPercentage = $student->getPaymentPercentage();

            return redirect()->route('sip.dashboard')
                ->with('error', "You need to pay at least {$requiredPercentage}% of fees to register for courses. Your current payment: {$currentPercentage}%");
        }

        if ($student->isDeferred()) {
            return redirect()->route('sip.dashboard')
                ->with('error', 'You cannot register for courses while your admission is deferred.');
        }

        if (!$student->program_id) {
            return view('sip.course-registration.form', [
                'student' => $student,
                'availableOfferings' => collect(),
                'offering' => null,
                'courses' => collect(),
                'existingRegistration' => null,
                'semester' => null,
                'academicYear' => null,
            ]);
        }

        $availableOfferings = SemesterCourseOffering::published()
            ->where('program_id', $student->program_id)
            ->orderByDesc('academic_year')
            ->orderBy('semester')
            ->get();

        $offering = null;
        if ($request->filled('offering_id')) {
            $offering = $availableOfferings->firstWhere('id', (int) $request->offering_id);
        }

        if (!$offering && $availableOfferings->isNotEmpty()) {
            $preferredYear = $request->get('academic_year', $student->academic_year ?: SiteSetting::currentAcademicYear());
            $preferredSemester = $request->get('semester', 'First Semester');

            $offering = $availableOfferings->first(function ($item) use ($preferredYear, $preferredSemester) {
                return $item->academic_year === $preferredYear && $item->semester === $preferredSemester;
            }) ?: $availableOfferings->first();
        }

        $courses = $offering ? $offering->courses() : collect();
        $semester = $offering->semester ?? null;
        $academicYear = $offering->academic_year ?? null;

        $existingRegistration = null;
        if ($offering) {
            $existingRegistration = CourseRegistration::where('student_id', $student->id)
                ->where('semester', $offering->semester)
                ->where('academic_year', $offering->academic_year)
                ->first();
        }

        return view('sip.course-registration.form', compact(
            'student',
            'availableOfferings',
            'offering',
            'courses',
            'existingRegistration',
            'semester',
            'academicYear'
        ));
    }

    /**
     * Confirm registration for the published semester course package.
     */
    public function registerCourses(Request $request)
    {
        $student = $this->getStudent();

        if (!$student->canRegisterForCourses()) {
            return back()->with('error', 'You are not eligible to register for courses.');
        }

        if ($student->isDeferred()) {
            return back()->with('error', 'You cannot register for courses while your admission is deferred.');
        }

        $request->validate([
            'offering_id' => 'required|integer|exists:semester_course_offerings,id',
            'confirm_registration' => 'accepted',
        ], [
            'confirm_registration.accepted' => 'Please tick the confirmation box to register for the listed courses.',
        ]);

        if (!$student->program_id) {
            return back()->with('error', 'You have no program assigned. Please contact the registrar.');
        }

        $offering = SemesterCourseOffering::published()
            ->where('id', $request->offering_id)
            ->where('program_id', $student->program_id)
            ->first();

        if (!$offering) {
            return back()->with('error', 'This course package is not available for your program or is not published.');
        }

        $existingRegistration = CourseRegistration::where('student_id', $student->id)
            ->where('semester', $offering->semester)
            ->where('academic_year', $offering->academic_year)
            ->first();

        if ($existingRegistration) {
            return redirect()->route('sip.course-registration.list')
                ->with('error', 'You have already registered for ' . $offering->semester . ' ' . $offering->academic_year . '.');
        }

        $coursesPayload = $offering->coursesPayload();
        if (empty($coursesPayload)) {
            return back()->with('error', 'This course package has no active courses. Please contact your HOD or Registrar.');
        }

        $rule = RegistrationRule::getActiveRule();
        $isLate = false;
        $lateFee = 0;

        if ($rule) {
            // Placeholder for future registration-period / late-fee logic
            $isLate = false;
            if ($isLate) {
                $lateFee = $rule->late_registration_fee;
            }
        }

        $registration = CourseRegistration::create([
            'student_id' => $student->id,
            'semester_course_offering_id' => $offering->id,
            'semester' => $offering->semester,
            'academic_year' => $offering->academic_year,
            'courses' => $coursesPayload,
            'status' => $isLate ? 'late' : 'registered',
            'late_fee' => $lateFee,
            'is_late_registration' => $isLate,
            'registered_at' => now(),
        ]);

        if ($lateFee > 0) {
            \App\Models\Invoice::create([
                'student_id' => $student->id,
                'invoice_number' => 'LATE-' . strtoupper(uniqid()),
                'invoice_type' => 'late_registration',
                'academic_year' => $offering->academic_year,
                'semester' => $offering->semester,
                'total_amount' => $lateFee,
                'paid_amount' => 0,
                'balance' => $lateFee,
                'status' => 'pending',
                'due_date' => now()->addDays(14),
                'issued_date' => now(),
            ]);
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'courses_registered',
            'model_type' => CourseRegistration::class,
            'model_id' => $registration->id,
            'system_source' => 'SIP',
            'description' => "Confirmed course registration for {$offering->semester} {$offering->academic_year} (package #{$offering->id})",
        ]);

        return redirect()->route('sip.course-registration.list')
            ->with('success', 'Course registration confirmed successfully.');
    }

    public function showRegisteredCourses()
    {
        $student = $this->getStudent();
        $registrations = $student->courseRegistrations()
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'desc')
            ->get();

        return view('sip.course-registration.list', compact('student', 'registrations'));
    }
}
