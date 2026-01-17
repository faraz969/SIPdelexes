<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ERPIntegrationService;
use App\Services\ActivityLogService;
use Illuminate\Support\Str;

class SIPPaymentController extends Controller
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
     * View invoices
     */
    public function invoices()
    {
        $student = $this->getStudent();
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

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
            'payment_reference' => 'PAY-' . strtoupper(Str::random(12)),
            'payment_method' => $request->payment_method,
            'amount' => $request->amount,
            'status' => 'processing',
        ]);

        // For now, set payment as processing (admin will manually process it)
        // In production, this would integrate with payment gateway and ERP
        $payment->update([
            'status' => 'processing',
            'erp_status' => 'pending',
        ]);

        \Log::info("Payment Initiated", [
            'payment_id' => $payment->id,
            'payment_reference' => $payment->payment_reference,
            'student_id' => $student->student_id,
            'invoice_id' => $invoice->id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
        ]);

        $this->activityLogService->log([
            'user_id' => Auth::id(),
            'role' => 'student',
            'action' => 'payment_initiated',
            'model_type' => Payment::class,
            'model_id' => $payment->id,
            'system_source' => 'SIP',
            'description' => "Payment of GHS {$payment->amount} initiated for invoice {$invoice->invoice_number}. Status: Processing - Awaiting admin confirmation.",
        ]);

        // TODO: In production, integrate with payment gateway here
        // For now, return message that payment is pending admin approval
        return redirect()->route('sip.payments.history')
            ->with('info', 'Payment initiated successfully. Your payment is being processed. An admin will confirm it shortly.');
    }
}

