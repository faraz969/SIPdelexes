<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Deferment;
use App\Services\ERPIntegrationService;
use App\Services\ActivityLogService;

class SIPDefermentController extends Controller
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
     * Show deferment request form
     */
    public function showDefermentForm()
    {
        $student = $this->getStudent();

        // Check if there's an active deferment
        $activeDeferment = $student->deferments()
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        return view('sip.deferment.form', compact('student', 'activeDeferment'));
    }

    /**
     * Submit deferment request
     */
    public function submitDeferment(Request $request)
    {
        $student = $this->getStudent();

        // Check if there's an active deferment
        $activeDeferment = $student->deferments()
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($activeDeferment) {
            return back()->with('error', 'You already have an active deferment request.');
        }

        $request->validate([
            'reason' => 'required|string|min:20|max:1000',
            'defer_from' => 'required|date|after_or_equal:today',
        ]);

        $deferment = Deferment::create([
            'student_id' => $student->id,
            'reason' => $request->reason,
            'defer_from' => $request->defer_from,
            'defer_to' => $request->defer_to ?? null,
            'status' => 'pending',
        ]);

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'deferment_requested',
            'model_type' => Deferment::class,
            'model_id' => $deferment->id,
            'system_source' => 'SIP',
            'description' => 'Deferment request submitted',
        ]);

        return redirect()->route('sip.deferment.status')
            ->with('success', 'Deferment request submitted successfully. Waiting for registrar approval.');
    }

    /**
     * View deferment status
     */
    public function viewDefermentStatus()
    {
        $student = $this->getStudent();
        $deferments = $student->deferments()
            ->with('approver')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sip.deferment.status', compact('student', 'deferments'));
    }
}

