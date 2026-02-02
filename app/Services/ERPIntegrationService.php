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
            // Prepare data for ERPNext Customer creation
            $studentId = $data['student_id'] ?? '';
            $biodata = $data['biodata'] ?? [];
            $studentName = $biodata['name'] ?? "Student {$studentId}";
            
            // Determine endpoint and data format based on base URL
            $endpoint = $this->erpBaseUrl;
            $useStandardAPI = false;
            
            // Check if using ERPNext standard REST API
            // Check for '/resource' (with or without trailing slash or doctype)
            if (strpos($this->erpBaseUrl, '/resource') !== false) {
                // Already has /resource - use standard Customer API
                $useStandardAPI = true;
                // Extract base URL up to /resource
                $baseResourceUrl = preg_replace('#(/resource).*$#', '$1', $this->erpBaseUrl);
                // Always use /Customer doctype
                $endpoint = rtrim($baseResourceUrl, '/') . '/Customer';
            } elseif (strpos($this->erpBaseUrl, '/method/') !== false) {
                // Custom method endpoint - use as-is
                $endpoint = $this->erpBaseUrl;
            } elseif (strpos($this->erpBaseUrl, '/api') !== false) {
                // Base API URL - use standard Customer API
                $useStandardAPI = true;
                $endpoint = rtrim($this->erpBaseUrl, '/') . '/resource/Customer';
            } else {
                // Fallback: try custom /students endpoint
                $endpoint = rtrim($this->erpBaseUrl, '/') . '/students';
            }
            
            // Prepare customer data for ERPNext
            if ($useStandardAPI) {
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
                
                $requestData = $customerData;
            } else {
                // Custom endpoint - send data as-is
                $requestData = $data;
            }
            
            Log::info('ERP Student Creation Request', [
                'endpoint' => $endpoint,
                'student_id' => $studentId,
                'use_standard_api' => $useStandardAPI,
                'auth_type' => $this->authType
            ]);
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => $this->getAuthHeader(),
                    'Content-Type' => 'application/json',
                ])->post($endpoint, $requestData);

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

