<?php

namespace App\Services;

use App\Models\FormType;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Support\Str;

class BankVoucherService
{
    /**
     * Calculate voucher amount in GHS from form type + nationality.
     */
    public function calculateAmount(FormType $formType, string $nationality): float
    {
        $isLocal = strtolower(trim($nationality)) === 'ghana';
        $amount = $isLocal ? (float) $formType->local_price : (float) $formType->international_price;

        if (!$isLocal && $formType->conversion_rate) {
            $amount = $amount * (float) $formType->conversion_rate;
        }

        return round($amount, 2);
    }

    /**
     * Generate a unique invoice ID (same style as online registration: DUC...).
     */
    public function generateUniqueInvoiceId(): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $invoiceId = 'DUC' . time() . rand(100, 999);
            $exists = User::where('invoice_id', $invoiceId)->exists();
            $attempt++;

            if (!$exists) {
                return $invoiceId;
            }

            usleep(1000);
        } while ($attempt < $maxAttempts);

        return 'DUC' . time() . Str::upper(Str::random(4));
    }

    public function generateUniqueSerialNumber(): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $serialNumber = 'DUC' . rand(100000, 999999);
            $exists = User::where('serial_number', $serialNumber)->exists();
            $attempt++;

            if (!$exists) {
                return $serialNumber;
            }
        } while ($attempt < $maxAttempts);

        return 'DUC' . substr((string) time(), -6);
    }

    public function generateReceiptNumber(): string
    {
        return strtoupper(Str::random(20));
    }

    /**
     * Build payment JSON payload stored on users.payment for bank-created applicants.
     */
    public function buildPaymentPayload(
        string $invoiceId,
        float $amount,
        FormType $formType,
        ?string $voucherFor = null,
        string $createdVia = 'bank_portal',
        ?string $receiptNumber = null,
        ?string $transactionDate = null,
        ?string $academicYear = null
    ): array {
        return [
            'invoice_id' => $invoiceId,
            'receipt_number' => $receiptNumber ?: $this->generateReceiptNumber(),
            'amount' => $amount,
            'form_type' => $formType->name,
            'transaction_date' => $transactionDate ?: now()->format('Y-m-d H:i:s'),
            'academic_year' => $academicYear ?: SiteSetting::currentAcademicYear(),
            'voucher_for' => $voucherFor,
            'created_via' => $createdVia,
        ];
    }

    /**
     * Resolve admission/voucher amount from users.payment (numeric or JSON).
     */
    public static function resolvePaymentAmount($payment): ?float
    {
        if ($payment === null || $payment === '') {
            return null;
        }

        if (is_numeric($payment)) {
            return round((float) $payment, 2);
        }

        if (is_string($payment)) {
            $decoded = json_decode($payment, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && isset($decoded['amount'])) {
                return round((float) $decoded['amount'], 2);
            }
            if (is_numeric($payment)) {
                return round((float) $payment, 2);
            }
        }

        if (is_array($payment) && isset($payment['amount'])) {
            return round((float) $payment['amount'], 2);
        }

        return null;
    }

    /**
     * Decode payment JSON if present.
     */
    public static function resolvePaymentData($payment): array
    {
        if (is_array($payment)) {
            return $payment;
        }

        if (is_string($payment) && $payment !== '') {
            $decoded = json_decode($payment, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
