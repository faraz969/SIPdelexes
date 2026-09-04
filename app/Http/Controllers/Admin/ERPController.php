<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ERPIntegrationService;
use App\Services\ERPInvoiceSyncService;
use App\Services\ActivityLogService;
use App\Models\SiteSetting;

class ERPController extends Controller
{
    protected $erpService;
    protected $invoiceSyncService;
    protected $activityLogService;

    public function __construct(
        ERPIntegrationService $erpService,
        ERPInvoiceSyncService $invoiceSyncService,
        ActivityLogService $activityLogService
    ) {
        $this->erpService = $erpService;
        $this->invoiceSyncService = $invoiceSyncService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * ERP Dashboard - Manage ERP integration functions
     */
    public function dashboard()
    {
        $this->invoiceSyncService->syncAllIfNeeded(10);

        $stats = [
            'total_students' => Student::count(),
            'active_students' => Student::where('academic_status', 'active')->count(),
            'deferred_students' => Student::where('academic_status', 'deferred')->count(),
            'total_invoices' => Invoice::count(),
            'pending_invoices' => Invoice::where('synced_from_erp', false)->count(),
            'unpaid_invoices' => Invoice::where('status', '!=', 'paid')->count(),
            'total_payments' => Payment::count(),
            'pending_payments' => Payment::where('erp_status', 'pending')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
        ];

        // Recent activity
        $recentInvoices = Invoice::with('student.user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentPayments = Payment::with(['student.user', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.erp.dashboard', compact('stats', 'recentInvoices', 'recentPayments'));
    }

    /**
     * Sync invoice from ERP
     */
    public function syncInvoice(Request $request)
    {
        $request->validate([
            'erp_invoice_id' => 'required|string',
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::findOrFail($request->student_id);

        // Get invoice data from ERP
        $erpData = $this->erpService->syncInvoice([
            'erp_invoice_id' => $request->erp_invoice_id,
        ]);

        // Create or update invoice in SIP
        $invoice = Invoice::updateOrCreate(
            ['erp_invoice_id' => $request->erp_invoice_id],
            [
                'student_id' => $student->id,
                'invoice_number' => $erpData['invoice_number'] ?? 'INV-' . uniqid(),
                'invoice_type' => $erpData['invoice_type'] ?? 'tuition',
                'academic_year' => $erpData['academic_year'] ?? $student->academic_year,
                'semester' => $erpData['semester'] ?? null,
                'total_amount' => $erpData['total_amount'] ?? 0,
                'paid_amount' => $erpData['paid_amount'] ?? 0,
                'balance' => $erpData['balance'] ?? $erpData['total_amount'] ?? 0,
                'status' => $erpData['status'] ?? 'pending',
                'due_date' => $erpData['due_date'] ?? now()->addDays(30),
                'issued_date' => $erpData['issued_date'] ?? now(),
                'line_items' => $erpData['line_items'] ?? [],
                'synced_from_erp' => true,
                'synced_at' => now(),
            ]
        );

        $this->activityLogService->log([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role,
            'action' => 'invoice_synced_from_erp',
            'model_type' => Invoice::class,
            'model_id' => $invoice->id,
            'system_source' => 'ERP',
            'description' => "Invoice synced from ERP: {$request->erp_invoice_id}",
        ]);

        return redirect()->route('admin.erp.dashboard')
            ->with('success', 'Invoice synced successfully.');
    }

    /**
     * Show form to generate invoice manually
     */
    public function showGenerateInvoiceForm()
    {
        $students = Student::with('user', 'program')
            ->where('sip_account_created', true)
            ->orderBy('student_id')
            ->get();

        $defaultAcademicYear = SiteSetting::currentAcademicYear();

        return view('admin.erp.generate-invoice', compact('students', 'defaultAcademicYear'));
    }

    /**
     * Generate invoice manually (for testing until ERP is integrated)
     */
    public function generateInvoice(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_type' => 'required|in:tuition,late_registration,other',
            'academic_year' => 'required|string',
            'semester' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'line_items' => 'nullable|array',
        ]);

        $student = Student::findOrFail($request->student_id);

        $invoice = Invoice::create([
            'student_id' => $student->id,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'invoice_type' => $request->invoice_type,
            'academic_year' => $request->academic_year,
            'semester' => $request->semester,
            'total_amount' => $request->total_amount,
            'paid_amount' => 0,
            'balance' => $request->total_amount,
            'status' => 'pending',
            'due_date' => $request->due_date,
            'issued_date' => now(),
            'line_items' => $request->line_items ?? [],
            'synced_from_erp' => false,
        ]);

        \Log::info("Invoice Generated Manually", [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'student_id' => $student->student_id,
            'amount' => $invoice->total_amount,
            'generated_by' => auth()->id(),
        ]);

        $this->activityLogService->log([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role,
            'action' => 'invoice_generated_manually',
            'model_type' => Invoice::class,
            'model_id' => $invoice->id,
            'system_source' => 'SIP',
            'description' => "Invoice generated manually for student {$student->student_id}",
            'metadata' => [
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->total_amount,
            ],
        ]);

        return redirect()->route('admin.erp.invoices')
            ->with('success', 'Invoice generated successfully.');
    }

    /**
     * View students
     */
    public function students()
    {
        $students = Student::with(['user', 'program', 'department'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.erp.students', compact('students'));
    }

    /**
     * View invoices
     */
    public function invoices()
    {
        $this->invoiceSyncService->syncAllIfNeeded(10);

        $invoices = Invoice::with(['student.user', 'student.program'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => Invoice::count(),
            'pending' => Invoice::where('status', 'pending')->count(),
            'partial' => Invoice::where('status', 'partial')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'total_amount' => Invoice::sum('total_amount'),
            'total_balance' => Invoice::sum('balance'),
        ];

        return view('admin.erp.invoices', compact('invoices', 'stats'));
    }

    /**
     * View single invoice
     */
    public function showInvoice(Invoice $invoice)
    {
        $invoice->load(['student.user', 'student.program', 'payments']);
        return view('admin.erp.invoice-show', compact('invoice'));
    }

    /**
     * Show sync invoice form
     */
    public function showSyncInvoiceForm()
    {
        $students = Student::with('user')
            ->where('sip_account_created', true)
            ->orderBy('student_id')
            ->get();

        return view('admin.erp.sync-invoice', compact('students'));
    }

    /**
     * View payments
     */
    public function payments()
    {
        $payments = Payment::with(['student.user', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total' => Payment::count(),
            'completed' => Payment::where('status', 'completed')->count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'failed' => Payment::where('status', 'failed')->count(),
            'total_amount' => Payment::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.erp.payments', compact('payments', 'stats'));
    }

    /**
     * Manually process payment (mock ERP confirmation)
     */
    public function processPayment(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:completed,failed',
            'erp_payment_id' => 'nullable|string',
        ]);

        $oldStatus = $payment->status;
        
        $payment->update([
            'status' => $request->status,
            'erp_payment_id' => $request->erp_payment_id ?? 'ERP-PAY-' . uniqid(),
            'erp_status' => 'synced',
            'erp_synced_at' => now(),
            'erp_response' => json_encode(['status' => $request->status, 'processed_by' => auth()->id()]),
        ]);

        // Update invoice balance if payment is completed
        if ($request->status === 'completed' && $payment->invoice) {
            $payment->invoice->updateBalance();
        }

        \Log::info("Payment Processed Manually", [
            'payment_id' => $payment->id,
            'payment_reference' => $payment->payment_reference,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'processed_by' => auth()->id(),
        ]);

        $this->activityLogService->log([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role,
            'action' => 'payment_processed_manually',
            'model_type' => Payment::class,
            'model_id' => $payment->id,
            'system_source' => 'SIP',
            'description' => "Payment {$payment->payment_reference} processed manually",
            'old_value' => json_encode(['status' => $oldStatus]),
            'new_value' => json_encode(['status' => $request->status]),
        ]);

        return redirect()->route('admin.erp.payments')
            ->with('success', 'Payment processed successfully.');
    }

    /**
     * View student details
     */
    public function showStudent(Student $student)
    {
        $this->invoiceSyncService->syncForStudentIfNeeded($student, 5);

        $student->load(['user', 'program', 'department', 'invoices', 'payments', 'courseRegistrations', 'examPins', 'deferments']);
        
        return view('admin.erp.student-show', compact('student'));
    }

    /**
     * View student emails and passwords for webmail creation
     */
    public function studentEmails()
    {
        $students = Student::with(['user', 'program', 'department'])
            ->where('sip_account_created', true)
            ->orderBy('student_id')
            ->paginate(50);

        return view('admin.erp.student-emails', compact('students'));
    }
}

