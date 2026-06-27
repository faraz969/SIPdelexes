<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackService
{
    protected $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    public function getPublicKey(): ?string
    {
        return config('services.paystack.public_key');
    }

    /**
     * Initialize a Paystack transaction.
     */
    public function initialize(string $email, float $amountGhs, string $reference, string $callbackUrl, array $metadata = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paystack is not configured. Please contact support.',
            ];
        }

        $amountInPesewas = (int) round($amountGhs * 100);

        if ($amountInPesewas < 100) {
            return [
                'success' => false,
                'message' => 'Payment amount is too low for Paystack.',
            ];
        }

        try {
            $payload = [
                'email' => $email,
                'amount' => $amountInPesewas,
                'currency' => 'GHS',
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
            ];

            Log::info('Paystack Initialize', [
                'reference' => $reference,
                'amount_pesewas' => $amountInPesewas,
                'email' => $email,
            ]);

            $response = Http::timeout(30)
                ->withToken($this->secretKey)
                ->acceptJson()
                ->post('https://api.paystack.co/transaction/initialize', $payload);

            Log::info('Paystack Initialize Response', [
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => $this->parseErrorMessage($response->json(), $response->body()),
                ];
            }

            $data = $response->json();
            if (!($data['status'] ?? false) || empty($data['data']['authorization_url'])) {
                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Paystack could not start the payment.',
                ];
            }

            return [
                'success' => true,
                'authorization_url' => $data['data']['authorization_url'],
                'reference' => $data['data']['reference'] ?? $reference,
                'access_code' => $data['data']['access_code'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack Initialize Error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while initiating Paystack payment.',
            ];
        }
    }

    /**
     * Verify a Paystack transaction by reference.
     */
    public function verify(string $reference): array
    {
        if (!$this->isConfigured() || empty($reference)) {
            return [
                'success' => false,
                'message' => 'Paystack verification is not available.',
            ];
        }

        try {
            $response = Http::timeout(30)
                ->withToken($this->secretKey)
                ->acceptJson()
                ->get('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));

            Log::info('Paystack Verify Response', [
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => $this->parseErrorMessage($response->json(), $response->body()),
                ];
            }

            $payload = $response->json();
            $transaction = $payload['data'] ?? [];

            if (($payload['status'] ?? false) !== true || ($transaction['status'] ?? '') !== 'success') {
                return [
                    'success' => false,
                    'message' => $transaction['gateway_response'] ?? 'Payment was not successful.',
                    'data' => $transaction,
                ];
            }

            return [
                'success' => true,
                'data' => $transaction,
                'amount_pesewas' => (int) ($transaction['amount'] ?? 0),
                'amount_ghs' => ((int) ($transaction['amount'] ?? 0)) / 100,
                'transaction_id' => $transaction['id'] ?? null,
                'reference' => $transaction['reference'] ?? $reference,
            ];
        } catch (\Exception $e) {
            Log::error('Paystack Verify Error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Could not verify payment with Paystack.',
            ];
        }
    }

    public function parseErrorMessage($json, $rawBody): string
    {
        if (is_array($json) && !empty($json['message']) && is_string($json['message'])) {
            return $json['message'];
        }

        $rawBody = trim((string) $rawBody);

        return $rawBody !== '' ? $rawBody : 'Paystack request failed.';
    }
}
