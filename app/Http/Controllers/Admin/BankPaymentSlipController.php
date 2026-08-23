<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class BankPaymentSlipController extends Controller
{
    /**
     * List submitted bank payment slips from SIP invoice payments.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['student.user', 'invoice'])
            ->where('payment_method', 'bank')
            ->whereNotNull('bank_slip_path')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('payment_reference', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('student_id', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                        $invoiceQuery->where('invoice_number', 'like', "%{$search}%");
                    });
            });
        }

        $slips = $query->paginate(20)->withQueryString();

        $baseQuery = Payment::where('payment_method', 'bank')->whereNotNull('bank_slip_path');

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'failed' => (clone $baseQuery)->where('status', 'failed')->count(),
            'total_pending_amount' => (clone $baseQuery)->where('status', 'pending')->sum('amount'),
        ];

        return view('admin.bank-payment-slips.index', compact('slips', 'stats'));
    }

    /**
     * View a single bank payment slip submission.
     */
    public function show(Payment $payment)
    {
        if (!$this->isBankSlipPayment($payment)) {
            abort(404, 'Bank payment slip not found.');
        }

        $payment->load(['student.user', 'invoice']);

        return view('admin.bank-payment-slips.show', compact('payment'));
    }

    /**
     * View or download the uploaded bank slip file.
     */
    public function slip(Payment $payment)
    {
        if (!$this->isBankSlipPayment($payment)) {
            abort(404, 'Bank payment slip not found.');
        }

        $path = storage_path('app/public/' . ltrim($payment->bank_slip_path, '/'));
        if (!file_exists($path)) {
            abort(404, 'Slip file not found.');
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $filename = $payment->payment_details['original_filename'] ?? basename($payment->bank_slip_path);

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    protected function isBankSlipPayment(Payment $payment): bool
    {
        return $payment->payment_method === 'bank' && !empty($payment->bank_slip_path);
    }
}
