<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ERPIntegrationService;
use App\Services\ERPInvoiceSyncService;
use App\Services\ActivityLogService;
use Illuminate\Support\Str;

class SIPPaymentController extends Controller
{
    protected $erpService;
    protected $invoiceSyncService;
    protected $activityLogService;

    public function __construct(
        ERPIntegrationService $erpService,
        ERPInvoiceSyncService $invoiceSyncService,
        ActivityLogService $activityLogService
    ) {
        $this->middleware('auth');
        $this->erpService = $erpService;
        $this->invoiceSyncService = $invoiceSyncService;
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

        return view('sip.payments.pay', compact('student', 'invoice'));
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, Invoice $invoice)
    {
        $student = $this->getStudent();

        if ($invoice->student_id !== $student->id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance,
            'payment_method' => 'required|in:card,momo,bank',
        ]);

        $paymentReference = 'PAY-' . strtoupper(Str::random(12));
        $amount = (float) $request->amount;

        $payment = Payment::create([
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => $paymentReference,
            'payment_method' => $request->payment_method,
            'amount' => $amount,
            'status' => 'processing',
            'erp_status' => 'pending',
        ]);

        $erpSynced = false;
        if ($invoice->erp_invoice_id && $student->erp_student_name) {
            try {
                $result = $this->erpService->submitPaymentEntry(
                    $invoice->erp_invoice_id,
                    $amount,
                    $paymentReference
                );
                if (!empty($result['erp_payment_id'])) {
                    $payment->update([
                        'erp_payment_id' => $result['erp_payment_id'],
                        'erp_status' => 'synced',
                        'erp_synced_at' => now(),
                        'status' => 'completed',
                    ]);
                    $invoice->updateBalance();
                    $erpSynced = true;
                }
            } catch (\Exception $e) {
                \Log::error('ERP payment submission failed', [
                    'payment_id' => $payment->id,
                    'invoice' => $invoice->erp_invoice_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        \Log::info('Payment Initiated', [
            'payment_id' => $payment->id,
            'payment_reference' => $paymentReference,
            'student_id' => $student->student_id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'erp_synced' => $erpSynced,
        ]);

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'payment_initiated',
            'model_type' => Payment::class,
            'model_id' => $payment->id,
            'system_source' => 'SIP',
            'description' => $erpSynced
                ? "Payment of GHS {$amount} submitted to ERP for invoice {$invoice->invoice_number}."
                : "Payment of GHS {$amount} initiated for invoice {$invoice->invoice_number}. " .
                  ($invoice->erp_invoice_id ? 'ERP sync failed - check logs.' : 'Awaiting admin confirmation.'),
        ]);

        if ($erpSynced) {
            return redirect()->route('sip.payments.history')
                ->with('success', 'Payment of GHS ' . number_format($amount, 2) . ' submitted successfully.');
        }

        return redirect()->route('sip.payments.history')
            ->with('info', 'Payment initiated. Your payment is being processed. An admin will confirm it shortly.');
    }
}

