<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use App\Models\Course;
use App\Models\Department;
use App\Models\AdmissionFormData;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Services\SIPAutomationService;
use App\Services\AdmissionFormService;
use App\Services\CourseEnrollmentService;

class RegistrarController extends Controller
{
    protected $sipAutomationService;
    protected $admissionFormService;

    public function __construct(SIPAutomationService $sipAutomationService, AdmissionFormService $admissionFormService)
    {
        $this->sipAutomationService = $sipAutomationService;
        $this->admissionFormService = $admissionFormService;
    }
    public function dashboard(Request $request)
    {
        $academicYear = trim((string) $request->get('academic_year', ''));
        $departmentId = $request->get('department_id');

        $departments = Department::orderBy('name')->get();

        $academicYears = Application::where('status', '!=', 'draft')
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year');

        $baseQuery = Application::query()->where('status', '!=', 'draft');

        if ($academicYear !== '') {
            $baseQuery->where('academic_year', $academicYear);
        }

        if (!empty($departmentId)) {
            $baseQuery->where(function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId)
                    ->orWhereJsonContains('department_ids', (int) $departmentId)
                    ->orWhereJsonContains('department_ids', (string) $departmentId);
            });
        }

        $pendingApplications = (clone $baseQuery)
            ->with(['user', 'department', 'examRecords.subjects'])
            ->where('hod_status', 'approved')
            ->where('registrar_status', 'pending')
            ->orderBy('hod_reviewed_at', 'desc')
            ->get();

        $reviewedApplications = (clone $baseQuery)
            ->with(['user', 'department', 'examRecords.subjects'])
            ->whereIn('registrar_status', ['approved', 'rejected'])
            ->orderBy('registrar_reviewed_at', 'desc')
            ->get();

        $allApplications = (clone $baseQuery)
            ->with(['user', 'department', 'examRecords.subjects'])
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'pending' => (clone $baseQuery)
                ->where('hod_status', 'approved')
                ->where('registrar_status', 'pending')
                ->count(),
            'approved' => (clone $baseQuery)->where('registrar_status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('registrar_status', 'rejected')->count(),
            'total_applications' => (clone $baseQuery)->count(),
        ];

        return view('registrar.dashboard', compact(
            'pendingApplications',
            'reviewedApplications',
            'allApplications',
            'stats',
            'departments',
            'academicYears',
            'academicYear',
            'departmentId'
        ));
    }

    /**
     * List applications by registrar review status.
     */
    public function applications(Request $request, $status)
    {
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            abort(404);
        }

        $academicYear = trim((string) $request->get('academic_year', ''));
        $departmentId = $request->get('department_id');

        $departments = Department::orderBy('name')->get();

        $academicYears = Application::where('status', '!=', 'draft')
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year');

        $baseQuery = Application::query()->where('status', '!=', 'draft');

        if ($academicYear !== '') {
            $baseQuery->where('academic_year', $academicYear);
        }

        if (!empty($departmentId)) {
            $baseQuery->where(function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId)
                    ->orWhereJsonContains('department_ids', (int) $departmentId)
                    ->orWhereJsonContains('department_ids', (string) $departmentId);
            });
        }

        if ($status === 'pending') {
            $baseQuery->where('hod_status', 'approved')->where('registrar_status', 'pending');
            $baseQuery->orderBy('hod_reviewed_at', 'desc');
        } else {
            $baseQuery->where('registrar_status', $status);
            $baseQuery->orderBy('registrar_reviewed_at', 'desc');
        }

        $applications = $baseQuery
            ->with(['user', 'department', 'examRecords.subjects'])
            ->get();

        $titles = [
            'pending' => 'Pending Applications',
            'approved' => 'Approved Applications',
            'rejected' => 'Rejected Applications',
        ];

        return view('registrar.applications.index', [
            'applications' => $applications,
            'status' => $status,
            'pageTitle' => $titles[$status],
            'departments' => $departments,
            'academicYears' => $academicYears,
            'academicYear' => $academicYear,
            'departmentId' => $departmentId,
        ]);
    }

    public function pendingApplications(Request $request)
    {
        return $this->applications($request, 'pending');
    }

    public function approvedApplications(Request $request)
    {
        return $this->applications($request, 'approved');
    }

    public function rejectedApplications(Request $request)
    {
        return $this->applications($request, 'rejected');
    }

    public function showApplication(Application $application)
    {
        // Prevent registrar from viewing draft applications
        if ($application->status === 'draft') {
            abort(403, 'You cannot view draft applications.');
        }
        
        $application->load(['user', 'department', 'admissionForm']);
        $examRecords = \App\Models\ExamRecord::with('subjects')
            ->where('application_id', $application->id)
            ->get();
        
        return view('registrar.application.show', compact('application', 'examRecords'));
    }

    public function approveApplication(Request $request, Application $application)
    {
        // Prevent registrar from approving draft applications
        if ($application->status === 'draft') {
            abort(403, 'You cannot approve draft applications.');
        }
        
        // Ensure registrar can only approve applications that have been approved by HOD
        if ($application->hod_status !== 'approved') {
            return redirect()->route('registrar.dashboard')
                ->with('error', 'You can only approve applications that have been approved by HOD.');
        }

        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            $student = $this->sipAutomationService->processAdmissionApproval(
                $application,
                $request->comments
            );
            
            // Apply admission form defaults (set by admin) for this student, by academic year
            $academicYear = $application->academic_year;
            $defaults = \App\Models\AdmissionFormDefault::where('academic_year', $academicYear)->first();
            if (!$defaults) {
                // Fallback to any default if specific academic year not found
                $defaults = \App\Models\AdmissionFormDefault::first();
            }
            if ($defaults) {
                $program = $student->program;
                $totalFees = $program && $program->price !== null ? $program->price : null;

                AdmissionFormData::updateOrCreate(
                    ['student_id' => $student->id],
                    [
                        'application_id' => $application->id,
                        'total_fees' => $totalFees,
                        'minimum_fee_percentage' => $defaults->minimum_fee_percentage,
                        'balance_percentage' => $defaults->balance_percentage,
                        'paid_fees_by_date' => $defaults->paid_fees_by_date,
                        'registration_begins' => $defaults->registration_begins,
                        'orientation_new_students' => $defaults->orientation_new_students,
                        'faculty_orientation' => $defaults->faculty_orientation,
                        'lectures_begin' => $defaults->lectures_begin,
                    ]
                );

                // Create download record for admission form (HTML/PDF view)
                try {
                    $this->admissionFormService->createDownloadRecord($student);
                    
                    \Log::info("Admission form download record created", [
                        'student_id' => $student->student_id,
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to create admission form download record', [
                        'student_id' => $student->student_id,
                        'error' => $e->getMessage(),
                    ]);
                    // Don't fail the approval if download record creation fails
                }
            }
            
            return redirect()->route('registrar.dashboard')
                ->with('success', 'Application approved successfully. SIP account and ERPNext applicant created.');
        } catch (\Exception $e) {
            \Log::error('Registrar Approval Failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);

            $data = is_array($application->data) ? $application->data : [];
            $data['_erp_last_error'] = $e->getMessage();
            $data['_erp_last_error_at'] = now()->toDateTimeString();
            $application->data = $data;
            $application->save();
            
            return redirect()->route('registrar.applications.show', $application->id)
                ->with('error', $e->getMessage());
        }
    }

    public function rejectApplication(Request $request, Application $application)
    {
        // Prevent registrar from rejecting draft applications
        if ($application->status === 'draft') {
            abort(403, 'You cannot reject draft applications.');
        }
        
        // Ensure registrar can only reject applications that have been approved by HOD
        if ($application->hod_status !== 'approved') {
            return redirect()->route('registrar.dashboard')
                ->with('error', 'You can only reject applications that have been approved by HOD.');
        }

        $request->validate([
            'comments' => 'required|string|max:1000'
        ]);

        $application->update([
            'registrar_status' => 'rejected',
            'registrar_comments' => $request->comments,
            'registrar_reviewed_at' => now(),
        ]);

        // Update main status based on workflow
        $application->updateMainStatus();

        return redirect()->route('registrar.dashboard')
            ->with('success', 'Application rejected.');
    }

    /**
     * View deferment requests
     */
    public function deferments()
    {
        $pendingDeferments = \App\Models\Deferment::with(['student.user', 'student.program'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $allDeferments = \App\Models\Deferment::with(['student.user', 'student.program', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('registrar.deferments', compact('pendingDeferments', 'allDeferments'));
    }

    /**
     * Approve deferment
     */
    public function approveDeferment(Request $request, \App\Models\Deferment $deferment)
    {
        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        $deferment->update([
            'status' => 'approved',
            'registrar_comments' => $request->comments,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Update student status
        $student = $deferment->student;
        $student->update([
            'academic_status' => 'deferred',
            'deferred_at' => now(),
        ]);

        // Notify ERP
        try {
            $erpService = app(\App\Services\ERPIntegrationService::class);
            $erpService->notifyDeferment($student->student_id, [
                'defer_from' => $deferment->defer_from,
                'defer_to' => $deferment->defer_to,
                'reason' => $deferment->reason,
            ]);
        } catch (\Exception $e) {
            \Log::error('ERP Deferment Notification Error: ' . $e->getMessage());
        }

        return redirect()->route('registrar.deferments')
            ->with('success', 'Deferment approved successfully.');
    }

    /**
     * Reject deferment
     */
    public function rejectDeferment(Request $request, \App\Models\Deferment $deferment)
    {
        $request->validate([
            'comments' => 'required|string|max:1000',
        ]);

        $deferment->update([
            'status' => 'rejected',
            'registrar_comments' => $request->comments,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('registrar.deferments')
            ->with('success', 'Deferment rejected.');
    }

    /**
     * Reactivate student
     */
    public function reactivateStudent(Request $request, \App\Models\Deferment $deferment)
    {
        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        $deferment->update([
            'status' => 'reactivated',
            'reactivated_at' => now(),
        ]);

        $student = $deferment->student;
        $student->update([
            'academic_status' => 'active',
            'reactivated_at' => now(),
        ]);

        // Notify ERP to resume billing
        try {
            $erpService = app(\App\Services\ERPIntegrationService::class);
            $erpService->notifyDeferment($student->student_id, [
                'action' => 'reactivate',
            ]);
        } catch (\Exception $e) {
            \Log::error('ERP Reactivation Notification Error: ' . $e->getMessage());
        }

        return redirect()->route('registrar.deferments')
            ->with('success', 'Student reactivated successfully.');
    }

    public function courseEnrollments(Request $request, CourseEnrollmentService $enrollmentService)
    {
        $filters = [
            'department_id' => $request->filled('department_id') ? (int) $request->department_id : null,
            'program_id' => $request->filled('program_id') ? (int) $request->program_id : null,
            'semester' => $request->get('semester'),
            'academic_year' => $request->get('academic_year', SiteSetting::currentAcademicYear()),
        ];

        $rows = $enrollmentService->getEnrollmentRows($filters);
        $options = $enrollmentService->filterOptions($filters['department_id']);
        $departments = Department::orderBy('name')->get();
        $programsQuery = Program::where('is_active', true)->orderBy('name');
        if (!empty($filters['department_id'])) {
            $programsQuery->where('department_id', $filters['department_id']);
        }
        $programs = $programsQuery->get();

        return view('shared.course-enrollments.index', [
            'rows' => $rows,
            'departments' => $departments,
            'programs' => $programs,
            'semesters' => $options['semesters'],
            'academicYears' => $options['academicYears'],
            'semester' => $filters['semester'] ?? '',
            'academicYear' => $filters['academic_year'] ?? '',
            'programId' => $filters['program_id'],
            'departmentId' => $filters['department_id'],
            'showStudentsRoute' => 'registrar.course-enrollments.students',
            'filterRoute' => 'registrar.course-enrollments',
            'pageTitle' => 'Course Enrollments',
        ]);
    }

    public function courseEnrollmentStudents(Request $request, Course $course, CourseEnrollmentService $enrollmentService)
    {
        $semester = trim((string) $request->get('semester', ''));
        $academicYear = trim((string) $request->get('academic_year', ''));

        if ($semester === '' || $academicYear === '') {
            return redirect()->route('registrar.course-enrollments')
                ->with('error', 'Semester and academic year are required.');
        }

        $students = $enrollmentService->getRegisteredStudents($course, $semester, $academicYear);
        $course->load(['program.department']);

        return view('shared.course-enrollments.students', [
            'course' => $course,
            'students' => $students,
            'semester' => $semester,
            'academicYear' => $academicYear,
            'backRoute' => 'registrar.course-enrollments',
        ]);
    }
}
