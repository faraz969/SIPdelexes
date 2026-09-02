<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS using the configured primary provider, with the other as fallback.
     *
     * Set SMS_PRIMARY_PROVIDER=nalo|arkesel in .env (default: nalo).
     * After changing it, run: php artisan config:clear
     */
    public function send($phone, $message)
    {
        if (empty($phone) || empty($message)) {
            Log::warning('SMS skipped: missing phone or message');
            return false;
        }

        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        $providers = $this->providerOrder();

        Log::info('SMS send started', [
            'phone' => $cleanPhone,
            'primary' => $providers[0] ?? null,
            'fallback' => $providers[1] ?? null,
        ]);

        foreach ($providers as $index => $provider) {
            $isPrimary = $index === 0;
            $label = $isPrimary ? 'primary' : 'fallback';

            try {
                $sent = $provider === 'nalo'
                    ? $this->sendViaNalo($cleanPhone, $message, $label)
                    : $this->sendViaArkesel($cleanPhone, $message, $label);

                if ($sent) {
                    return true;
                }

                if ($isPrimary) {
                    Log::warning(ucfirst($provider) . ' SMS failed, trying fallback provider');
                }
            } catch (\Throwable $e) {
                Log::error(ucfirst($provider) . " SMS exception ({$label})", [
                    'phone' => $cleanPhone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::error('Both SMS providers failed', ['phone' => $phone]);

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function providerOrder(): array
    {
        $primary = strtolower((string) config('services.sms.primary', 'nalo'));

        if ($primary === 'arkesel') {
            return ['arkesel', 'nalo'];
        }

        return ['nalo', 'arkesel'];
    }

    protected function sendViaNalo(string $cleanPhone, string $message, string $label): bool
    {
        $naloPhone = $this->normalizePhoneForNalo($cleanPhone);
        $naloKey = config('services.sms.nalo.key');
        $naloSenderId = config('services.sms.nalo.sender_id', 'DELEXESUC');

        Log::info("Attempting SMS via Nalo ({$label})", [
            'phone' => $naloPhone,
            'original_phone' => $cleanPhone,
            'sender' => $naloSenderId,
        ]);

        $naloResponse = Http::timeout(10)
            ->post('https://sms.nalosolutions.com/smsbackend/Resl_Nalo/send-message/', [
                'key' => $naloKey,
                'msisdn' => $naloPhone,
                'message' => $message,
                'sender_id' => $naloSenderId,
            ]);

        Log::info("Nalo SMS API Response ({$label})", [
            'phone' => $naloPhone,
            'status' => $naloResponse->status(),
            'response' => $naloResponse->body(),
        ]);

        if (!$naloResponse->successful()) {
            return false;
        }

        $responseData = $naloResponse->json();
        if (is_array($responseData) && isset($responseData['status'], $responseData['job_id'])) {
            Log::info("SMS sent successfully via Nalo ({$label})", [
                'job_id' => $responseData['job_id'],
                'status_code' => $responseData['status'],
            ]);
            return true;
        }

        return false;
    }

    protected function sendViaArkesel(string $cleanPhone, string $message, string $label): bool
    {
        $arkeselTo = $this->normalizePhoneForArkesel($cleanPhone);
        $arkeselApiKey = config('services.sms.arkesel.key');
        $arkeselSenderId = config('services.sms.arkesel.sender_id', 'DELEXESUC');

        Log::info("Attempting SMS via Arkesel ({$label})", [
            'to' => $arkeselTo,
            'original_phone' => $cleanPhone,
            'sender' => $arkeselSenderId,
        ]);

        $arkeselResponse = Http::timeout(10)
            ->get('https://sms.arkesel.com/sms/api', [
                'action' => 'send-sms',
                'api_key' => $arkeselApiKey,
                'to' => $arkeselTo,
                'from' => $arkeselSenderId,
                'sms' => $message,
            ]);

        Log::info("Arkesel SMS API Response ({$label})", [
            'to' => $arkeselTo,
            'status' => $arkeselResponse->status(),
            'response' => $arkeselResponse->body(),
        ]);

        if (!$arkeselResponse->successful()) {
            return false;
        }

        $responseData = $arkeselResponse->json();
        if (is_array($responseData) && $this->isArkeselSmsSuccess($responseData)) {
            Log::info("SMS sent successfully via Arkesel ({$label})", [
                'to' => $arkeselTo,
                'payload' => $responseData,
            ]);
            return true;
        }

        return false;
    }

    protected function isArkeselSmsSuccess(array $data): bool
    {
        $code = $data['code'] ?? null;
        if (in_array($code, ['ok', 'OK', '1000', 1000, 200, '200'], true)) {
            return true;
        }

        $status = strtolower((string) ($data['status'] ?? ''));
        if (in_array($status, ['success', 'ok'], true)) {
            return true;
        }

        $message = strtolower((string) ($data['message'] ?? ''));
        if ($message !== '' && strpos($message, 'success') !== false && strpos($message, 'fail') === false) {
            return true;
        }

        return false;
    }

    protected function normalizePhoneForNalo(string $cleanPhone): string
    {
        if (strpos($cleanPhone, '+233') === 0) {
            return '0' . substr($cleanPhone, 4);
        }
        if (strpos($cleanPhone, '233') === 0 && strpos($cleanPhone, '+') !== 0) {
            return '0' . substr($cleanPhone, 3);
        }

        return $cleanPhone;
    }

    /**
     * Arkesel expects recipients like 233XXXXXXXXX (no leading +).
     */
    protected function normalizePhoneForArkesel(string $cleanPhone): string
    {
        if (strpos($cleanPhone, '+233') === 0) {
            return substr($cleanPhone, 1);
        }
        if (strpos($cleanPhone, '233') === 0) {
            return $cleanPhone;
        }
        if (strpos($cleanPhone, '0') === 0 && strlen($cleanPhone) >= 10) {
            return '233' . substr($cleanPhone, 1);
        }

        return $cleanPhone;
    }
}
