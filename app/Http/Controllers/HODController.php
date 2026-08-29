<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Application;
use App\Models\Department;
use App\Models\Deferment;
use App\Services\SmsService;

class HODController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $department = $user->department;
        
        if (!$department) {
            return redirect()->route('admin.dashboard')->with('error', 'No department assigned to your account.');
        }

        $academicYear = trim((string) $request->get('academic_year', ''));

        $baseQuery = Application::query()
            ->where('status', '!=', 'draft')
            ->where(function ($query) use ($department) {
                $query->where('department_id', $department->id)
                    ->orWhereJsonContains('department_ids', $department->id)
                    ->orWhereJsonContains('department_ids', (string) $department->id);
            });

        $academicYears = (clone $baseQuery)
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year');

        $filteredQuery = clone $baseQuery;
        if ($academicYear !== '') {
            $filteredQuery->where('academic_year', $academicYear);
        }

        $pendingApplications = (clone $filteredQuery)
            ->with(['user', 'department', 'examRecords.subjects'])
            ->where('hod_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $reviewedApplications = (clone $filteredQuery)
            ->with(['user', 'department', 'examRecords.subjects'])
            ->whereIn('hod_status', ['approved', 'rejected'])
            ->orderBy('hod_reviewed_at', 'desc')
            ->get();

        $stats = [
            'pending' => (clone $filteredQuery)->where('hod_status', 'pending')->count(),
            'approved' => (clone $filteredQuery)->where('hod_status', 'approved')->count(),
            'rejected' => (clone $filteredQuery)->where('hod_status', 'rejected')->count(),
        ];

        return view('hod.dashboard', compact(
            'department',
            'pendingApplications',
            'reviewedApplications',
            'academicYears',
            'academicYear',
            'stats'
        ));
    }

    /**
     * List applications by HOD review status.
     */
    public function applications(Request $request, $status)
    {
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
            abort(404);
        }

        $user = Auth::user();
        $department = $user->department;

        if (!$department) {
            return redirect()->route('admin.dashboard')->with('error', 'No department assigned to your account.');
        }

        $academicYear = trim((string) $request->get('academic_year', ''));

        $baseQuery = Application::query()
            ->where('status', '!=', 'draft')
            ->where(function ($query) use ($department) {
                $query->where('department_id', $department->id)
                    ->orWhereJsonContains('department_ids', $department->id)
                    ->orWhereJsonContains('department_ids', (string) $department->id);
            });

        $academicYears = (clone $baseQuery)
            ->whereNotNull('academic_year')
            ->where('academic_year', '!=', '')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year');

        $filteredQuery = clone $baseQuery;
        if ($academicYear !== '') {
            $filteredQuery->where('academic_year', $academicYear);
        }

        $filteredQuery->where('hod_status', $status);

        if ($status === 'pending') {
            $filteredQuery->orderBy('created_at', 'desc');
        } else {
            $filteredQuery->orderBy('hod_reviewed_at', 'desc');
        }

        $applications = $filteredQuery
            ->with(['user', 'department', 'examRecords.subjects'])
            ->get();

        $titles = [
            'pending' => 'Pending Applications',
            'approved' => 'Approved Applications',
            'rejected' => 'Rejected Applications',
        ];

        return view('hod.applications.index', [
            'department' => $department,
            'applications' => $applications,
            'status' => $status,
            'pageTitle' => $titles[$status],
            'academicYears' => $academicYears,
            'academicYear' => $academicYear,
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
        $user = Auth::user();
        
        // Prevent HOD from viewing draft applications
        if ($application->status === 'draft') {
            abort(403, 'You cannot view draft applications.');
        }
        
        // Ensure HOD can only view applications from their department
        if (!$application->belongsToDepartment($user->department_id)) {
            abort(403, 'You can only view applications from your department.');
        }

        $application->load(['user', 'department', 'admissionForm']);
        $examRecords = \App\Models\ExamRecord::with('subjects')
            ->where('application_id', $application->id)
            ->get();
        
        return view('hod.application.show', compact('application', 'examRecords'));
    }

    public function approveApplication(Request $request, Application $application)
    {
        $user = Auth::user();
        
        // Prevent HOD from approving draft applications
        if ($application->status === 'draft') {
            abort(403, 'You cannot approve draft applications.');
        }
        
        // Ensure HOD can only approve applications from their department
        if (!$application->belongsToDepartment($user->department_id)) {
            abort(403, 'You can only approve applications from your department.');
        }

        $request->validate([
            'comments' => 'nullable|string|max:1000'
        ]);

        $application->update([
            'hod_status' => 'approved',
            'hod_comments' => $request->comments,
            'hod_reviewed_at' => now(),
        ]);

        // Update main status based on workflow
        $application->updateMainStatus();

        $this->notifyStudentOfHodReview($application, 'approved');

        return redirect()->route('hod.dashboard')
            ->with('success', 'Application approved successfully.');
    }

    public function rejectApplication(Request $request, Application $application)
    {
        $user = Auth::user();
        
        // Prevent HOD from rejecting draft applications
        if ($application->status === 'draft') {
            abort(403, 'You cannot reject draft applications.');
        }
        
        // Ensure HOD can only reject applications from their department
        if (!$application->belongsToDepartment($user->department_id)) {
            abort(403, 'You can only reject applications from your department.');
        }

        $request->validate([
            'comments' => 'required|string|max:1000'
        ]);

        $application->update([
            'hod_status' => 'rejected',
            'hod_comments' => $request->comments,
            'hod_reviewed_at' => now(),
        ]);

        // Update main status based on workflow
        $application->updateMainStatus();

        $this->notifyStudentOfHodReview($application, 'rejected');

        return redirect()->route('hod.dashboard')
            ->with('success', 'Application rejected.');
    }

    public function deferments()
    {
        $user = Auth::user();
        $department = $user->department;

        if (!$department) {
            return redirect()->route('admin.dashboard')->with('error', 'No department assigned to your account.');
        }

        $departmentScope = function ($query) use ($department) {
            $query->whereHas('student', function ($studentQuery) use ($department) {
                $studentQuery->where('department_id', $department->id);
            });
        };

        $pendingDeferments = Deferment::with(['student.user', 'student.program'])
            ->where('status', 'pending')
            ->where('hod_status', 'pending')
            ->where($departmentScope)
            ->orderBy('created_at', 'desc')
            ->get();

        $allDeferments = Deferment::with(['student.user', 'student.program', 'hodReviewer'])
            ->where($departmentScope)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('hod.deferments.index', compact('department', 'pendingDeferments', 'allDeferments'));
    }

    public function approveDeferment(Request $request, Deferment $deferment)
    {
        $user = Auth::user();
        $department = $user->department;

        if (!$department || $deferment->student->department_id !== $department->id) {
            abort(403, 'You can only review deferments from your department.');
        }

        if (!$deferment->isPendingHodReview()) {
            return redirect()->route('hod.deferments')
                ->with('error', 'This deferment is no longer awaiting HOD review.');
        }

        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        $deferment->update([
            'hod_status' => 'approved',
            'hod_comments' => $request->comments,
            'hod_reviewed_by' => $user->id,
            'hod_reviewed_at' => now(),
        ]);

        return redirect()->route('hod.deferments')
            ->with('success', 'Deferment approved and forwarded to the Registrar.');
    }

    public function rejectDeferment(Request $request, Deferment $deferment)
    {
        $user = Auth::user();
        $department = $user->department;

        if (!$department || $deferment->student->department_id !== $department->id) {
            abort(403, 'You can only review deferments from your department.');
        }

        if (!$deferment->isPendingHodReview()) {
            return redirect()->route('hod.deferments')
                ->with('error', 'This deferment is no longer awaiting HOD review.');
        }

        $request->validate([
            'comments' => 'required|string|max:1000',
        ]);

        $deferment->update([
            'status' => 'rejected',
            'hod_status' => 'rejected',
            'hod_comments' => $request->comments,
            'hod_reviewed_by' => $user->id,
            'hod_reviewed_at' => now(),
        ]);

        return redirect()->route('hod.deferments')
            ->with('success', 'Deferment rejected.');
    }

    /**
     * SMS the applicant after HOD accept/reject. Failures must not block the review.
     */
    private function notifyStudentOfHodReview(Application $application, $decision)
    {
        $application->loadMissing(['user', 'admissionForm']);

        $user = $application->user;
        $phone = optional($application->admissionForm)->telephone
            ?: (is_array($application->data) ? ($application->data['telephone'] ?? null) : null)
            ?: optional($user)->phone;

        if (!$phone) {
            Log::warning('HOD review SMS skipped: no phone number', [
                'application_id' => $application->id,
            ]);
            return;
        }

        $name = optional($application->admissionForm)->full_name
            ?: optional($user)->name
            ?: 'Applicant';
        $applicationNumber = $application->application_number;

        if ($decision === 'approved') {
            $message = "Dear {$name}, your application {$applicationNumber} has been accepted by the Head of Department and is now awaiting Registrar review. - Delexes University College";
        } else {
            $message = "Dear {$name}, your application {$applicationNumber} was not successful at HOD review. Please contact the admissions office for more information. - Delexes University College";
        }

        try {
            $this->smsService->send($phone, $message);
        } catch (\Exception $e) {
            Log::error('HOD review SMS failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
