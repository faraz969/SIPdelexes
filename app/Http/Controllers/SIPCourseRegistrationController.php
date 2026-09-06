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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

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

        $studentLevel = \App\Models\Student::normalizeLevel($student->level ?? null);

        $availableOfferings = SemesterCourseOffering::published()
            ->where('program_id', $student->program_id)
            ->forLevel($studentLevel)
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

        $studentLevel = \App\Models\Student::normalizeLevel($student->level ?? null);

        $offering = SemesterCourseOffering::published()
            ->where('id', $request->offering_id)
            ->where('program_id', $student->program_id)
            ->forLevel($studentLevel)
            ->first();

        if (!$offering) {
            return back()->with('error', 'This course package is not available for your program/level or is not published.');
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

    /**
     * Download Proof of Registration PDF for a completed course registration.
     */
    public function downloadProof(CourseRegistration $registration)
    {
        $student = $this->getStudent();

        if ((int) $registration->student_id !== (int) $student->id) {
            abort(403, 'Unauthorized access.');
        }

        $student->load(['user', 'program', 'preferredSession', 'application.admissionForm']);

        $admissionForm = optional($student->application)->admissionForm;
        $applicationData = is_array(optional($student->application)->data)
            ? $student->application->data
            : [];
        $studentName = optional($student->user)->name
            ?: ($admissionForm->full_name ?? $student->student_id);
        $programmeName = optional($student->program)->name ?: 'N/A';
        $level = Student::normalizeLevel($student->level ?? null);
        $levelLabel = $level . 'L';

        $doaDate = $student->admission_date
            ?: optional($registration->registered_at)
            ?: now();
        $doa = strtoupper(Carbon::parse($doaDate)->format('MY'));

        $durationYears = $this->estimateProgramYears(optional($student->program)->duration);
        $doc = strtoupper(Carbon::parse($doaDate)->copy()->addYears($durationYears)->format('MY'));

        $campus = $admissionForm->preferred_campus
            ?? ($applicationData['preferred_campus'] ?? null)
            ?? '—';
        $session = optional($student->preferredSession)->name
            ?? $admissionForm->preferred_session
            ?? ($applicationData['preferred_session'] ?? null)
            ?? '—';

        $semesterLabel = trim($registration->academic_year . ' ' . $this->formatSemesterLabel($registration->semester));

        $courses = [];
        $totalCredits = 0;
        foreach (($registration->courses ?? []) as $course) {
            $credits = $course['credit_units'] ?? $course['credits'] ?? 0;
            $credits = is_numeric($credits) ? (float) $credits : 0;
            $totalCredits += $credits;
            $courses[] = [
                'code' => $course['course_code'] ?? $course['code'] ?? 'N/A',
                'title' => strtoupper((string) ($course['course_title'] ?? $course['name'] ?? 'N/A')),
                'credits' => $credits == (int) $credits ? (int) $credits : rtrim(rtrim(number_format($credits, 2), '0'), '.'),
            ];
        }

        $logoPath = public_path('images/logo_blue.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('images/logo.png');
        }
        $logoSrc = file_exists($logoPath) ? $logoPath : null;

        $photoSrc = $this->resolveStudentPhotoSrc($admissionForm);

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'course_registration_proof_downloaded',
            'model_type' => CourseRegistration::class,
            'model_id' => $registration->id,
            'system_source' => 'SIP',
            'description' => "Downloaded proof of registration for {$registration->semester} {$registration->academic_year}",
        ]);

        $pdf = Pdf::loadView('sip.course-registration.proof-pdf', [
            'student' => $student,
            'studentName' => $studentName,
            'programmeName' => $programmeName,
            'levelLabel' => $levelLabel,
            'doa' => $doa,
            'doc' => $doc,
            'campus' => $campus,
            'session' => $session,
            'semesterLabel' => $semesterLabel,
            'printedOn' => now()->format('F j, Y'),
            'courses' => $courses,
            'totalCredits' => $totalCredits == (int) $totalCredits ? (int) $totalCredits : rtrim(rtrim(number_format($totalCredits, 2), '0'), '.'),
            'logoSrc' => $logoSrc,
            'photoSrc' => $photoSrc,
        ])->setPaper('a4', 'portrait')
          ->setOption('enable-remote', false);

        $fileName = 'Proof_of_Registration_' . $student->student_id . '_' . str_replace(['/', ' '], ['-', '_'], $registration->academic_year . '_' . $registration->semester) . '.pdf';

        return $pdf->download($fileName);
    }

    protected function formatSemesterLabel(?string $semester): string
    {
        $semester = trim((string) $semester);
        $map = [
            'First Semester' => 'Semester 1',
            'Second Semester' => 'Semester 2',
            'Third Semester' => 'Semester 3',
            'Semester 1' => 'Semester 1',
            'Semester 2' => 'Semester 2',
            'Semester 3' => 'Semester 3',
        ];

        return $map[$semester] ?? $semester;
    }

    protected function estimateProgramYears(?string $duration): int
    {
        if ($duration && preg_match('/(\d+)/', $duration, $matches)) {
            $years = (int) $matches[1];
            if ($years > 0 && $years <= 10) {
                return $years;
            }
        }

        return 4;
    }

    protected function resolveStudentPhotoSrc($admissionForm): ?string
    {
        if (!$admissionForm || !is_array($admissionForm->uploads ?? null)) {
            return null;
        }

        $relative = $admissionForm->uploads['passport_picture'] ?? null;
        if (empty($relative)) {
            return null;
        }

        $fullPath = storage_path('app/public/' . ltrim($relative, '/'));
        if (!file_exists($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
}
