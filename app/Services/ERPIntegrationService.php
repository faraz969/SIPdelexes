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
    protected $erpApiSecret;
    protected $authType; // 'bearer' or 'token' (ERPNext format)

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
        $this->erpBaseUrl = config('services.erp.base_url', 'http://localhost:8000/api');
        $this->erpApiKey = config('services.erp.api_key', '');
        $this->erpApiSecret = config('services.erp.api_secret', '');
        // Default to 'bearer' for custom endpoints, 'token' for ERPNext standard API
        $this->authType = config('services.erp.auth_type', 'bearer');
    }

    /**
     * Get authorization header based on auth type
     */
    protected function getAuthHeader()
    {
        if ($this->authType === 'token' && $this->erpApiSecret) {
            // ERPNext format: token api_key:api_secret
            return 'token ' . $this->erpApiKey . ':' . $this->erpApiSecret;
        }
        // Default: Bearer token
        return 'Bearer ' . $this->erpApiKey;
    }

    /**
     * Create student record in ERP
     * Supports both custom endpoints and ERPNext standard REST API
     */
    public function createStudentRecord(array $data)
    {
        $endpoint = $this->erpBaseUrl; // Initialize for error logging
        
        try {
            // Determine endpoint URL based on base URL
            $endpoint = $this->erpBaseUrl;
            
            // If base URL doesn't contain '/method/' or '/resource/', assume custom endpoint
            if (strpos($this->erpBaseUrl, '/method/') === false && strpos($this->erpBaseUrl, '/resource/') === false) {
                // Custom endpoint format: /api/students
                $endpoint = rtrim($this->erpBaseUrl, '/') . '/students';
            }
            
            // Prepare data for ERPNext Customer creation
            $studentId = $data['student_id'] ?? '';
            $biodata = $data['biodata'] ?? [];
            $studentName = $biodata['name'] ?? "Student {$studentId}";
            
            // If using ERPNext standard REST API, format as Customer
            if (strpos($endpoint, '/resource/Customer') !== false || strpos($endpoint, '/resource/') !== false) {
                $customerData = [
                    'customer_name' => $studentName,
                    'customer_group' => 'Student',
                    'territory' => 'Ghana',
                    'customer_type' => 'Individual',
                ];
                
                // Add custom fields if they exist in ERPNext
                if (isset($data['student_id'])) {
                    $customerData['student_id'] = $data['student_id'];
                }
                
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => $this->getAuthHeader(),
                        'Content-Type' => 'application/json',
                    ])->post($endpoint, $customerData);
            } else {
                // Custom endpoint - send data as-is
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Authorization' => $this->getAuthHeader(),
                        'Content-Type' => 'application/json',
                    ])->post($endpoint, $data);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                
                $this->activityLogService->log([
                    'action' => 'erp_student_created',
                    'system_source' => 'ERP',
                    'description' => "Student record created in ERP: {$studentId}",
                    'metadata' => ['erp_response' => $responseData],
                ]);

                Log::info('ERP Student Created Successfully', [
                    'student_id' => $studentId,
                    'endpoint' => $endpoint,
                    'response' => $responseData
                ]);

                return $responseData;
            }

            // Log detailed error
            Log::error('ERP API Error Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'endpoint' => $endpoint,
                'student_id' => $studentId
            ]);

            throw new \Exception('ERP API Error: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('ERP Integration Error', [
                'message' => $e->getMessage(),
                'student_id' => $data['student_id'] ?? 'unknown',
                'endpoint' => $endpoint ?? $this->erpBaseUrl,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return mock response for now (non-blocking)
            return $this->getMockResponse('create_student', $data);
        }
    }

    /**
     * Sync invoice from ERP to SIP
     */
    public function syncInvoice(array $invoiceData)
    {
        try {
            $response = Http::timeout(5) // 5 second timeout
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
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
            $response = Http::timeout(5) // 5 second timeout
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
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
            $response = Http::timeout(5) // 5 second timeout
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
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
            $response = Http::timeout(5) // 5 second timeout
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
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

