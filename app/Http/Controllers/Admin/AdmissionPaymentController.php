<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BankVoucherService;
use Illuminate\Http\Request;

class AdmissionPaymentController extends Controller
{
    /**
     * Display successful admission-form payment registrations.
     */
    public function index(Request $request)
    {
        $query = User::with('formType')
            ->where('role', 'user')
            ->where(function ($q) {
                $q->whereNotNull('invoice_id')
                    ->orWhereNotNull('payment');
            });

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('invoice_id', 'like', "%{$search}%");
            });
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        $statsQuery = User::where('role', 'user')
            ->where(function ($q) {
                $q->whereNotNull('invoice_id')
                    ->orWhereNotNull('payment');
            });

        $totalAmount = 0.0;
        User::where('role', 'user')
            ->whereNotNull('payment')
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$totalAmount) {
                foreach ($users as $user) {
                    $amount = BankVoucherService::resolvePaymentAmount($user->payment);
                    if ($amount !== null) {
                        $totalAmount += $amount;
                    }
                }
            });

        $stats = [
            'total_records' => (clone $statsQuery)->count(),
            'with_invoice' => (clone $statsQuery)->whereNotNull('invoice_id')->count(),
            'total_amount' => $totalAmount,
            'today_records' => (clone $statsQuery)->whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('admin.admission-payments.index', compact('payments', 'stats'));
    }
}
