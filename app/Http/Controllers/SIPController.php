<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\CourseRegistration;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ExamPin;
use App\Models\Deferment;
use App\Models\Download;
use App\Models\SipDocument;
use App\Services\ActivityLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SIPController extends Controller
{
    protected $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->middleware('auth');
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
            abort(403, 'SIP account not found. Please contact administration.');
        }

        // Check if student is approved (can login)
        if (!$student->sip_account_created) {
            abort(403, 'Your admission is pending approval. You cannot access SIP yet.');
        }

        return $student;
    }

    /**
     * SIP Dashboard
     */
    public function dashboard()
    {
        $student = $this->getStudent();
        
        // Check if password needs to be changed (first login)
        $user = Auth::user();
        if (is_null($user->password_changed_at)) {
            return redirect()->route('sip.change-password')
                ->with('warning', 'Please change your password to continue.');
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'sip_dashboard_viewed',
            'model_type' => Student::class,
            'model_id' => $student->id,
            'system_source' => 'SIP',
        ]);

        $stats = [
            'total_invoices' => $student->invoices()->count(),
            'total_balance' => $student->getTotalBalance(),
            'payment_percentage' => round($student->getPaymentPercentage(), 2),
            'can_register' => $student->canRegisterForCourses(),
            'can_generate_exam_pin' => $student->canGenerateExamPin(),
            'active_deferment' => $student->deferments()->where('status', 'approved')->exists(),
        ];

        return view('sip.dashboard', compact('student', 'stats'));
    }

    /**
     * Show change password form (for first-time login)
     */
    public function showChangePasswordForm()
    {
        $student = $this->getStudent();
        $user = Auth::user();
        
        // If password already changed, redirect to dashboard
        if (!is_null($user->password_changed_at)) {
            return redirect()->route('sip.dashboard');
        }
        
        return view('sip.change-password', compact('student'));
    }

    /**
     * Process password change
     */
    public function changePassword(Request $request)
    {
        $student = $this->getStudent();
        $user = Auth::user();
        
        // If password already changed, redirect to dashboard
        if (!is_null($user->password_changed_at)) {
            return redirect()->route('sip.dashboard');
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Please enter your current password.',
            'new_password.required' => 'Please enter a new password.',
            'new_password.min' => 'Password must be at least 8 characters.',
            'new_password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->withInput();
        }

        // Update password and PIN
        $user->password = Hash::make($request->new_password);
        $user->pin = $request->new_password; // Update PIN to match new password
        $user->password_changed_at = now();
        $user->save();

        // Log activity
        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'password_changed',
            'model_type' => \App\Models\User::class,
            'model_id' => $user->id,
            'system_source' => 'SIP',
            'description' => 'Student changed password on first login',
        ]);

        return redirect()->route('sip.dashboard')
            ->with('success', 'Password changed successfully. You can now access your dashboard.');
    }

    /**
     * Student Profile
     */
    public function profile()
    {
        $student = $this->getStudent();
        $student->load(['program', 'department']);

        return view('sip.profile', compact('student'));
    }

    /**
     * Academic Records
     */
    public function academicRecords()
    {
        $student = $this->getStudent();
        $records = $student->academicRecords()
            ->where('is_approved', true)
            ->orderBy('academic_year', 'desc')
            ->orderBy('semester', 'desc')
            ->get();

        return view('sip.academic-records', compact('student', 'records'));
    }

    /**
     * Downloads
     */
    public function downloads()
    {
        $student = $this->getStudent();
        $downloads = $student->downloads()
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('document_type');

        // Static medical examination form (same for all students)
        $medicalFormPath = public_path('STUDENTS-MEDICAL-EXAMINATION-FORM.pdf');
        $medicalFormAvailable = file_exists($medicalFormPath);
        $medicalFormUrl = $medicalFormAvailable ? asset('STUDENTS-MEDICAL-EXAMINATION-FORM.pdf') : null;

        $sharedDocuments = SipDocument::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('sip.downloads', compact('student', 'downloads', 'medicalFormAvailable', 'medicalFormUrl', 'sharedDocuments'));
    }

    /**
     * Download a shared document uploaded by admin (prospectus, etc.).
     */
    public function downloadSharedDocument(SipDocument $sip_document)
    {
        $this->getStudent();

        if (!$sip_document->is_active) {
            abort(404, 'Document not found.');
        }

        if (!Storage::disk('public')->exists($sip_document->file_path)) {
            abort(404, 'File not found.');
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'shared_document_downloaded',
            'model_type' => SipDocument::class,
            'model_id' => $sip_document->id,
            'system_source' => 'SIP',
            'description' => "Downloaded shared document: {$sip_document->name}",
        ]);

        return Storage::disk('public')->download(
            $sip_document->file_path,
            $sip_document->original_filename
        );
    }

    /**
     * Download document or view admission form
     */
    public function downloadDocument(Download $download)
    {
        $student = $this->getStudent();

        if ($download->student_id !== $student->id) {
            abort(403, 'Unauthorized access.');
        }

        // Handle admission form as HTML/PDF view
        if ($download->document_type === 'admission_form' && $download->file_path === 'html') {
            return $this->viewAdmissionForm($download);
        }

        // Handle regular file downloads
        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'document_downloaded',
            'model_type' => Download::class,
            'model_id' => $download->id,
            'system_source' => 'SIP',
            'description' => "Downloaded {$download->document_type}: {$download->file_name}",
        ]);

        $filePath = storage_path('app/' . $download->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $download->file_name);
    }

    /**
     * View admission form as HTML
     */
    public function viewAdmissionForm(Download $download)
    {
        $student = $this->getStudent();

        if ($download->student_id !== $student->id) {
            abort(403, 'Unauthorized access.');
        }

        $formData = $student->admissionFormData;
        if (!$formData) {
            abort(404, 'Admission form data not found.');
        }

        $admissionFormService = app(\App\Services\AdmissionFormService::class);
        $data = $admissionFormService->getAdmissionFormData($student, $formData);

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'admission_form_viewed',
            'model_type' => Download::class,
            'model_id' => $download->id,
            'system_source' => 'SIP',
            'description' => "Viewed admission form",
        ]);

        return view('sip.admission-form', compact('student', 'data', 'formData', 'download'));
    }

    /**
     * Accept admission offer (required before PDF download)
     */
    public function acceptAdmissionOffer(Download $download)
    {
        $student = $this->getStudent();

        if ($download->student_id !== $student->id) {
            abort(403, 'Unauthorized access.');
        }

        if ($download->document_type !== 'admission_form') {
            abort(404, 'Admission form not found.');
        }

        $formData = $student->admissionFormData;
        if (!$formData) {
            abort(404, 'Admission form data not found.');
        }

        if (!$formData->isOfferAccepted()) {
            $formData->update([
                'offer_accepted_at' => now(),
            ]);

            $this->activityLogService->log([
                'user_id' => Auth::id(),
                'role' => 'student',
                'action' => 'admission_offer_accepted',
                'model_type' => Download::class,
                'model_id' => $download->id,
                'system_source' => 'SIP',
                'description' => 'Accepted admission offer',
            ]);
        }

        return redirect()
            ->route('sip.downloads.file', $download)
            ->with('status', 'You have accepted the admission offer. You can now download the PDF.');
    }

    /**
     * Download admission form as PDF
     */
    public function downloadAdmissionFormPdf(Download $download)
    {
        $student = $this->getStudent();

        if ($download->student_id !== $student->id) {
            abort(403, 'Unauthorized access.');
        }

        $formData = $student->admissionFormData;
        if (!$formData) {
            abort(404, 'Admission form data not found.');
        }

        if (!$formData->isOfferAccepted()) {
            return redirect()
                ->route('sip.downloads.file', $download)
                ->with('error', 'Please accept the admission offer before downloading the PDF.');
        }

        $admissionFormService = app(\App\Services\AdmissionFormService::class);
        $data = $admissionFormService->getAdmissionFormData($student, $formData);

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'admission_form_downloaded_pdf',
            'model_type' => Download::class,
            'model_id' => $download->id,
            'system_source' => 'SIP',
            'description' => "Downloaded admission form as PDF",
        ]);

        // Pass isPdf flag to exclude Font Awesome and icons
        $pdf = Pdf::loadView('sip.admission-form', compact('student', 'data', 'formData', 'download') + ['isPdf' => true])
            ->setPaper('a4', 'portrait')
            ->setOption('enable-remote', false);

        $fileName = 'Admission_Form_' . $student->student_id . '.pdf';

        return $pdf->download($fileName);
    }
}

