<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\FormType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PaymentController extends Controller
{
    private $merchantKey = '4b67b159-e26d-4d23-96f0-b270b38e5cb7';
    private $hashingKey = 'f715db94f4bfe648cd69d85c4c89229668e8520f2eddd81d5f841297f55e15e0e010dac5be89738c4d540dce1d5aa587d25566abac6a6b7d303a6dbc9350679b';
    private $gatewayUrl = 'https://pgw.paywithonline.com/v1/mobile_agents_v2';
    private $statusCheckUrl = 'https://pgw.paywithonline.com/v1/gateway/json_status_chk';

    /**
     * Initiate payment with EcobankPay
     */
    public function initiatePayment(Request $request)
    {
        // Log incoming request for debugging
        Log::info('Payment Initiation Request', [
            'request_data' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'country_code' => 'required|string',
                'phone' => 'required|string|max:20',
                'nationality' => 'required|string|max:255',
                'form_type' => 'required|exists:form_types,id',
                'payment_mode' => 'required|in:gcb,paystack,ecobank',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Payment Validation Error', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', array_flatten($e->errors())),
                'errors' => $e->errors()
            ], 400);
        }

        // Get form type details
        $formType = FormType::find($validated['form_type']);
        
        if (!$formType) {
            Log::error('Form Type Not Found', ['form_type_id' => $validated['form_type']]);
            return response()->json([
                'success' => false,
                'message' => 'Selected form type not found.'
            ], 400);
        }
        
        // Determine student type and calculate price
        $isLocal = $validated['nationality'] === 'Ghana';
        $price = $isLocal ? $formType->local_price : $formType->international_price;
        
        // Convert to GHS if international student
        if (!$isLocal && $formType->conversion_rate) {
            $price = $price * $formType->conversion_rate;
        }

        Log::info('Price Calculation', [
            'form_type' => $formType->name,
            'is_local' => $isLocal,
            'original_price' => $isLocal ? $formType->local_price : $formType->international_price,
            'conversion_rate' => $formType->conversion_rate,
            'final_price' => $price
        ]);

        // Generate unique invoice ID - using simpler format
        $invoiceId = 'DUC' . time();

        // Format total WITHOUT thousand separators to avoid gateway rejection
        // Ensure a dot as decimal separator and no grouping separators
        $formattedTotal = number_format((float) $price, 2, '.', '');

        // Create secure hash - parameters must be sorted alphabetically
        // According to sample: invoice_id=test001&merchant_key=xxx-xxx&total=1.00
        $queryString = "invoice_id={$invoiceId}&merchant_key={$this->merchantKey}&total={$formattedTotal}";
        $secureHash = strtoupper(hash_hmac('sha256', $queryString, hex2bin($this->hashingKey)));

        Log::info('Hash Generation', [
            'query_string' => $queryString,
            'hashing_key' => $this->hashingKey,
            'generated_hash' => $secureHash,
            'invoice_id' => $invoiceId,
            'merchant_key' => $this->merchantKey,
            'total' => $formattedTotal
        ]);

        // Prepare payment data - following the sample format
        $paymentData = [
            'merchant_key' => $this->merchantKey,
            'total' => $formattedTotal, // Use dot-decimal, no thousand separators
            'invoice_id' => $invoiceId,
    "success_url"=> route('payment.success'),
    "cancel_url"=> route('payment.cancelled'),
    "ipn_url"=>"https://webhook.site/4324234243",
    "extra_outlet"=>1061,
    "generate_checkout_url"=>true,
            
            'secure_hash' => $secureHash,
            //'pymt_instrument' => $validated['country_code'] . $validated['phone'],
        ];

        // Store pending registration data in session
        session([
            'pending_registration' => [
                'user_data' => $validated,
                'form_type' => $formType,
                'invoice_id' => $invoiceId,
                'amount' => $price,
                'is_local' => $isLocal,
                'payment_mode' => $request->input('payment_mode', 'ecobank')
            ]
        ]);
        Log::info('pending Data', [
            'pending_data' => session('pending_registration'),
            
        ]);

        // Branch: Paystack, GCB, or Ecobank based on selected payment method
        $selectedPaymentMode = strtolower($request->input('payment_mode', 'ecobank'));

        if ($selectedPaymentMode === 'paystack') {
            return $this->initiatePaystackPayment($validated, $formType, $price, $invoiceId, $isLocal);
        }

        if ($selectedPaymentMode === 'gcb') {
            try {
                // Build GCB Checkout request
                $gcbApiKey = env('GCB_API_KEY', 'GCB-Cp98RM7YKey6JUlMyzk1uQALX7IkhQuC');
                $maskedKey = substr($gcbApiKey, 0, 6) . '...' . substr($gcbApiKey, -4);
                // Use provided base URL for GCB UAT, configurable via env
                $gcbBaseUrl = rtrim(env('GCB_BASE_URL', 'https://epay.gcbltd.com:211/'), '/');
                $gcbCheckoutUrl = $gcbBaseUrl . '/checkout';

                $payload = [
                    'merchantRef' => $invoiceId,
                    'amount' => (float) number_format((float) $price, 2, '.', ''),
                    'currency' => 'GHS',
                    'description' => 'Admission Form Purchase - ' . $formType->name,
                    // Intentionally omit paymentOption to let gateway decide (UAT docs show it optional)
                    // 'paymentOption' => null,
                    'callBackUrl' => route('payment.success'),
                ];
                // Remove any null/empty values to avoid schema rejection
                $payload = array_filter($payload, function ($v) { return !is_null($v) && $v !== ''; });

                Log::info('Initiating GCB Checkout', [
                    'url' => $gcbCheckoutUrl,
                    'payload' => $payload,
                    'api_key_present' => !empty($gcbApiKey),
                    'api_key_masked' => $maskedKey,
                ]);

                $response = Http::timeout(30)
                    // UAT may use self-signed certs; allow disabling via env (default false)
                    ->withOptions(['verify' => env('GCB_TLS_VERIFY', false)])
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        // Some servers are finicky about header casing; send both
                        'X-Api-Key' => $gcbApiKey,
                        
                        // Some API gateways map the OpenAPI security scheme name to a header
                       
                        'Accept' => 'application/json',
                    ])
                    ->post($gcbCheckoutUrl, $payload);

                Log::info('GCB API Response', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $response->body(),
                ]);

                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'GCB gateway error (HTTP ' . $response->status() . ').',
                        'gateway_response' => $response->body(),
                        'headers' => $response->headers(),
                    ], 500);
                }

                $data = [];
                try { $data = $response->json(); } catch (\Throwable $t) { /* ignore json errors */ }
                $checkoutId = $data['checkOutId'] ?? $data['checkoutId'] ?? null;
                session([
                    'checkoutid' => $checkoutId
                ]);
                $sessioncheck=session('checkoutid');

                Log::info('GCB Checkout ID', ['checkoutid' => $sessioncheck]);
                // Try common fields for redirect URL
                $redirectUrl = $data['checkOutUrl'] ?? $data['payment_url'] ?? $data['url'] ?? $data['redirectUrl'] ?? null;
                if (!$redirectUrl) {
                    // Sometimes redirect comes via Location header
                    $locationHeader = $response->header('Location') ?? ($response->headers()['Location'][0] ?? null);
                    if ($locationHeader) {
                        $redirectUrl = $locationHeader;
                    }
                }

                if ($redirectUrl) {
                    return response()->json([
                        'success' => true,
                        'payment_url' => $redirectUrl,
                        'invoice_id' => $invoiceId,
                    ]);
                }

                // If API returns a checkout identifier only
               
                if ($checkoutId) {
                    // Try building a hosted URL under the same prefix if applicable
                    $hostedUrl = 'https://epay.gcbltd.com:211/checkout?id=' . $checkoutId;
                    return response()->json([
                        'success' => true,
                        'payment_url' => $hostedUrl,
                        'invoice_id' => $invoiceId,
                    ]);

                   
                }

                // Fallback: unknown response
                return response()->json([
                    'success' => false,
                    'message' => 'Unexpected GCB response; no redirect URL provided.',
                    'gateway_response' => $data ?: $response->body(),
                ], 500);
            } catch (\Exception $e) {
                Log::error('GCB Initiation Error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while initiating GCB payment.',
                ], 500);
            }
        }

        try {
            // Log payment data being sent
            Log::info('Sending Payment Data to EcobankPay', [
                'payment_data' => $paymentData,
                'gateway_url' => $this->gatewayUrl
            ]);

            // Make API call to EcobankPay with JSON content type
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Content-Length' => strlen(json_encode($paymentData))
                ])
                ->post($this->gatewayUrl, $paymentData);

            Log::info('EcobankPay API Response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('EcobankPay Response Data', ['response_data' => $responseData]);
                
                if (isset($responseData['success']) && $responseData['success']) {
                    return response()->json([
                        'success' => true,
                        'payment_url' => $responseData['url'],
                        'invoice_id' => $invoiceId
                    ]);
                } else {
                    Log::error('EcobankPay API Success False', [
                        'response_data' => $responseData
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment gateway returned error: ' . ($responseData['message'] ?? 'Unknown error'),
                        'gateway_response' => $responseData
                    ], 400);
                }
            } else {
                Log::error('EcobankPay API HTTP Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers' => $response->headers()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment gateway error (HTTP ' . $response->status() . '). Please try again.',
                    'gateway_response' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Payment Initiation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing payment. Please try again.'
            ], 500);
        }
    }

    /**
     * Initialize Paystack checkout for admission form purchase.
     */
    private function initiatePaystackPayment(array $validated, FormType $formType, $price, $invoiceId, $isLocal)
    {
        $secretKey = config('services.paystack.secret_key');

        if (empty($secretKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Paystack is not configured. Please contact support.',
            ], 500);
        }

        try {
            $amountInPesewas = (int) round((float) $price * 100);

            if ($amountInPesewas < 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount is too low for Paystack.',
                ], 400);
            }

            $payload = [
                'email' => $validated['email'],
                'amount' => $amountInPesewas,
                'currency' => 'GHS',
                'reference' => $invoiceId,
                'callback_url' => route('payment.success'),
                'metadata' => [
                    'invoice_id' => $invoiceId,
                    'full_name' => $validated['full_name'],
                    'form_type' => $formType->name,
                    'student_type' => $isLocal ? 'local' : 'international',
                ],
            ];

            Log::info('Initiating Paystack Checkout', [
                'reference' => $invoiceId,
                'amount_pesewas' => $amountInPesewas,
                'email' => $validated['email'],
            ]);

            $response = Http::timeout(30)
                ->withToken($secretKey)
                ->acceptJson()
                ->post('https://api.paystack.co/transaction/initialize', $payload);

            Log::info('Paystack Initialize Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => $this->parsePaystackErrorMessage($response->json(), $response->body()),
                ], 500);
            }

            $data = $response->json();
            if (!($data['status'] ?? false) || empty($data['data']['authorization_url'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['message'] ?? 'Paystack could not start the payment.',
                ], 500);
            }

            session(['paystack_reference' => $data['data']['reference'] ?? $invoiceId]);

            return response()->json([
                'success' => true,
                'payment_url' => $data['data']['authorization_url'],
                'invoice_id' => $invoiceId,
            ]);
        } catch (\Exception $e) {
            Log::error('Paystack Initiation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initiating Paystack payment.',
            ], 500);
        }
    }

    /**
     * Verify a Paystack transaction by reference.
     */
    private function verifyPaystackPayment($reference)
    {
        $secretKey = config('services.paystack.secret_key');

        if (empty($secretKey) || empty($reference)) {
            return false;
        }

        try {
            $response = Http::timeout(30)
                ->withToken($secretKey)
                ->acceptJson()
                ->get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));

            Log::info('Paystack Verify Response', [
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                return false;
            }

            $data = $response->json();

            return ($data['status'] ?? false) === true
                && ($data['data']['status'] ?? '') === 'success';
        } catch (\Exception $e) {
            Log::error('Paystack Verification Error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Extract a readable message from a Paystack API error response.
     */
    private function parsePaystackErrorMessage($json, $rawBody)
    {
        if (is_array($json) && !empty($json['message']) && is_string($json['message'])) {
            return $json['message'];
        }

        $rawBody = trim((string) $rawBody);

        return $rawBody !== '' ? $rawBody : 'Paystack payment could not be started.';
    }

    /**
     * Handle payment success
     */
    public function paymentSuccess(Request $request)
    {
        // Log all incoming parameters for debugging
        Log::info('Payment Success Callback', [
            'query_params' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        // Get invoice ID from request or session
        $invoiceId = $request->get('invoice_id') ?? $request->get('merchantRef') ?? session('pending_registration.invoice_id');
        
        if (!$invoiceId) {
            return redirect()->route('registration.create')
                ->with('error', 'Invalid payment response.');
        }

        // GCB and Paystack payments are verified against their own gateways — not Ecobank.
        $paymentMode = strtolower((string) session('pending_registration.payment_mode', 'ecobank'));
        if ($paymentMode === 'paystack') {
            $reference = $request->get('reference') ?? $request->get('trxref') ?? $invoiceId;

            Log::info('Paystack Payment Return', [
                'invoice_id' => $invoiceId,
                'reference' => $reference,
            ]);

            if (!$this->verifyPaystackPayment($reference)) {
                return redirect()->route('payment.cancelled')
                    ->with('error', 'Payment was not completed or could not be verified with Paystack.');
            }

            $paystackVerifiedPayload = [
                $invoiceId => [
                    'status' => 'paid',
                    'status_reason' => 'Verified via Paystack',
                ],
            ];

            return $this->completeRegistration($invoiceId, $paystackVerifiedPayload);
        }

        if ($paymentMode === 'gcb') {
            $checkOutId = session('checkoutid');
            $statusParam = $request->get('statusCode') ?? $request->get('paymentStatus');

            Log::info('GCB Payment Return', [
                'invoice_id' => $invoiceId,
                'checkOutId' => $checkOutId,
                'status_param' => $statusParam,
            ]);

            if ($checkOutId) {
                $verifiedStatus = $this->checkGcbPaymentStatus($checkOutId);
                Log::info('GCB Status Check Result', ['verified_status' => $verifiedStatus]);

                if (!in_array(strtolower((string) $verifiedStatus), ['paid', 'success', 'completed', 'successful', '00'], true)) {
                    return redirect()->route('payment.cancelled')
                        ->with('error', 'Payment was not completed. Status: ' . $verifiedStatus);
                }
            } elseif ($statusParam) {
                if (!in_array(strtolower((string) $statusParam), ['paid', 'success', 'completed', 'successful', '00'], true)) {
                    return redirect()->route('payment.cancelled')
                        ->with('error', 'Payment was not completed. Status: ' . $statusParam);
                }
            } else {
                return redirect()->route('payment.cancelled')
                    ->with('error', 'Payment status could not be verified.');
            }

            $gcbVerifiedPayload = [
                $invoiceId => [
                    'status' => 'paid',
                    'status_reason' => 'Verified via GCB ePay',
                ],
            ];

            return $this->completeRegistration($invoiceId, $gcbVerifiedPayload);
        }

        // Ecobank / default: verify payment status before completing registration
        $paymentStatus = $this->checkPaymentStatus($invoiceId);
        
        Log::info('Ecobank Payment Status Check', [
            'invoice_id' => $invoiceId,
            'payment_status' => $paymentStatus
        ]);
        
        // Extract status from response (response format: {"INVOICE_ID": {"status": "..."}})
        $actualStatus = null;
        if (isset($paymentStatus[$invoiceId])) {
            $actualStatus = $paymentStatus[$invoiceId]['status'] ?? null;
        } elseif (isset($paymentStatus['status'])) {
            $actualStatus = $paymentStatus['status'];
        }
        
        // Check if payment was successful
        $successStatuses = ['paid', 'success', 'completed', 'successful', '00'];
        if (!$actualStatus || !in_array(strtolower((string) $actualStatus), $successStatuses, true)) {
            Log::warning('Payment verification failed', [
                'invoice_id' => $invoiceId,
                'status' => $actualStatus,
                'status_reason' => $paymentStatus[$invoiceId]['status_reason'] ?? 'unknown'
            ]);
            
            return redirect()->route('payment.cancelled')
                ->with('error', 'Payment verification failed. Status: ' . ($actualStatus ?? 'unknown') . '. Please contact support if payment was deducted.');
        }
        
        // Payment verified successfully, complete registration
        return $this->completeRegistration($invoiceId, $paymentStatus);
    }

    /**
     * Handle payment cancellation
     */
    public function paymentCancelled(Request $request)
    {
        return redirect()->route('registration.create')
            ->with('error', 'Payment was cancelled. Please try again.');
    }

    /**
     * Handle IPN notifications
     */
    public function handleIpn(Request $request)
    {
        $invoiceId = $request->get('invoice_id');
        
        if (!$invoiceId) {
            return response('Invalid request', 400);
        }

        // Check payment status
        $paymentStatus = $this->checkPaymentStatus($invoiceId);
        
        // Extract status from response (response format: {"INVOICE_ID": {"status": "..."}})
        $actualStatus = null;
        if (isset($paymentStatus[$invoiceId])) {
            $actualStatus = $paymentStatus[$invoiceId]['status'] ?? null;
        } elseif (isset($paymentStatus['status'])) {
            $actualStatus = $paymentStatus['status'];
        }
        
        Log::info('IPN Notification Received', [
            'invoice_id' => $invoiceId,
            'status' => $actualStatus,
            'full_response' => $paymentStatus
        ]);

        // If payment is successful, complete registration
        $successStatuses = ['paid', 'success', 'completed', 'successful', '00'];
        if ($actualStatus && in_array(strtolower((string) $actualStatus), $successStatuses, true)) {
            $this->completeRegistration($invoiceId, $paymentStatus);
        } else {
            Log::warning('IPN: Payment not successful, registration not completed', [
                'invoice_id' => $invoiceId,
                'status' => $actualStatus,
                'status_reason' => $paymentStatus[$invoiceId]['status_reason'] ?? 'unknown'
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Check payment status with EcobankPay
     */
    private function checkPaymentStatus($invoiceId)
    {
        try {
            $response = Http::get($this->statusCheckUrl, [
                'merchant_key' => $this->merchantKey,
                'invoice_id' => $invoiceId
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('Payment Status Check Response', [
                    'invoice_id' => $invoiceId,
                    'response' => $responseData
                ]);
                return $responseData;
            } else {
                Log::error('Payment Status Check Failed', [
                    'invoice_id' => $invoiceId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return ['status' => 'failed'];
            }
        } catch (\Exception $e) {
            Log::error('Payment Status Check Error', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            return ['status' => 'failed'];
        }
    }

    /**
     * Check payment status with GCB
     */
    private function checkGcbPaymentStatus($checkOutId)
    {
        try {
            $gcbApiKey = env('GCB_API_KEY', 'GCB-Cp98RM7YKey6JUlMyzk1uQALX7IkhQuC');
            $gcbBaseUrl = rtrim(env('GCB_BASE_URL', 'https://epay.gcbltd.com:211'), '/');
            $statusUrl = $gcbBaseUrl . '/transactions/' . $checkOutId . '/status';

            Log::info('Checking GCB Payment Status', [
                'checkOutId' => $checkOutId,
                'url' => $statusUrl,
            ]);

            $response = Http::timeout(30)
                ->withOptions(['verify' => env('GCB_TLS_VERIFY', false)])
                ->withHeaders([
                    'X-Api-Key' => $gcbApiKey,
                    'Accept' => 'application/json',
                ])
                ->get($statusUrl);

            Log::info('GCB Status API Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Return the status field from response (adjust based on actual API response)
                return $data['status'] ?? $data['paymentStatus'] ?? 'unknown';
            } else {
                Log::error('GCB Status Check Failed', [
                    'checkOutId' => $checkOutId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return 'failed';
            }
        } catch (\Exception $e) {
            Log::error('GCB Status Check Error', [
                'checkOutId' => $checkOutId,
                'error' => $e->getMessage()
            ]);
            return 'failed';
        }
    }

    /**
     * Complete user registration after successful payment
     */
    private function completeRegistration($invoiceId, $paymentStatus)
    {
        $pendingData = session('pending_registration');
        $paymentAmount = $pendingData['amount'];
        
        if (!$pendingData || $pendingData['invoice_id'] !== $invoiceId) {
            return redirect()->route('registration.create')
                ->with('error', 'Invalid payment session. Please try again.');
        }

        // Double-check payment status before completing registration
        // Extract status from response (response format: {"INVOICE_ID": {"status": "..."}})
        $actualStatus = null;
        if (isset($paymentStatus[$invoiceId])) {
            $actualStatus = $paymentStatus[$invoiceId]['status'] ?? null;
        } elseif (isset($paymentStatus['status'])) {
            $actualStatus = $paymentStatus['status'];
        }

        // Verify payment was actually successful (00 = GCB success code when echoed through some gateways)
        $successStatuses = ['paid', 'success', 'completed', 'successful', '00'];
        if (!$actualStatus || !in_array(strtolower((string) $actualStatus), $successStatuses, true)) {
            Log::error('Registration blocked: Payment not verified', [
                'invoice_id' => $invoiceId,
                'status' => $actualStatus,
                'status_reason' => $paymentStatus[$invoiceId]['status_reason'] ?? 'unknown',
                'payment_status' => $paymentStatus
            ]);
            
            return redirect()->route('payment.cancelled')
                ->with('error', 'Payment verification failed. Status: ' . ($actualStatus ?? 'unknown') . '. Registration cannot be completed without successful payment.');
        }

        $userData = $pendingData['user_data'];
        $formType = $pendingData['form_type'];
        $isLocal = $pendingData['is_local'];
        Log::info('Complete registration log', [
            'user' => $pendingData['user_data'],
            'formType' => $pendingData['form_type']
           
        ]);


        // Track whether this call is creating a brand new user
        $isNewRegistration = false;

        // Check if user with this invoice_id already exists (prevent duplicate registrations)
        $existingUser = User::where('invoice_id', $invoiceId)->first();
        if ($existingUser) {
            Log::info('User already exists with this invoice_id', [
                'invoice_id' => $invoiceId,
                'user_id' => $existingUser->id,
                'user_email' => $existingUser->email
            ]);

            $pendingPaymentMode = strtolower((string) ($pendingData['payment_mode'] ?? 'ecobank'));
            $successStatuses = ['paid', 'success', 'completed', 'successful', '00'];

            if (in_array($pendingPaymentMode, ['gcb', 'paystack'], true)) {
                $verifyActualStatus = null;
                if (isset($paymentStatus[$invoiceId]['status'])) {
                    $verifyActualStatus = $paymentStatus[$invoiceId]['status'];
                } elseif (isset($paymentStatus['status'])) {
                    $verifyActualStatus = $paymentStatus['status'];
                }
                if ($verifyActualStatus && in_array(strtolower((string) $verifyActualStatus), $successStatuses, true)) {
                    Log::info('Existing user gateway payment verified (payload from callback)', [
                        'invoice_id' => $invoiceId,
                        'user_id' => $existingUser->id,
                        'payment_mode' => $pendingPaymentMode,
                    ]);
                    $user = $existingUser;
                    $pin = $user->pin;
                    $serialNumber = $user->serial_number;
                    $pinExpiry = $user->pin_expires_at;
                } else {
                    Log::error('Existing user gateway payment verification failed', [
                        'invoice_id' => $invoiceId,
                        'user_id' => $existingUser->id,
                        'payment_mode' => $pendingPaymentMode,
                        'status' => $verifyActualStatus,
                    ]);
                    return redirect()->route('payment.cancelled')
                        ->with('error', 'Payment verification failed for this invoice. Please contact support.');
                }
            } else {
                $verifyStatus = $this->checkPaymentStatus($invoiceId);
                $verifyActualStatus = null;
                if (isset($verifyStatus[$invoiceId])) {
                    $verifyActualStatus = $verifyStatus[$invoiceId]['status'] ?? null;
                } elseif (isset($verifyStatus['status'])) {
                    $verifyActualStatus = $verifyStatus['status'];
                }

                if ($verifyActualStatus && in_array(strtolower((string) $verifyActualStatus), $successStatuses, true)) {
                    Log::info('Existing user payment verified successfully', [
                        'invoice_id' => $invoiceId,
                        'user_id' => $existingUser->id
                    ]);
                    $user = $existingUser;
                    $pin = $user->pin;
                    $serialNumber = $user->serial_number;
                    $pinExpiry = $user->pin_expires_at;
                } else {
                    Log::error('Existing user payment verification failed', [
                        'invoice_id' => $invoiceId,
                        'user_id' => $existingUser->id,
                        'status' => $verifyActualStatus
                    ]);
                    return redirect()->route('payment.cancelled')
                        ->with('error', 'Payment verification failed for this invoice. Please contact support.');
                }
            }
        } else {
            // Generate PIN
            $pin = Str::upper(Str::random(8));
            $pinExpiry = Carbon::now()->addMonths(3);
            
            // Generate unique serial number (DUC + random 6 digits)
            $serialNumber = $this->generateUniqueSerialNumber();

            // Create or update user
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['full_name'],
                    'phone' => $userData['country_code'] . $userData['phone'],
                    'nationality' => $userData['nationality'],
                    'form_type_id' => isset($pendingData['form_type']->id) ? $pendingData['form_type']->id : null,
                    'password' => Hash::make($pin),
                    'pin' => $pin,
                    'serial_number' => $serialNumber,
                    'pin_expires_at' => $pinExpiry,
                    'role' => 'user',
                    'invoice_id' => $invoiceId,
                    'payment'=> $paymentAmount
                ]
            );
            
            // Send SMS with PIN for new registrations
            $this->sendSMS($user->phone, $pin, $user->name, $serialNumber);

            // Mark that this is a new registration (not an existing invoice/user)
            $isNewRegistration = $user->wasRecentlyCreated ?? true;
        }

        // Send admin email notification for new registrations only.
        // Any failure here must NOT affect the registration flow.
        if ($isNewRegistration) {
            try {
                $adminEmail = 'alfred.quarshie@delexesuniversity.edu.gh';
                $subject = 'New Applicant Registration';
                $body = "A new applicant has registered.\n\n"
                    . "Name: {$user->name}\n"
                    . "Email: {$user->email}\n"
                    . "Phone: {$user->phone}\n"
                    . "Invoice ID: {$invoiceId}\n"
                    . "Serial Number: {$user->serial_number}\n"
                    . "Payment Amount: {$paymentAmount}\n"
                    . "Registered At: " . now()->toDateTimeString() . "\n";

                Mail::raw($body, function ($message) use ($adminEmail, $subject) {
                    $message->to($adminEmail)->subject($subject);
                });

                Log::info('Admin registration email sent', [
                    'invoice_id' => $invoiceId,
                    'admin_email' => $adminEmail,
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send admin registration email', [
                    'invoice_id' => $invoiceId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clear session data
        session()->forget('pending_registration');

        // Calculate display price and currency
        $displayPrice = $isLocal ? $formType->local_price : $formType->international_price;
        $currency = $isLocal ? '₵' : '$';
        $studentType = $isLocal ? 'local' : 'international';

        // Get PIN and serial number (use existing if user already existed)
        $pin = $user->pin ?? $pin;
        $serialNumber = $user->serial_number ?? $serialNumber;
        $pinExpiry = $user->pin_expires_at ?? $pinExpiry;

        return view('registration.success', [
            'pin' => $pin,
            'serial_number' => $serialNumber,
            'email' => $user->email,
            'user' => $user,
            'pin_expires_at' => $pinExpiry,
            'form_type' => $formType->name,
            'price' => $displayPrice,
            'currency' => $currency,
            'student_type' => $studentType,
            'nationality' => $userData['nationality'],
            'payment_amount' => $pendingData['amount'],
            'payment_currency' => '₵',
            'invoice_id' => $invoiceId,
        ]);
    }

    /**
     * Send SMS notification
     */
    private function sendSMS($phone, $pin, $name, $serialNumber)
    {
        $message = "Hello {$name}, your registration Serial Number is: {$serialNumber} and PIN is: {$pin}. This PIN expires in 3 months. Use this PIN to login to your dashboard.";
        app(\App\Services\SmsService::class)->send($phone, $message);
    }

    /**
     * Get wallet issuer hint based on payment mode
     */
    private function getWalletIssuerHint($paymentMode)
    {
        switch (strtolower($paymentMode)) {
            case 'mtn mobile money':
            case 'mtn':
                return 'mtn';
            case 'vodafone cash':
            case 'vodafone':
                return 'vodafone';
            case 'airteltigo money':
            case 'airteltigo':
                return 'airteltigo';
            case 'visa':
            case 'mastercard':
                return 'card';
            case 'qr':
            case 'qr code':
                return 'qr';
            default:
                return 'mtn'; // Default to MTN
        }
    }

    /**
     * Generate a unique serial number: DUC + 6 random digits
     */
    private function generateUniqueSerialNumber()
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            // Generate DUC + 6 random digits (100000 to 999999)
            $randomNumber = rand(100000, 999999);
            $serialNumber = 'DUC' . $randomNumber;

            // Check if it already exists
            $exists = User::where('serial_number', $serialNumber)->exists();
            
            $attempt++;
            
            if (!$exists) {
                return $serialNumber;
            }
            
            if ($attempt >= $maxAttempts) {
                // Fallback: use timestamp-based unique serial
                return 'DUC' . substr(time(), -6);
            }
        } while ($exists);

        return $serialNumber;
    }
}