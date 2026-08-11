<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS via Arkesel, with Nalo as fallback.
     */
    public function send($phone, $message)
    {
        if (empty($phone) || empty($message)) {
            Log::warning('SMS skipped: missing phone or message');
            return false;
        }

        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        $arkeselTo = $this->normalizePhoneForArkesel($cleanPhone);

        $naloPhone = $cleanPhone;
        if (strpos($cleanPhone, '+233') === 0) {
            $naloPhone = '0' . substr($cleanPhone, 4);
        } elseif (strpos($cleanPhone, '233') === 0 && strpos($cleanPhone, '+') !== 0) {
            $naloPhone = '0' . substr($cleanPhone, 3);
        }

        try {
            $arkeselApiKey = env('ARKESEL_SMS_KEY', 'Ok1GNWlYWFB0VHI1NHJZUUQ=');
            $arkeselSenderId = env('ARKESEL_SENDER_ID', 'DELEXESUC');

            Log::info('Attempting SMS via Arkesel API', [
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

            Log::info('Arkesel SMS API Response', [
                'to' => $arkeselTo,
                'status' => $arkeselResponse->status(),
                'response' => $arkeselResponse->body(),
            ]);

            if ($arkeselResponse->successful()) {
                $responseData = $arkeselResponse->json();
                if (is_array($responseData)) {
                    $code = isset($responseData['code']) ? strtolower((string) $responseData['code']) : '';
                    $status = isset($responseData['status']) ? strtolower((string) $responseData['status']) : '';
                    if ($code === 'ok' || $status === 'success') {
                        return true;
                    }
                }
            }

            Log::warning('Arkesel SMS API failed or returned error, trying backup Nalo API');
        } catch (\Exception $e) {
            Log::error('Arkesel SMS API Exception', [
                'to' => $arkeselTo ?? $cleanPhone,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $naloKey = env('NALO_SMS_KEY', 'LNMKky07fqvxVO6IK33I7UvuWMVXDR_sZnf8bDRnG7qu2ErL3vTM1farB5UYw26L');
            $naloSenderId = env('NALO_SENDER_ID', 'DELEXESUC');

            Log::info('Attempting SMS via Nalo API (Backup)', [
                'phone' => $naloPhone,
                'original_phone' => $cleanPhone,
            ]);

            $naloResponse = Http::timeout(10)
                ->post('https://sms.nalosolutions.com/smsbackend/Resl_Nalo/send-message/', [
                    'key' => $naloKey,
                    'msisdn' => $naloPhone,
                    'message' => $message,
                    'sender_id' => $naloSenderId,
                ]);

            Log::info('Nalo SMS API Response (Backup)', [
                'phone' => $naloPhone,
                'status' => $naloResponse->status(),
                'response' => $naloResponse->body(),
            ]);

            if ($naloResponse->successful()) {
                $responseData = $naloResponse->json();
                if (isset($responseData['status']) && isset($responseData['job_id'])) {
                    return true;
                }
            }

            Log::warning('Nalo SMS backup failed');
        } catch (\Exception $e) {
            Log::error('Nalo SMS API Exception (Backup)', [
                'phone' => $naloPhone,
                'error' => $e->getMessage(),
            ]);
        }

        Log::error('Both SMS providers failed', ['phone' => $phone]);

        return false;
    }

    private function normalizePhoneForArkesel($cleanPhone)
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
