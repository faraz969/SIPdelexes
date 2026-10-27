<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BankInvoicePaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BankInvoiceApiController extends Controller
{
    protected BankInvoicePaymentService $paymentService;

    public function __construct(BankInvoicePaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Look up a single invoice by invoice_number and optional student_id.
     *
     * GET /api/bank/invoices/lookup?invoice_number=...&student_id=...
     */
    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required_without:erp_invoice_id|nullable|string|max:100',
            'erp_invoice_id' => 'required_without:invoice_number|nullable|string|max:100',
            'student_id' => 'nullable|string|max:50',
        ]);

        $invoice = $this->paymentService->findInvoice(
            $validated['invoice_number'] ?? null,
            $validated['erp_invoice_id'] ?? null
        );

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found.',
            ], 404);
        }

        if (!empty($validated['student_id'])) {
            $student = $invoice->student;
            if (!$student || strcasecmp((string) $student->student_id, (string) $validated['student_id']) !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID does not match this invoice.',
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $this->paymentService->formatInvoice($invoice),
        ]);
    }

    /**
     * List outstanding invoices for a student.
     *
     * GET /api/bank/students/{student_id}/invoices
     */
    public function studentInvoices(string $studentId)
    {
        $student = $this->paymentService->findStudent($studentId);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 404);
        }

        $invoices = $this->paymentService->outstandingInvoices($student);

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $this->paymentService->formatStudent($student),
                'invoices' => $invoices->map(function ($invoice) {
                    return $this->paymentService->formatInvoice($invoice);
                })->values(),
            ],
        ]);
    }

    /**
     * Record a bank payment and update invoice status.
     *
     * POST /api/bank/invoices/pay
     */
    public function pay(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required_without:erp_invoice_id|nullable|string|max:100',
            'erp_invoice_id' => 'required_without:invoice_number|nullable|string|max:100',
            'student_id' => 'nullable|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'bank_transaction_id' => 'required|string|max:100',
            'paid_at' => 'nullable|date',
            'teller_id' => 'nullable|string|max:100',
            'branch_code' => 'nullable|string|max:100',
            'narration' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->paymentService->recordPayment($validated, $request->user());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Bank invoice payment API failed', [
                'error' => $e->getMessage(),
                'payload' => $validated,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to process payment. Please try again or contact support.',
            ], 500);
        }

        $payment = $result['payment'];
        $invoice = $result['invoice'];
        $statusCode = $result['already_processed'] ? 200 : 201;

        return response()->json([
            'success' => true,
            'message' => $result['already_processed']
                ? 'Payment already recorded for this bank transaction.'
                : 'Payment recorded successfully. Invoice status updated.',
            'data' => [
                'already_processed' => $result['already_processed'],
                'erp_synced' => $result['erp_synced'],
                'payment' => [
                    'payment_reference' => $payment->payment_reference,
                    'bank_transaction_id' => $payment->transaction_id,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'created_at' => optional($payment->created_at)->toIso8601String(),
                ],
                'invoice' => $this->paymentService->formatInvoice($invoice),
            ],
        ], $statusCode);
    }

    /**
     * Get payment status by bank transaction id.
     *
     * GET /api/bank/payments/{bank_transaction_id}
     */
    public function paymentStatus(string $bankTransactionId)
    {
        $reference = 'BANKTXN-' . strtoupper(preg_replace('/[^A-Za-z0-9\-_]/', '', $bankTransactionId));
        $payment = \App\Models\Payment::with(['invoice.student.user', 'invoice.student.program'])
            ->where(function ($q) use ($reference, $bankTransactionId) {
                $q->where('payment_reference', $reference)
                    ->orWhere('transaction_id', $bankTransactionId);
            })
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for this bank transaction.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'payment' => [
                    'payment_reference' => $payment->payment_reference,
                    'bank_transaction_id' => $payment->transaction_id,
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'erp_status' => $payment->erp_status,
                    'created_at' => optional($payment->created_at)->toIso8601String(),
                ],
                'invoice' => $payment->invoice
                    ? $this->paymentService->formatInvoice($payment->invoice)
                    : null,
            ],
        ]);
    }
}
