<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BankInvoicePaymentService
{
    protected ERPIntegrationService $erpService;
    protected ActivityLogService $activityLogService;

    public function __construct(ERPIntegrationService $erpService, ActivityLogService $activityLogService)
    {
        $this->erpService = $erpService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Find a student by public student_id string.
     */
    public function findStudent(string $studentId): ?Student
    {
        return Student::with(['user', 'program'])
            ->where('student_id', $studentId)
            ->first();
    }

    /**
     * Find an invoice by invoice_number or erp_invoice_id.
     */
    public function findInvoice(?string $invoiceNumber = null, ?string $erpInvoiceId = null): ?Invoice
    {
        if (!$invoiceNumber && !$erpInvoiceId) {
            return null;
        }

        $query = Invoice::with(['student.user', 'student.program']);

        if ($invoiceNumber && $erpInvoiceId) {
            $query->where(function ($q) use ($invoiceNumber, $erpInvoiceId) {
                $q->where('invoice_number', $invoiceNumber)
                    ->orWhere('erp_invoice_id', $erpInvoiceId);
            });
        } elseif ($invoiceNumber) {
            $query->where(function ($q) use ($invoiceNumber) {
                $q->where('invoice_number', $invoiceNumber)
                    ->orWhere('erp_invoice_id', $invoiceNumber);
            });
        } else {
            $query->where('erp_invoice_id', $erpInvoiceId);
        }

        return $query->first();
    }

    /**
     * Outstanding (payable) invoices for a student.
     */
    public function outstandingInvoices(Student $student)
    {
        return Invoice::where('student_id', $student->id)
            ->whereIn('status', ['pending', 'partial'])
            ->where('balance', '>', 0)
            ->orderByDesc('issued_date')
            ->orderByDesc('id')
            ->get();
    }

    public function formatInvoice(Invoice $invoice): array
    {
        $student = $invoice->student;

        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'erp_invoice_id' => $invoice->erp_invoice_id,
            'invoice_type' => $invoice->invoice_type,
            'academic_year' => $invoice->academic_year,
            'semester' => $invoice->semester,
            'total_amount' => (float) $invoice->total_amount,
            'paid_amount' => (float) $invoice->paid_amount,
            'balance' => (float) $invoice->balance,
            'status' => $invoice->status,
            'due_date' => optional($invoice->due_date)->format('Y-m-d'),
            'issued_date' => optional($invoice->issued_date)->format('Y-m-d'),
            'currency' => 'GHS',
            'student' => $student ? [
                'student_id' => $student->student_id,
                'name' => optional($student->user)->name,
                'email' => optional($student->user)->email,
                'program' => optional($student->program)->name,
            ] : null,
        ];
    }

    public function formatStudent(Student $student): array
    {
        return [
            'student_id' => $student->student_id,
            'name' => optional($student->user)->name,
            'email' => optional($student->user)->email,
            'program' => optional($student->program)->name,
            'total_balance' => (float) $student->getTotalBalance(),
        ];
    }

    /**
     * Record a bank counter payment against an invoice and update status.
     *
     * Idempotent on bank_transaction_id (stored as payment_reference).
     *
     * @return array{payment: Payment, invoice: Invoice, already_processed: bool, erp_synced: bool}
     */
    public function recordPayment(array $data, ?User $bankUser = null): array
    {
        $invoiceNumber = $data['invoice_number'] ?? null;
        $erpInvoiceId = $data['erp_invoice_id'] ?? null;
        $studentId = $data['student_id'] ?? null;
        $amount = round((float) $data['amount'], 2);
        $bankTxnId = trim((string) $data['bank_transaction_id']);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Amount must be greater than zero.'],
            ]);
        }

        $paymentReference = $this->buildPaymentReference($bankTxnId);

        // Idempotency: same bank transaction already applied
        $existing = Payment::where('payment_reference', $paymentReference)->first();
        if ($existing) {
            $existing->load(['invoice.student.user', 'invoice.student.program']);
            return [
                'payment' => $existing,
                'invoice' => $existing->invoice,
                'already_processed' => true,
                'erp_synced' => $existing->erp_status === 'synced',
            ];
        }

        $invoice = $this->findInvoice($invoiceNumber, $erpInvoiceId);
        if (!$invoice) {
            throw ValidationException::withMessages([
                'invoice_number' => ['Invoice not found.'],
            ]);
        }

        if ($invoice->status === 'cancelled') {
            throw ValidationException::withMessages([
                'invoice_number' => ['This invoice has been cancelled and cannot accept payments.'],
            ]);
        }

        if ((float) $invoice->balance <= 0 || $invoice->status === 'paid') {
            throw ValidationException::withMessages([
                'invoice_number' => ['This invoice is already fully paid.'],
            ]);
        }

        if ($studentId) {
            $student = $invoice->student;
            if (!$student || strcasecmp((string) $student->student_id, (string) $studentId) !== 0) {
                throw ValidationException::withMessages([
                    'student_id' => ['Student ID does not match this invoice.'],
                ]);
            }
        }

        // Allow tiny float tolerance; reject clear overpayments
        if ($amount > ((float) $invoice->balance + 0.01)) {
            throw ValidationException::withMessages([
                'amount' => [
                    sprintf(
                        'Amount (%.2f) exceeds outstanding balance (%.2f).',
                        $amount,
                        (float) $invoice->balance
                    ),
                ],
            ]);
        }

        return DB::transaction(function () use ($invoice, $amount, $paymentReference, $bankTxnId, $data, $bankUser) {
            $invoice = Invoice::where('id', $invoice->id)->lockForUpdate()->first();

            if ((float) $invoice->balance <= 0 || $invoice->status === 'paid') {
                throw ValidationException::withMessages([
                    'invoice_number' => ['This invoice is already fully paid.'],
                ]);
            }

            if ($amount > ((float) $invoice->balance + 0.01)) {
                throw ValidationException::withMessages([
                    'amount' => [
                        sprintf(
                            'Amount (%.2f) exceeds outstanding balance (%.2f).',
                            $amount,
                            (float) $invoice->balance
                        ),
                    ],
                ]);
            }

            // Race: another request with same txn may have inserted
            $existing = Payment::where('payment_reference', $paymentReference)->first();
            if ($existing) {
                $existing->load(['invoice.student.user', 'invoice.student.program']);
                return [
                    'payment' => $existing,
                    'invoice' => $existing->invoice,
                    'already_processed' => true,
                    'erp_synced' => $existing->erp_status === 'synced',
                ];
            }

            $payment = Payment::create([
                'student_id' => $invoice->student_id,
                'invoice_id' => $invoice->id,
                'payment_reference' => $paymentReference,
                'payment_method' => 'bank',
                'amount' => $amount,
                'status' => 'completed',
                'transaction_id' => $bankTxnId,
                'erp_status' => 'pending',
                'payment_details' => [
                    'submitted_via' => 'bank_api',
                    'bank_transaction_id' => $bankTxnId,
                    'paid_at' => $data['paid_at'] ?? now()->toIso8601String(),
                    'teller_id' => $data['teller_id'] ?? null,
                    'branch_code' => $data['branch_code'] ?? null,
                    'narration' => $data['narration'] ?? null,
                    'bank_user_id' => $bankUser ? $bankUser->id : null,
                    'bank_name' => $bankUser->bank_name ?? null,
                    'bank_branch' => $bankUser->branch ?? ($data['branch_code'] ?? null),
                ],
            ]);

            $erpSynced = $this->trySyncToErp($payment, $invoice);

            $invoice->updateBalance();
            $invoice->refresh()->load(['student.user', 'student.program']);

            $this->activityLogService->log([
                'user_id' => $bankUser ? $bankUser->id : null,
                'role' => 'bank',
                'action' => 'bank_invoice_payment',
                'model_type' => Payment::class,
                'model_id' => $payment->id,
                'system_source' => 'BANK_API',
                'description' => sprintf(
                    'Bank API payment of GHS %.2f for invoice %s (txn %s). Invoice status: %s.',
                    $amount,
                    $invoice->invoice_number,
                    $bankTxnId,
                    $invoice->status
                ),
            ]);

            return [
                'payment' => $payment->fresh(),
                'invoice' => $invoice,
                'already_processed' => false,
                'erp_synced' => $erpSynced,
            ];
        });
    }

    protected function buildPaymentReference(string $bankTxnId): string
    {
        $clean = preg_replace('/[^A-Za-z0-9\-_]/', '', $bankTxnId);
        $clean = strtoupper(substr($clean ?: 'TXN', 0, 60));

        return 'BANKTXN-' . $clean;
    }

    protected function trySyncToErp(Payment $payment, Invoice $invoice): bool
    {
        $student = $invoice->student;
        if (!$invoice->erp_invoice_id || !$student || !$student->erp_student_name) {
            return false;
        }

        try {
            $result = $this->erpService->submitPaymentEntry(
                $invoice->erp_invoice_id,
                (float) $payment->amount,
                $payment->payment_reference
            );

            if (!empty($result['erp_payment_id'])) {
                $payment->update([
                    'erp_payment_id' => $result['erp_payment_id'],
                    'erp_status' => 'synced',
                    'erp_synced_at' => now(),
                    'erp_response' => $result,
                ]);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Bank API ERP payment sync failed', [
                'payment_id' => $payment->id,
                'invoice' => $invoice->erp_invoice_id,
                'error' => $e->getMessage(),
            ]);
            $payment->update([
                'erp_status' => 'failed',
                'erp_response' => ['error' => $e->getMessage()],
            ]);
        }

        return false;
    }
}
