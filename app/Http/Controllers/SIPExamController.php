<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\ExamPin;
use App\Services\ActivityLogService;

class SIPExamController extends Controller
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
            abort(403, 'SIP account not found.');
        }

        return $student;
    }

    /**
     * Generate Exam PIN
     */
    public function generateExamPin(Request $request)
    {
        $student = $this->getStudent();

        // Check eligibility
        if (!$student->canGenerateExamPin()) {
            $balance = $student->getTotalBalance();
            return redirect()->route('sip.dashboard')
                ->with('error', "You must pay all fees (100%) to generate exam PIN. Current balance: {$balance}");
        }

        // Check if student is deferred
        if ($student->isDeferred()) {
            return redirect()->route('sip.dashboard')
                ->with('error', 'You cannot generate exam PIN while your admission is deferred.');
        }

        $request->validate([
            'semester' => 'required|string',
            'academic_year' => 'required|string',
        ]);

        // Check if PIN already exists for this semester
        $existingPin = ExamPin::where('student_id', $student->id)
            ->where('semester', $request->semester)
            ->where('academic_year', $request->academic_year)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingPin) {
            return redirect()->route('sip.exam.pins')
                ->with('info', 'You already have a valid exam PIN for this semester.');
        }

        // Generate new PIN
        $pin = ExamPin::create([
            'student_id' => $student->id,
            'pin' => ExamPin::generateUniquePin(),
            'semester' => $request->semester,
            'academic_year' => $request->academic_year,
            'expires_at' => now()->addMonths(6), // PIN valid for 6 months
        ]);

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'exam_pin_generated',
            'model_type' => ExamPin::class,
            'model_id' => $pin->id,
            'system_source' => 'SIP',
            'description' => "Exam PIN generated for {$request->semester} {$request->academic_year}",
        ]);

        return redirect()->route('sip.exam.pins')
            ->with('success', 'Exam PIN generated successfully. Please save it securely.');
    }

    /**
     * View Exam PINs
     */
    public function viewExamPins()
    {
        $student = $this->getStudent();
        $pins = $student->examPins()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sip.exam.pins', compact('student', 'pins'));
    }
}

