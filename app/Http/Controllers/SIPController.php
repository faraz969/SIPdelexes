<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\StudentAcademicRecord;
use App\Models\CourseRegistration;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ExamPin;
use App\Models\Deferment;
use App\Models\Download;
use App\Services\ActivityLogService;

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

        return view('sip.downloads', compact('student', 'downloads'));
    }

    /**
     * Download document
     */
    public function downloadDocument(Download $download)
    {
        $student = $this->getStudent();

        if ($download->student_id !== $student->id) {
            abort(403, 'Unauthorized access.');
        }

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
}

