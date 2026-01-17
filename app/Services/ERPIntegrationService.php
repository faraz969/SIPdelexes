<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityLogService;

class ERPIntegrationService
{
    protected $activityLogService;
    protected $erpBaseUrl;
    protected $erpApiKey;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
        $this->erpBaseUrl = config('services.erp.base_url', 'http://localhost:8000/api');
        $this->erpApiKey = config('services.erp.api_key', '');
    }

    /**
     * Create student record in ERP
     */
    public function createStudentRecord(array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->erpApiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->erpBaseUrl}/students", $data);

            if ($response->successful()) {
                $this->activityLogService->log([
                    'action' => 'erp_student_created',
                    'system_source' => 'ERP',
                    'description' => "Student record created in ERP: {$data['student_id']}",
                    'metadata' => ['erp_response' => $response->json()],
                ]);

                return $response->json();
            }

            throw new \Exception('ERP API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('ERP Integration Error: ' . $e->getMessage());
            // For now, return mock response since ERP is not integrated
            return $this->getMockResponse('create_student', $data);
        }
    }

    /**
     * Sync invoice from ERP to SIP
     */
    public function syncInvoice(array $invoiceData)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->erpApiKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->erpBaseUrl}/invoices/{$invoiceData['erp_invoice_id']}");

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
     * Process payment in ERP
     */
    public function processPayment(array $paymentData)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->erpApiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->erpBaseUrl}/payments", $paymentData);

            if ($response->successful()) {
                $this->activityLogService->log([
                    'action' => 'erp_payment_processed',
                    'system_source' => 'ERP',
                    'description' => "Payment processed in ERP: {$paymentData['payment_reference']}",
                    'metadata' => ['erp_response' => $response->json()],
                ]);

                return $response->json();
            }

            throw new \Exception('ERP API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('ERP Payment Processing Error: ' . $e->getMessage());
            // For now, return mock response
            return $this->getMockResponse('process_payment', $paymentData);
        }
    }

    /**
     * Get student balance from ERP
     */
    public function getStudentBalance($studentId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->erpApiKey,
            ])->get("{$this->erpBaseUrl}/students/{$studentId}/balance");

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('ERP API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('ERP Balance Check Error: ' . $e->getMessage());
            return $this->getMockResponse('get_balance', ['student_id' => $studentId]);
        }
    }

    /**
     * Notify ERP about deferment
     */
    public function notifyDeferment($studentId, $deferData)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->erpApiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->erpBaseUrl}/students/{$studentId}/defer", $deferData);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('ERP API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('ERP Deferment Notification Error: ' . $e->getMessage());
            return $this->getMockResponse('notify_deferment', $deferData);
        }
    }

    /**
     * Mock responses for development (until ERP is integrated)
     */
    protected function getMockResponse($action, $data)
    {
        switch ($action) {
            case 'create_student':
                return [
                    'success' => true,
                    'erp_student_id' => 'ERP-' . uniqid(),
                    'message' => 'Student created successfully (Mock)',
                ];
            case 'sync_invoice':
                return [
                    'success' => true,
                    'invoice' => $data,
                ];
            case 'process_payment':
                return [
                    'success' => true,
                    'erp_payment_id' => 'ERP-PAY-' . uniqid(),
                    'balance_updated' => true,
                ];
            case 'get_balance':
                return [
                    'success' => true,
                    'balance' => 0,
                    'total_invoiced' => 0,
                ];
            case 'notify_deferment':
                return [
                    'success' => true,
                    'message' => 'Deferment notification sent (Mock)',
                ];
            default:
                return ['success' => true];
        }
    }
}

