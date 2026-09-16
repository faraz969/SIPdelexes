<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\Student;
use App\Models\TranscriptRequest;
use App\Services\ActivityLogService;
use App\Services\TranscriptPdfService;
use App\Services\TranscriptRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SIPTranscriptController extends Controller
{
    protected TranscriptRequestService $requestService;
    protected TranscriptPdfService $pdfService;
    protected ActivityLogService $activityLogService;

    public function __construct(
        TranscriptRequestService $requestService,
        TranscriptPdfService $pdfService,
        ActivityLogService $activityLogService
    ) {
        $this->middleware('auth');
        $this->requestService = $requestService;
        $this->pdfService = $pdfService;
        $this->activityLogService = $activityLogService;
    }

    protected function getStudent(): Student
    {
        $student = Student::where('user_id', Auth::id())->first();
        if (!$student) {
            abort(403, 'SIP account not found.');
        }

        return $student;
    }

    public function index()
    {
        $student = $this->getStudent();
        $fee = SiteSetting::transcriptFeeAmount();
        $requests = TranscriptRequest::where('student_id', $student->id)
            ->with('invoice')
            ->orderByDesc('id')
            ->get();

        foreach ($requests as $request) {
            if ($request->isPendingPayment()) {
                $this->requestService->syncPaymentStatus($request);
            }
        }

        $requests = TranscriptRequest::where('student_id', $student->id)
            ->with('invoice')
            ->orderByDesc('id')
            ->get();

        $openRequest = $requests->first(function (TranscriptRequest $r) {
            return in_array($r->status, [
                TranscriptRequest::STATUS_PENDING_PAYMENT,
                TranscriptRequest::STATUS_PENDING_APPROVAL,
            ], true);
        });

        $latestApproved = $requests->first(function (TranscriptRequest $r) {
            return $r->isApproved();
        });

        return view('sip.transcript.index', compact(
            'student',
            'fee',
            'requests',
            'openRequest',
            'latestApproved'
        ));
    }

    public function store(Request $request)
    {
        $student = $this->getStudent();

        $validated = $request->validate([
            'purpose' => 'nullable|string|max:500',
            'copies' => 'nullable|integer|min:1|max:5',
        ]);

        try {
            $transcriptRequest = $this->requestService->createRequest(
                $student,
                $validated['purpose'] ?? null,
                (int) ($validated['copies'] ?? 1)
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'transcript_requested',
            'model_type' => TranscriptRequest::class,
            'model_id' => $transcriptRequest->id,
            'system_source' => 'SIP',
            'description' => 'Student requested official transcript',
        ]);

        if ($transcriptRequest->isPendingPayment() && $transcriptRequest->invoice_id) {
            return redirect()
                ->route('sip.payments.pay', $transcriptRequest->invoice_id)
                ->with('success', 'Transcript request created. Please pay the transcript fee to continue.');
        }

        return redirect()
            ->route('sip.transcript.index')
            ->with('success', 'Transcript request submitted to Registrar for approval.');
    }

    /**
     * View-only official transcript (no download for students).
     */
    public function view(TranscriptRequest $transcriptRequest)
    {
        $student = $this->getStudent();
        if ((int) $transcriptRequest->student_id !== (int) $student->id) {
            abort(403);
        }

        if (!$transcriptRequest->isApproved()) {
            return redirect()->route('sip.transcript.index')
                ->with('error', 'Your transcript is not yet approved by the Registrar.');
        }

        $payload = $this->pdfService->buildPayload($student);
        if (!$payload) {
            return redirect()->route('sip.transcript.index')
                ->with('error', 'No published results are available for your transcript.');
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'transcript_viewed',
            'model_type' => TranscriptRequest::class,
            'model_id' => $transcriptRequest->id,
            'system_source' => 'SIP',
            'description' => 'Student viewed approved official transcript',
        ]);

        return view('sip.transcript.view', $payload + [
            'transcriptRequest' => $transcriptRequest,
            'viewOnly' => true,
        ]);
    }
}
