<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\Student;
use App\Models\TranscriptRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TranscriptRequestService
{
    /**
     * Create a transcript request and fee invoice for the student.
     */
    public function createRequest(Student $student, ?string $purpose = null, int $copies = 1): TranscriptRequest
    {
        $open = TranscriptRequest::where('student_id', $student->id)
            ->whereIn('status', [
                TranscriptRequest::STATUS_PENDING_PAYMENT,
                TranscriptRequest::STATUS_PENDING_APPROVAL,
            ])
            ->exists();

        if ($open) {
            throw ValidationException::withMessages([
                'request' => 'You already have an open transcript request. Complete payment or wait for Registrar approval.',
            ]);
        }

        $fee = SiteSetting::transcriptFeeAmount();
        if ($fee < 0) {
            $fee = 0.0;
        }

        return DB::transaction(function () use ($student, $purpose, $copies, $fee) {
            $invoice = null;
            $status = TranscriptRequest::STATUS_PENDING_APPROVAL;
            $paidAt = now();

            if ($fee > 0) {
                $invoice = Invoice::create([
                    'student_id' => $student->id,
                    'invoice_number' => 'TRN-' . strtoupper(uniqid()),
                    'invoice_type' => 'transcript',
                    'academic_year' => SiteSetting::currentAcademicYear(),
                    'semester' => null,
                    'total_amount' => $fee,
                    'paid_amount' => 0,
                    'balance' => $fee,
                    'status' => 'pending',
                    'due_date' => now()->addDays(14),
                    'issued_date' => now(),
                    'line_items' => [
                        [
                            'description' => 'Official Academic Transcript Fee',
                            'amount' => $fee,
                            'copies' => $copies,
                        ],
                    ],
                ]);
                $status = TranscriptRequest::STATUS_PENDING_PAYMENT;
                $paidAt = null;
            }

            return TranscriptRequest::create([
                'student_id' => $student->id,
                'invoice_id' => $invoice ? $invoice->id : null,
                'amount' => $fee,
                'status' => $status,
                'purpose' => $purpose,
                'copies' => max(1, $copies),
                'paid_at' => $paidAt,
            ]);
        });
    }

    /**
     * When a transcript invoice is fully paid, move request to registrar queue.
     */
    public function markPaidFromInvoice(Invoice $invoice): void
    {
        if ($invoice->invoice_type !== 'transcript') {
            return;
        }

        if ($invoice->status !== 'paid' && (float) $invoice->balance > 0) {
            return;
        }

        $request = TranscriptRequest::where('invoice_id', $invoice->id)
            ->where('status', TranscriptRequest::STATUS_PENDING_PAYMENT)
            ->first();

        if (!$request) {
            return;
        }

        $payment = Payment::where('invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->first();

        $request->update([
            'status' => TranscriptRequest::STATUS_PENDING_APPROVAL,
            'payment_id' => $payment ? $payment->id : null,
            'paid_at' => now(),
        ]);
    }

    /**
     * Sync payment status if student returns before webhook/callback finishes.
     */
    public function syncPaymentStatus(TranscriptRequest $request): TranscriptRequest
    {
        if (!$request->isPendingPayment() || !$request->invoice_id) {
            return $request;
        }

        $invoice = $request->invoice()->first();
        if (!$invoice) {
            return $request;
        }

        $invoice->updateBalance();
        $this->markPaidFromInvoice($invoice->fresh());

        return $request->fresh();
    }

    public function approve(TranscriptRequest $request, ?string $comments = null): TranscriptRequest
    {
        if (!$request->isPendingApproval()) {
            throw ValidationException::withMessages([
                'status' => 'Only paid requests awaiting approval can be approved.',
            ]);
        }

        $request->update([
            'status' => TranscriptRequest::STATUS_APPROVED,
            'registrar_comments' => $comments,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $request->fresh();
    }

    public function reject(TranscriptRequest $request, string $comments): TranscriptRequest
    {
        if (!$request->isPendingApproval()) {
            throw ValidationException::withMessages([
                'status' => 'Only paid requests awaiting approval can be rejected.',
            ]);
        }

        $comments = trim($comments);
        if ($comments === '') {
            throw ValidationException::withMessages([
                'comments' => 'Rejection comments are required.',
            ]);
        }

        $request->update([
            'status' => TranscriptRequest::STATUS_REJECTED,
            'registrar_comments' => $comments,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $request->fresh();
    }
}
