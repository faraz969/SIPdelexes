<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ERPInvoiceSyncService
{
    protected $erpService;

    public function __construct(ERPIntegrationService $erpService)
    {
        $this->erpService = $erpService;
    }

    /**
     * Sync invoices from ERPNext for a student.
     * Creates/updates Invoice records in SIP.
     */
    public function syncForStudent(Student $student): int
    {
        $erpStudentName = $student->erp_student_name;
        if (!$erpStudentName) {
            Log::info('ERP Invoice Sync skipped - no erp_student_name', [
                'student_id' => $student->student_id,
            ]);
            return 0;
        }

        $erpInvoices = $this->erpService->fetchStudentInvoices($erpStudentName);
        $synced = 0;

        foreach ($erpInvoices as $erpInv) {
            $erpInvoiceId = $erpInv['name'] ?? null;
            if (!$erpInvoiceId) {
                continue;
            }

            $grandTotal = (float) ($erpInv['grand_total'] ?? 0);
            $outstanding = (float) ($erpInv['outstanding_amount'] ?? $grandTotal);
            $paidAmount = $grandTotal - $outstanding;

            $status = $this->mapErpStatusToSip($erpInv['status'] ?? 'Unpaid');

            $invoice = Invoice::firstOrNew(
                ['erp_invoice_id' => $erpInvoiceId]
            );

            $invoice->student_id = $student->id;
            $invoice->invoice_number = $erpInvoiceId;
            $invoice->erp_invoice_id = $erpInvoiceId;
            $invoice->invoice_type = 'tuition';
            $invoice->academic_year = $this->extractAcademicYear($erpInv);
            $invoice->total_amount = $grandTotal;
            $invoice->paid_amount = $paidAmount;
            $invoice->balance = $outstanding;
            $invoice->status = $status;
            $invoice->due_date = $this->parseDate($erpInv['due_date'] ?? null);
            $invoice->issued_date = $this->parseDate($erpInv['posting_date'] ?? null);
            $invoice->synced_from_erp = true;
            $invoice->synced_at = now();

            $invoice->save();
            $synced++;
        }

        return $synced;
    }

    /**
     * Sync invoices for all students with erp_student_name.
     */
    public function syncAll(): array
    {
        $students = Student::whereNotNull('erp_student_name')->get();
        $total = 0;
        $errors = [];

        foreach ($students as $student) {
            try {
                $count = $this->syncForStudent($student);
                $total += $count;
            } catch (\Exception $e) {
                $errors[] = [
                    'student_id' => $student->student_id,
                    'error' => $e->getMessage(),
                ];
                Log::error('ERP Invoice Sync failed for student', [
                    'student_id' => $student->student_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'synced' => $total,
            'students_processed' => $students->count(),
            'errors' => $errors,
        ];
    }

    protected function mapErpStatusToSip(string $erpStatus): string
    {
        $status = strtolower($erpStatus);
        switch ($status) {
            case 'paid':
                return 'paid';
            case 'partly paid':
                return 'partial';
            default:
                return 'pending';
        }
    }

    protected function extractAcademicYear(array $erpInv): string
    {
        return now()->format('Y');
    }

    protected function parseDate($value): Carbon
    {
        if (empty($value)) {
            return now();
        }
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return now();
        }
    }
}
