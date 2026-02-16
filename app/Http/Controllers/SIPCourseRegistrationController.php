<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\RegistrationRule;
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

    /**
     * Get authenticated student
     */
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
     * Show course registration form
     */
    public function showRegistrationForm(Request $request)
    {
        $student = $this->getStudent();

        // Check if student can register
        if (!$student->canRegisterForCourses()) {
            $rule = RegistrationRule::getActiveRule();
            $requiredPercentage = $rule ? $rule->minimum_payment_percentage : 70;
            $currentPercentage = $student->getPaymentPercentage();

            return redirect()->route('sip.dashboard')
                ->with('error', "You need to pay at least {$requiredPercentage}% of fees to register for courses. Your current payment: {$currentPercentage}%");
        }

        // Check if student is deferred
        if ($student->isDeferred()) {
            return redirect()->route('sip.dashboard')
                ->with('error', 'You cannot register for courses while your admission is deferred.');
        }

        $semester = $request->get('semester', 'First Semester');
        $academicYear = $request->get('academic_year', $student->academic_year);

        // Load courses by student's program (core + elective, active only)
        $coreCourses = collect();
        $electiveCourses = collect();
        if ($student->program_id) {
            $coreCourses = Course::where('program_id', $student->program_id)
                ->where('is_elective', false)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('course_code')
                ->get();
            $electiveCourses = Course::where('program_id', $student->program_id)
                ->where('is_elective', true)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('course_code')
                ->get();
        }

        // Check for existing registration
        $existingRegistration = CourseRegistration::where('student_id', $student->id)
            ->where('semester', $semester)
            ->where('academic_year', $academicYear)
            ->first();

        return view('sip.course-registration.form', compact('student', 'coreCourses', 'electiveCourses', 'semester', 'academicYear', 'existingRegistration'));
    }

    /**
     * Process course registration
     */
    public function registerCourses(Request $request)
    {
        $student = $this->getStudent();

        // Check eligibility
        if (!$student->canRegisterForCourses()) {
            return back()->with('error', 'You are not eligible to register for courses.');
        }

        $request->validate([
            'semester' => 'required|string',
            'academic_year' => 'required|string',
            'courses' => 'required|array|min:1',
            'courses.*' => 'required|integer|exists:courses,id',
        ]);

        if (!$student->program_id) {
            return back()->with('error', 'You have no program assigned. Please contact the registrar.');
        }

        // Load selected courses and ensure they belong to student's program
        $selectedCourses = Course::whereIn('id', $request->courses)
            ->where('program_id', $student->program_id)
            ->where('is_active', true)
            ->get();

        if ($selectedCourses->count() !== count($request->courses)) {
            return back()->with('error', 'One or more selected courses are invalid or not available for your program.');
        }

        $maxCredits = 21;
        $totalCredits = $selectedCourses->sum(function ($c) {
            return (float) ($c->total_credit_units ?? $c->credit_units);
        });
        if ($totalCredits > $maxCredits) {
            return back()->with('error', "Total credit units ({$totalCredits}) exceeds the maximum allowed ({$maxCredits}). Please select fewer or lower-credit courses.");
        }

        // Build courses payload for storage (id, code, title, credit_units)
        $coursesPayload = $selectedCourses->map(function ($c) {
            return [
                'id' => $c->id,
                'course_code' => $c->course_code,
                'course_title' => $c->course_title,
                'credit_units' => (float) ($c->total_credit_units ?? $c->credit_units),
            ];
        })->values()->toArray();

        // Check for late registration
        $rule = RegistrationRule::getActiveRule();
        $isLate = false;
        $lateFee = 0;

        if ($rule) {
            // Check if registration is late (simplified - should check actual registration period)
            // TODO: Implement proper registration period checking
            $isLate = false; // Placeholder
            if ($isLate) {
                $lateFee = $rule->late_registration_fee;
            }
        }

        // Create registration
        $registration = CourseRegistration::create([
            'student_id' => $student->id,
            'semester' => $request->semester,
            'academic_year' => $request->academic_year,
            'courses' => $coursesPayload,
            'status' => $isLate ? 'late' : 'registered',
            'late_fee' => $lateFee,
            'is_late_registration' => $isLate,
            'registered_at' => now(),
        ]);

        // If late fee applies, create invoice
        if ($lateFee > 0) {
            $invoice = \App\Models\Invoice::create([
                'student_id' => $student->id,
                'invoice_number' => 'LATE-' . strtoupper(uniqid()),
                'invoice_type' => 'late_registration',
                'academic_year' => $request->academic_year,
                'semester' => $request->semester,
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
            'description' => "Registered for {$request->semester} {$request->academic_year}",
        ]);

        return redirect()->route('sip.course-registration.show')
            ->with('success', 'Course registration successful.');
    }

    /**
     * View registered courses
     */
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

