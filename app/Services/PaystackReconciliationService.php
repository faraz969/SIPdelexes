<?php

namespace App\Services;

use App\Http\Controllers\SIPPaymentController;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PaystackReconciliationService
{
    protected PaystackService $paystackService;

    /** Terminal Paystack statuses that should become failed in SIP. */
    public const FAILED_STATUSES = ['abandoned', 'failed', 'reversed'];

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    /**
     * Re-check pending/processing Paystack payments and update SIP status.
     * - abandoned / failed / reversed → failed
     * - success → finalize + ERP sync (same as Verify & Sync)
     * Skips very recent payments so in-progress checkouts are not interrupted.
     *
     * @return array{checked:int,failed:int,completed:int,skipped:int,errors:int}
     */
    public function reconcilePending(?int $studentId = null, int $limit = 40, int $minAgeMinutes = 5): array
    {
        $result = [
            'checked' => 0,
            'failed' => 0,
            'completed' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        if (!$this->paystackService->isConfigured()) {
            return $result;
        }

        $query = Payment::query()
            ->where('payment_method', 'paystack')
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('payment_reference')
            ->where('created_at', '<=', now()->subMinutes($minAgeMinutes))
            ->orderBy('created_at', 'asc')
            ->limit($limit);

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $payments = $query->get();

        foreach ($payments as $payment) {
            $result['checked']++;

            try {
                $outcome = $this->reconcileOne($payment);
                if ($outcome === 'failed') {
                    $result['failed']++;
                } elseif ($outcome === 'completed') {
                    $result['completed']++;
                } else {
                    $result['skipped']++;
                }
            } catch (\Throwable $e) {
                $result['errors']++;
                Log::error('Paystack reconcile error', [
                    'payment_id' => $payment->id,
                    'reference' => $payment->payment_reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    /**
     * Throttled reconcile for page loads (avoids hammering Paystack on refresh).
     */
    public function reconcilePendingIfNeeded(?int $studentId = null, int $throttleSeconds = 120): array
    {
        $cacheKey = 'paystack_reconcile_' . ($studentId ?? 'all');

        if (!Cache::add($cacheKey, 1, $throttleSeconds)) {
            return [
                'checked' => 0,
                'failed' => 0,
                'completed' => 0,
                'skipped' => 0,
                'errors' => 0,
                'throttled' => true,
            ];
        }

        return $this->reconcilePending($studentId);
    }

    /**
     * @return 'failed'|'completed'|'skipped'
     */
    public function reconcileOne(Payment $payment): string
    {
        if (
            $payment->payment_method !== 'paystack'
            || !in_array($payment->status, ['pending', 'processing'], true)
            || empty($payment->payment_reference)
        ) {
            return 'skipped';
        }

        $verification = $this->paystackService->verify($payment->payment_reference);

        if ($verification['success']) {
            $verifiedAmount = round((float) ($verification['amount_ghs'] ?? 0), 2);
            $expectedAmount = round((float) $payment->amount, 2);
            $charged = round((float) ($verification['charged_amount_ghs'] ?? 0), 2);
            $fees = round(((float) ($verification['fees_pesewas'] ?? 0)) / 100, 2);
            $netCharged = round($charged - $fees, 2);

            if (
                $verifiedAmount > 0
                && abs($verifiedAmount - $expectedAmount) > 0.01
                && abs($netCharged - $expectedAmount) > 0.01
            ) {
                Log::warning('Paystack reconcile amount mismatch', [
                    'payment_id' => $payment->id,
                    'expected' => $expectedAmount,
                    'verified' => $verifiedAmount,
                    'net_charged' => $netCharged,
                ]);

                return 'skipped';
            }

            app(SIPPaymentController::class)->finalizePayment($payment, $verification['data'] ?? []);

            return 'completed';
        }

        $paystackStatus = $verification['data']['status']
            ?? $verification['paystack_status']
            ?? null;
        $message = $verification['message'] ?? 'Payment was not successful.';

        if (!in_array($paystackStatus, self::FAILED_STATUSES, true)) {
            // e.g. ongoing — leave pending/processing
            return 'skipped';
        }

        $payment->update([
            'status' => 'failed',
            'payment_details' => array_merge($payment->payment_details ?? [], [
                'paystack' => $verification['data'] ?? [],
                'verification_error' => $message,
                'marked_failed_by' => 'auto_reconcile',
                'marked_failed_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Paystack payment marked failed by reconcile', [
            'payment_id' => $payment->id,
            'reference' => $payment->payment_reference,
            'paystack_status' => $paystackStatus,
        ]);

        return 'failed';
    }
}
