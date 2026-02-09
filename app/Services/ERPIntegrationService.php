<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityLogService;
use App\Models\Program;

class ERPIntegrationService
{
    protected $activityLogService;
    protected $erpBaseUrl;
    protected $erpApiKey;
    protected $erpApiSecret;
    protected $authType;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
        $this->erpBaseUrl = rtrim(config('services.erp.base_url', 'http://localhost:8000/api'), '/');
        $this->erpApiKey = config('services.erp.api_key', '');
        $this->erpApiSecret = config('services.erp.api_secret', '');
        $this->authType = config('services.erp.auth_type', 'token');
    }

    /**
     * Get authorization header for ERPNext API
     */
    protected function getAuthHeader(): string
    {
        if ($this->authType === 'token' && $this->erpApiSecret) {
            return 'token ' . $this->erpApiKey . ':' . $this->erpApiSecret;
        }
        return 'Bearer ' . $this->erpApiKey;
    }

    /**
     * Get base URL for Frappe resource/method API
     * Expects base_url to be e.g. http://localhost:8000 or http://localhost:8000/api
     */
    protected function getResourceUrl(string $doctype): string
    {
        $base = $this->erpBaseUrl;
        if (strpos($base, '/api') === false) {
            $base .= '/api';
        }
        return $base . '/resource/' . rawurlencode($doctype);
    }

    protected function getMethodUrl(string $method): string
    {
        if (config('services.erp.use_cmd_endpoint', false)) {
            return $this->erpBaseUrl;
        }
        $base = $this->erpBaseUrl;
        if (strpos($base, '/api') === false) {
            $base .= '/api';
        }
        return $base . '/method/' . $method;
    }

    /**
     * For use_cmd_endpoint: build form body with cmd and other params
     */
    protected function getMethodBody(string $method, array $params): array
    {
        if (config('services.erp.use_cmd_endpoint', false)) {
            return array_merge(['cmd' => $method], $params);
        }
        return $params;
    }

    /**
     * Create Student Applicant and enroll in ERPNext Education module.
     * Creates Student + Program Enrollment. Returns erp_student_name for linking.
     */
    public function createStudentRecord(array $data): array
    {
        $studentId = $data['student_id'] ?? '';
        $biodata = $data['biodata'] ?? [];
        $programId = $data['program_id'] ?? null;
        $academicYear = $data['academic_year'] ?? '';

        $studentEmail = $studentId . '@delexesuniversity.edu.gh';
        $fullName = $biodata['full_name'] ?? $biodata['name'] ?? "Student {$studentId}";
        $nameParts = $this->splitFullName($fullName);

        $programName = $this->getErpProgramName($programId);
        $academicTerm = $data['academic_term'] ?? config('services.erp.default_academic_term', 'Semester 1');

        try {
            // 1. Create Student Applicant (Approved - ready for enrollment)
            $applicantData = [
                'first_name' => $nameParts['first'],
                'middle_name' => $nameParts['middle'],
                'last_name' => $nameParts['last'],
                'program' => $programName,
                'student_email_id' => $studentEmail,
                'academic_year' => $academicYear,
                'academic_term' => $academicTerm,
                'application_status' => 'Approved',
                'student_mobile_number' => $biodata['phone'] ?? null,
                'date_of_birth' => $biodata['dob'] ?? null,
                'gender' => $biodata['gender'] ?? null,
                'nationality' => $biodata['nationality'] ?? null,
                'address_line_1' => $biodata['address'] ?? null,
                'country' => $biodata['country'] ?? 'Ghana',
            ];

            // Use whitelisted method. Send as form-urlencoded (Frappe expects this for method calls on many setups).
            $createMethod = 'education.education.api.create_student_applicant_from_sip';
            $applicantUrl = $this->getMethodUrl($createMethod);
            $body = $this->getMethodBody($createMethod, [
                'data' => json_encode($applicantData),
            ]);
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
                    'Accept' => 'application/json',
                ])
                ->asForm()
                ->post($applicantUrl, $body);

            if (!$response->successful()) {
                Log::error('ERP Student Applicant Creation Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $applicantUrl,
                    'student_id' => $studentId,
                ]);
                throw new \Exception('ERP Student Applicant creation failed: ' . $response->body());
            }

            $applicantResponse = $response->json();
            $applicantName = $applicantResponse['message']['name'] ?? $applicantResponse['name'] ?? $applicantResponse['data']['name'] ?? null;

            if (!$applicantName) {
                throw new \Exception('No Student Applicant name in ERP response');
            }

            Log::info('ERP Student Applicant Created', [
                'student_id' => $studentId,
                'applicant_name' => $applicantName,
            ]);

            // 2. Enroll student (creates Student + Program Enrollment)
            $enrollMethod = 'education.education.api.enroll_student';
            $enrollUrl = $this->getMethodUrl($enrollMethod);
            $enrollBody = $this->getMethodBody($enrollMethod, ['source_name' => $applicantName]);
            $enrollResponse = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
                    'Accept' => 'application/json',
                ])
                ->asForm()
                ->post($enrollUrl, $enrollBody);

            if (!$enrollResponse->successful()) {
                Log::error('ERP Enroll Student Failed', [
                    'status' => $enrollResponse->status(),
                    'body' => $enrollResponse->body(),
                    'applicant_name' => $applicantName,
                ]);
                throw new \Exception('ERP enroll student failed: ' . $enrollResponse->body());
            }

            $enrollData = $enrollResponse->json();
            $erpStudentName = null;

            // enroll_student returns Program Enrollment doc; student name is in program_enrollment.student
            $docs = $enrollData['docs'] ?? [];
            $doc = is_array($docs) && count($docs) > 0 ? $docs[0] : ($enrollData['data'] ?? $enrollData);
            if (is_array($doc)) {
                $erpStudentName = $doc['student'] ?? $doc['name'] ?? null;
            }

            if (!$erpStudentName) {
                $erpStudentName = $this->findErpStudentByEmail($studentEmail);
            }

            if ($erpStudentName) {
                $this->updateErpStudentIndexNumber($erpStudentName, $studentId);
            }

            $this->activityLogService->log([
                'action' => 'erp_student_created',
                'system_source' => 'ERP',
                'description' => "Student enrolled in ERP: {$studentId} -> {$erpStudentName}",
                'metadata' => [
                    'erp_student_name' => $erpStudentName,
                    'applicant_name' => $applicantName,
                ],
            ]);

            Log::info('ERP Student Enrolled Successfully', [
                'student_id' => $studentId,
                'erp_student_name' => $erpStudentName,
            ]);

            return [
                'success' => true,
                'erp_student_name' => $erpStudentName,
                'applicant_name' => $applicantName,
            ];
        } catch (\Exception $e) {
            Log::error('ERP Integration Error', [
                'message' => $e->getMessage(),
                'student_id' => $studentId,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Update ERPNext Student with index_number (SIP student_id) for linking
     */
    protected function updateErpStudentIndexNumber(string $erpStudentName, string $indexNumber): void
    {
        try {
            $url = $this->getResourceUrl('Student') . '/' . rawurlencode($erpStudentName);
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
                    'Content-Type' => 'application/json',
                ])->put($url, ['index_number' => $indexNumber]);

            if ($response->successful()) {
                Log::info('ERP Student index_number updated', [
                    'erp_student' => $erpStudentName,
                    'index_number' => $indexNumber,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Could not update ERP Student index_number', [
                'erp_student' => $erpStudentName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find ERPNext Student by student_email_id
     */
    protected function findErpStudentByEmail(string $email): ?string
    {
        try {
            $url = $this->getResourceUrl('Student') . '?filters=[["student_email_id","=","' . addslashes($email) . '"]]&fields=["name"]&limit_page_length=1';
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => $this->getAuthHeader()])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $students = $data['data'] ?? [];
                return $students[0]['name'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('Could not find ERP Student by email', ['email' => $email, 'error' => $e->getMessage()]);
        }
        return null;
    }

    protected function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 3);
        return [
            'first' => $parts[0] ?? 'Student',
            'middle' => $parts[1] ?? '',
            'last' => $parts[2] ?? '',
        ];
    }

    /**
     * Map SIP program to ERPNext program name. Add config mapping if names differ.
     */
    protected function getErpProgramName(?int $programId): string
    {
        $mapping = config('services.erp.program_mapping', []);
        if ($programId && isset($mapping[$programId])) {
            return $mapping[$programId];
        }
        if ($programId) {
            $program = Program::find($programId);
            return $program ? $program->name : 'General';
        }
        return config('services.erp.default_program', 'General');
    }

    /**
     * Sync Sales Invoices from ERPNext for a student
     */
    public function fetchStudentInvoices(string $erpStudentName): array
    {
        try {
            $url = $this->getResourceUrl('Sales Invoice')
                . '?filters=[["student","=","' . rawurlencode($erpStudentName) . '"]]'
                . '&fields=["name","status","due_date","grand_total","outstanding_amount","fee_schedule","posting_date"]'
                . '&limit_page_length=100';

            $response = Http::timeout(15)
                ->withHeaders(['Authorization' => $this->getAuthHeader()])
                ->get($url);

            if (!$response->successful()) {
                throw new \Exception('ERP Invoice fetch failed: ' . $response->body());
            }

            $data = $response->json();
            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('ERP Invoice Fetch Error', [
                'erp_student' => $erpStudentName,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Submit Payment Entry to ERPNext against a Sales Invoice.
     * Uses education billing.submit_fee_payment_from_sip for clean integration.
     */
    public function submitPaymentEntry(string $erpInvoiceName, float $amount, string $paymentReference, ?string $bankAccount = null): array
    {
        try {
            $methodUrl = $this->getMethodUrl('education.education.api.submit_fee_payment_from_sip');
            $params = [
                'against_invoice' => $erpInvoiceName,
                'amount' => $amount,
                'reference_no' => $paymentReference,
            ];
            if ($bankAccount) {
                $params['bank_account'] = $bankAccount;
            }

            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
                    'Content-Type' => 'application/json',
                ])->post($methodUrl, $params);

            if (!$response->successful()) {
                throw new \Exception('Payment submission failed: ' . $response->body());
            }

            $data = $response->json();
            if (isset($data['exc'])) {
                throw new \Exception('ERP error: ' . ($data['message'] ?? $data['exc'] ?? 'Unknown'));
            }

            $peName = $data['payment_entry'] ?? $data['message']['payment_entry'] ?? null;

            if (!$peName) {
                throw new \Exception('No Payment Entry name in response');
            }

            $this->activityLogService->log([
                'action' => 'erp_payment_processed',
                'system_source' => 'ERP',
                'description' => "Payment Entry submitted: {$peName} for {$erpInvoiceName}",
                'metadata' => ['payment_entry' => $peName, 'invoice' => $erpInvoiceName],
            ]);

            return [
                'success' => true,
                'erp_payment_id' => $peName,
                'erp_invoice_id' => $erpInvoiceName,
            ];
        } catch (\Exception $e) {
            Log::error('ERP Payment Submission Error', [
                'invoice' => $erpInvoiceName,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync invoice from ERP to SIP (legacy - kept for compatibility)
     */
    public function syncInvoice(array $invoiceData)
    {
        try {
            $erpInvoiceId = $invoiceData['erp_invoice_id'] ?? null;
            if (!$erpInvoiceId) {
                return $this->getMockResponse('sync_invoice', $invoiceData);
            }

            $url = $this->getResourceUrl('Sales Invoice') . '/' . rawurlencode($erpInvoiceId);
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => $this->getAuthHeader()])
                ->get($url);

            if ($response->successful()) {
                return $response->json();
            }
            throw new \Exception('ERP API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('ERP Invoice Sync Error: ' . $e->getMessage());
            return $this->getMockResponse('sync_invoice', $invoiceData);
        }
    }

    /**
     * Process payment in ERP (delegates to submitPaymentEntry)
     */
    public function processPayment(array $paymentData)
    {
        $erpInvoiceId = $paymentData['erp_invoice_id'] ?? null;
        $amount = (float) ($paymentData['amount'] ?? 0);
        $reference = $paymentData['payment_reference'] ?? 'SIP-' . uniqid();

        if (!$erpInvoiceId || $amount <= 0) {
            Log::warning('ERP processPayment: missing invoice or amount', $paymentData);
            return $this->getMockResponse('process_payment', $paymentData);
        }

        try {
            return $this->submitPaymentEntry($erpInvoiceId, $amount, $reference);
        } catch (\Exception $e) {
            Log::error('ERP Payment Processing Error: ' . $e->getMessage());
            return $this->getMockResponse('process_payment', $paymentData);
        }
    }

    /**
     * Get student balance from ERP
     */
    public function getStudentBalance(string $erpStudentName): array
    {
        try {
            $invoices = $this->fetchStudentInvoices($erpStudentName);
            $totalInvoiced = 0;
            $outstanding = 0;
            foreach ($invoices as $inv) {
                $totalInvoiced += (float) ($inv['grand_total'] ?? 0);
                $outstanding += (float) ($inv['outstanding_amount'] ?? 0);
            }
            return [
                'success' => true,
                'balance' => $outstanding,
                'total_invoiced' => $totalInvoiced,
            ];
        } catch (\Exception $e) {
            Log::error('ERP Balance Check Error: ' . $e->getMessage());
            return ['success' => false, 'balance' => 0, 'total_invoiced' => 0];
        }
    }

    /**
     * Notify ERP about deferment
     */
    public function notifyDeferment(string $studentId, array $deferData)
    {
        Log::info('ERP Deferment notification (not implemented)', ['student_id' => $studentId]);
        return ['success' => true, 'message' => 'Deferment notification (placeholder)'];
    }

    protected function getMockResponse(string $action, $data): array
    {
        switch ($action) {
            case 'create_student':
                return ['success' => false, 'message' => 'ERP integration failed - check logs'];
            case 'sync_invoice':
                return ['success' => false, 'invoice' => $data];
            case 'process_payment':
                return ['success' => false, 'erp_payment_id' => null];
            default:
                return ['success' => false];
        }
    }
}
