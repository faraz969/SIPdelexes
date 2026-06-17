<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Application;
use App\Models\Department;
use App\Models\AdmissionFormData;
use App\Services\SIPAutomationService;
use App\Services\AdmissionFormService;

class RegistrarController extends Controller
{
    protected $sipAutomationService;
    protected $admissionFormService;

    public function __construct(SIPAutomationService $sipAutomationService, AdmissionFormService $admissionFormService)
    {
        $this->sipAutomationService = $sipAutomationService;
        $this->admissionFormService = $admissionFormService;
    }
    public function dashboard()
    {
        // Get all applications that can be reviewed by registrar (hod_status = approved, exclude drafts)
        $pendingApplications = Application::with(['user', 'department', 'examRecords.subjects'])
            ->where('hod_status', 'approved')
            ->where('registrar_status', 'pending')
            ->where('status', '!=', 'draft')
            ->orderBy('hod_reviewed_at', 'desc')
            ->get();

        // Get all applications that have been reviewed by registrar (exclude drafts)
        $reviewedApplications = Application::with(['user', 'department', 'examRecords.subjects'])
            ->whereIn('registrar_status', ['approved', 'rejected'])
            ->where('status', '!=', 'draft')
            ->orderBy('registrar_reviewed_at', 'desc')
            ->get();

        // Get all applications for overview (registrar can see all, but exclude drafts)
        $allApplications = Application::with(['user', 'department', 'examRecords.subjects'])
            ->where('status', '!=', 'draft')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get statistics (exclude drafts)
        $stats = [
            'total_pending' => $pendingApplications->count(),
            'total_reviewed' => $reviewedApplications->count(),
            'total_applications' => $allApplications->count(),
            'approved_today' => Application::where('registrar_status', 'approved')
                ->where('status', '!=', 'draft')
                ->whereDate('registrar_reviewed_at', today())
                ->count(),
            'rejected_today' => Application::where('registrar_status', 'rejected')
                ->where('status', '!=', 'draft')
                ->whereDate('registrar_reviewed_at', today())
                ->count(),
        ];

        return view('registrar.dashboard', compact('pendingApplications', 'reviewedApplications', 'allApplications', 'stats'));
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
}
