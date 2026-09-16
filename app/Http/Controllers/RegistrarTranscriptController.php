<?php

namespace App\Http\Controllers;

use App\Models\TranscriptRequest;
use App\Services\ActivityLogService;
use App\Services\TranscriptPdfService;
use App\Services\TranscriptRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RegistrarTranscriptController extends Controller
{
    protected TranscriptRequestService $requestService;
    protected TranscriptPdfService $pdfService;
    protected ActivityLogService $activityLogService;

    public function __construct(
        TranscriptRequestService $requestService,
        TranscriptPdfService $pdfService,
        ActivityLogService $activityLogService
    ) {
        $this->requestService = $requestService;
        $this->pdfService = $pdfService;
        $this->activityLogService = $activityLogService;
    }

    public function index()
    {
        $pending = TranscriptRequest::with(['student.user', 'student.program', 'invoice'])
            ->where('status', TranscriptRequest::STATUS_PENDING_APPROVAL)
            ->orderBy('paid_at')
            ->orderBy('created_at')
            ->get();

        $all = TranscriptRequest::with(['student.user', 'student.program', 'approver', 'invoice'])
            ->orderByDesc('id')
            ->paginate(20);

        return view('registrar.transcripts.index', compact('pending', 'all'));
    }

    public function approve(Request $request, TranscriptRequest $transcriptRequest)
    {
        $request->validate(['comments' => 'nullable|string|max:1000']);

        try {
            $this->requestService->approve($transcriptRequest, $request->input('comments'));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'registrar',
            'action' => 'transcript_approved',
            'model_type' => TranscriptRequest::class,
            'model_id' => $transcriptRequest->id,
            'system_source' => 'SIP',
            'description' => 'Registrar approved transcript request',
        ]);

        return redirect()->route('registrar.transcripts.index')
            ->with('success', 'Transcript request approved. Student can now view it in SIP.');
    }

    public function reject(Request $request, TranscriptRequest $transcriptRequest)
    {
        $request->validate(['comments' => 'required|string|max:1000']);

        try {
            $this->requestService->reject($transcriptRequest, $request->input('comments'));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'registrar',
            'action' => 'transcript_rejected',
            'model_type' => TranscriptRequest::class,
            'model_id' => $transcriptRequest->id,
            'system_source' => 'SIP',
            'description' => 'Registrar rejected transcript request',
        ]);

        return redirect()->route('registrar.transcripts.index')
            ->with('success', 'Transcript request rejected.');
    }

    /**
     * Registrar downloads official PDF for a student request.
     */
    public function download(TranscriptRequest $transcriptRequest)
    {
        if (!in_array($transcriptRequest->status, [
            TranscriptRequest::STATUS_APPROVED,
            TranscriptRequest::STATUS_PENDING_APPROVAL,
        ], true)) {
            return back()->with('error', 'Transcript PDF can only be downloaded for paid or approved requests.');
        }

        $student = $transcriptRequest->student()->with(['user', 'program', 'application.admissionForm'])->first();
        $response = $this->pdfService->downloadPdf($student);

        if (!$response) {
            return back()->with('error', 'No published results available to generate this transcript.');
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'registrar',
            'action' => 'transcript_downloaded',
            'model_type' => TranscriptRequest::class,
            'model_id' => $transcriptRequest->id,
            'system_source' => 'SIP',
            'description' => 'Registrar downloaded official transcript PDF for student ' . $student->student_id,
        ]);

        return $response;
    }
}
