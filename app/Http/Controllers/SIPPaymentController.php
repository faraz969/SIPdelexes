<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ERPIntegrationService;
use App\Services\ERPInvoiceSyncService;
use App\Services\ActivityLogService;
use App\Services\PaystackService;
use Illuminate\Support\Str;

class SIPPaymentController extends Controller
{
    protected $erpService;
    protected $invoiceSyncService;
    protected $activityLogService;
    protected $paystackService;

    public function __construct(
        ERPIntegrationService $erpService,
        ERPInvoiceSyncService $invoiceSyncService,
        ActivityLogService $activityLogService,
        PaystackService $paystackService
    ) {
        $this->middleware('auth');
        $this->erpService = $erpService;
        $this->invoiceSyncService = $invoiceSyncService;
        $this->activityLogService = $activityLogService;
        $this->paystackService = $paystackService;
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
     * View invoices (syncs from ERP first if student has erp_student_name)
     */
    public function invoices()
    {
        $student = $this->getStudent();
        if ($student->erp_student_name) {
            try {
                $this->invoiceSyncService->syncForStudent($student);
            } catch (\Exception $e) {
                \Log::warning('ERP invoice sync failed on view', [
                    'student_id' => $student->student_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        $invoices = $student->invoices()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sip.payments.invoices', compact('student', 'invoices'));
    }

    /**
     * View payment history
     */
    public function paymentHistory()
    {
        $student = $this->getStudent();
        $payments = $student->payments()
            ->with('invoice')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('sip.payments.history', compact('student', 'payments'));
    }

    /**
     * Show payment form
     */
    public function showPaymentForm(Invoice $invoice)
    {
        $student = $this->getStudent();

        if ($invoice->student_id !== $student->id) {
            abort(403, 'Unauthorized access.');
        }

        if ($invoice->status === 'paid') {
            return redirect()->route('sip.payments.invoices')
                ->with('info', 'This invoice is already paid.');
        }

        $paystackConfigured = $this->paystackService->isConfigured();

        return view('sip.payments.pay', compact('student', 'invoice', 'paystackConfigured'));
    }

    /**
     * Start invoice payment via Paystack or bank payment slip upload.
     */
    public function processPayment(Request $request, Invoice $invoice)
    {
        $student = $this->getStudent();

        if ($invoice->student_id !== $student->id) {
            abort(403, 'Unauthorized access.');
        }

        if ($invoice->status === 'paid') {
            return redirect()->route('sip.payments.invoices')
                ->with('info', 'This invoice is already paid.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance,
            'payment_method' => 'required|in:paystack,bank',
            'bank_slip' => 'required_if:payment_method,bank|nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($request->payment_method === 'bank') {
            return $this->processBankSlipPayment($request, $invoice, $student);
        }

        return $this->processPaystackPayment($request, $invoice, $student);
    }

    /**
     * Store bank payment slip and email accounts for ERP update.
     */
    protected function processBankSlipPayment(Request $request, Invoice $invoice, Student $student)
    {
        $amount = round((float) $request->amount, 2);
        $paymentReference = 'BANK-' . strtoupper(Str::random(12));

        $slipPath = $request->file('bank_slip')->store('payment-slips/' . $student->id, 'public');

        $payment = Payment::create([
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => $paymentReference,
            'payment_method' => 'bank',
            'amount' => $amount,
            'status' => 'pending',
            'erp_status' => 'pending',
            'bank_slip_path' => $slipPath,
            'payment_details' => [
                'submitted_via' => 'sip_bank_slip',
                'original_filename' => $request->file('bank_slip')->getClientOriginalName(),
                'mime_type' => $request->file('bank_slip')->getMimeType(),
            ],
        ]);

        try {
            $this->emailBankSlipToAccounts($payment, $student, $invoice);
        } catch (\Exception $e) {
            Log::error('Failed to email bank payment slip to accounts', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('sip.payments.history')
                ->with('info', 'Your payment slip was uploaded, but the accounts email could not be sent. Reference: ' . $paymentReference . '. Please contact the finance office.');
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'bank_slip_submitted',
            'model_type' => Payment::class,
            'model_id' => $payment->id,
            'system_source' => 'SIP',
            'description' => "Bank payment slip of GHS {$amount} submitted for invoice {$invoice->invoice_number}.",
        ]);

        return redirect()->route('sip.payments.history')
            ->with('success', 'Payment slip submitted successfully. Accounts has been notified to update ERP. Reference: ' . $paymentReference);
    }

    /**
     * Email accounts department with bank slip attached.
     */
    protected function emailBankSlipToAccounts(Payment $payment, Student $student, Invoice $invoice): void
    {
        $accountsEmail = config('services.accounts.email');
        if (empty($accountsEmail)) {
            throw new \RuntimeException('Accounts email is not configured (ACCOUNTS_EMAIL).');
        }

        $student->loadMissing('user');
        $absoluteSlipPath = storage_path('app/public/' . ltrim($payment->bank_slip_path, '/'));
        $attachmentName = basename($payment->bank_slip_path);

        Mail::send('emails.bank-payment-slip', [
            'payment' => $payment,
            'student' => $student,
            'invoice' => $invoice,
        ], function ($message) use ($accountsEmail, $payment, $absoluteSlipPath, $attachmentName) {
            $message->to($accountsEmail)
                ->subject('SIP Bank Payment Slip - ' . $payment->payment_reference);

            if (file_exists($absoluteSlipPath)) {
                $message->attach($absoluteSlipPath, [
                    'as' => $attachmentName,
                ]);
            }
        });
    }

    /**
     * Start invoice payment via Paystack.
     */
    protected function processPaystackPayment(Request $request, Invoice $invoice, Student $student)
    {
        if (!$this->paystackService->isConfigured()) {
            return redirect()->route('sip.payments.pay', $invoice->id)
                ->with('error', 'Paystack is not configured. Please use bank payment slip upload or contact the finance office.');
        }

        $amount = round((float) $request->amount, 2);
        $paymentReference = 'PAY-' . strtoupper(Str::random(12));

        $payment = Payment::create([
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => $paymentReference,
            'payment_method' => 'paystack',
            'amount' => $amount,
            'status' => 'processing',
            'erp_status' => 'pending',
        ]);

        $studentEmail = $student->user->email ?? ($student->student_id . '@delexesuniversity.edu.gh');

        $result = $this->paystackService->initialize(
            $studentEmail,
            $amount,
            $paymentReference,
            route('sip.payments.paystack.callback'),
            [
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'student_id' => $student->student_id,
                'custom_fields' => [
                    [
                        'display_name' => 'Invoice',
                        'variable_name' => 'invoice_number',
                        'value' => $invoice->invoice_number,
                    ],
                    [
                        'display_name' => 'Student ID',
                        'variable_name' => 'student_id',
                        'value' => $student->student_id,
                    ],
                ],
            ]
        );

        if (!$result['success']) {
            $payment->update([
                'status' => 'failed',
                'payment_details' => ['error' => $result['message'] ?? 'Initialization failed'],
            ]);

            return redirect()->route('sip.payments.pay', $invoice->id)
                ->with('error', $result['message'] ?? 'Could not start Paystack payment.');
        }

        $payment->update([
            'payment_details' => [
                'paystack_reference' => $result['reference'] ?? $paymentReference,
                'access_code' => $result['access_code'] ?? null,
            ],
        ]);

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'payment_initiated',
            'model_type' => Payment::class,
            'model_id' => $payment->id,
            'system_source' => 'SIP',
            'description' => "Paystack payment of GHS {$amount} initiated for invoice {$invoice->invoice_number}.",
        ]);

        return redirect()->away($result['authorization_url']);
    }

    /**
     * Handle Paystack redirect after invoice payment.
     */
    public function paystackCallback(Request $request)
    {
        $student = $this->getStudent();
        $reference = $request->get('reference') ?? $request->get('trxref');

        if (!$reference) {
            return redirect()->route('sip.payments.invoices')
                ->with('error', 'Invalid Paystack payment response.');
        }

        $payment = Payment::where('payment_reference', $reference)
            ->where('student_id', $student->id)
            ->where('payment_method', 'paystack')
            ->with('invoice')
            ->first();

        if (!$payment) {
            return redirect()->route('sip.payments.invoices')
                ->with('error', 'Payment record not found.');
        }

        if ($payment->status === 'completed') {
            return redirect()->route('sip.payments.history')
                ->with('success', 'Payment of GHS ' . number_format($payment->amount, 2) . ' was already completed.');
        }

        $verification = $this->paystackService->verify($reference);

        if (!$verification['success']) {
            $payment->update([
                'status' => 'failed',
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'verification_error' => $verification['message'] ?? 'Verification failed',
                ]),
            ]);

            return redirect()->route('sip.payments.pay', $payment->invoice_id)
                ->with('error', $verification['message'] ?? 'Payment could not be verified.');
        }

        $verifiedAmount = round((float) ($verification['amount_ghs'] ?? 0), 2);
        if ($verifiedAmount > 0 && abs($verifiedAmount - (float) $payment->amount) > 0.01) {
            \Log::warning('Paystack amount mismatch', [
                'payment_id' => $payment->id,
                'expected' => $payment->amount,
                'verified' => $verifiedAmount,
            ]);

            return redirect()->route('sip.payments.pay', $payment->invoice_id)
                ->with('error', 'Payment amount mismatch. Please contact support.');
        }

        $completed = $this->finalizePayment($payment, $verification['data'] ?? []);

        if ($completed['erp_synced']) {
            return redirect()->route('sip.payments.history')
                ->with('success', 'Payment of GHS ' . number_format($payment->amount, 2) . ' completed successfully.');
        }

        return redirect()->route('sip.payments.history')
            ->with('info', 'Payment received. ERP sync is pending — finance will confirm shortly.');
    }

    /**
     * Mark payment complete and sync to ERP when possible.
     */
    protected function finalizePayment(Payment $payment, array $paystackData = []): array
    {
        if ($payment->status === 'completed') {
            return ['erp_synced' => $payment->erp_status === 'synced'];
        }

        $invoice = $payment->invoice;
        $student = $payment->student;

        $payment->update([
            'status' => 'completed',
            'transaction_id' => isset($paystackData['id']) ? (string) $paystackData['id'] : null,
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'paystack' => $paystackData,
            ]),
        ]);

        $erpSynced = false;

        if ($invoice && $invoice->erp_invoice_id && $student && $student->erp_student_name) {
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
                    $erpSynced = true;
                }
            } catch (\Exception $e) {
                \Log::error('ERP payment submission failed after Paystack', [
                    'payment_id' => $payment->id,
                    'invoice' => $invoice->erp_invoice_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($invoice) {
            $invoice->updateBalance();
        }

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'payment_completed',
            'model_type' => Payment::class,
            'model_id' => $payment->id,
            'system_source' => 'SIP',
            'description' => $erpSynced
                ? "Paystack payment of GHS {$payment->amount} synced to ERP for invoice {$invoice->invoice_number}."
                : "Paystack payment of GHS {$payment->amount} completed for invoice {$invoice->invoice_number}.",
        ]);

        return ['erp_synced' => $erpSynced];
    }
}
