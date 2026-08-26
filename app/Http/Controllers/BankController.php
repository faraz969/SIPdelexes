<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\FormType;
use App\Helpers\CountryCodes;
use App\Models\SiteSetting;
use App\Services\BankVoucherService;
use Carbon\Carbon;

class BankController extends Controller
{
    protected $voucherService;

    public function __construct(BankVoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    public function dashboard(Request $request)
    {
        $bankUser = Auth::user();
        
        // Get only users created by this bank user
        $query = User::where('created_by', $bankUser->id);
        
        // Apply search filter if provided
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%')
                  ->orWhere('phone', 'like', '%' . $searchTerm . '%')
                  ->orWhere('invoice_id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('serial_number', 'like', '%' . $searchTerm . '%');
            });
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        
        return view('bank.dashboard', compact('users', 'bankUser'));
    }

    public function createUser()
    {
        $formTypes = FormType::active()->orderBy('name')->get();
        $countries = CountryCodes::getCountries();
        return view('bank.create-user', compact('formTypes', 'countries'));
    }

    public function storeUser(Request $request)
    {
        $bankUser = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'nationality' => 'required|string|max:255',
            'form_type_id' => 'required|exists:form_types,id',
            'voucher_for' => 'nullable|string|max:255',
        ]);

        // Generate a unique email if not provided (to satisfy unique constraint)
        if (empty($validated['email'])) {
            $validated['email'] = 'user_' . time() . '_' . rand(1000, 9999) . '@bank.created';
        }

        $formType = FormType::findOrFail($validated['form_type_id']);
        $amount = $this->voucherService->calculateAmount($formType, $validated['nationality']);
        $invoiceId = $this->voucherService->generateUniqueInvoiceId();
        $serialNumber = $this->voucherService->generateUniqueSerialNumber();
        $receiptNumber = $this->voucherService->generateReceiptNumber();
        $academicYear = SiteSetting::currentAcademicYear();
        $transactionDate = now()->format('Y-m-d H:i:s');

        $pin = Str::upper(Str::random(8));
        $pinExpiry = Carbon::now()->addMonths(3);

        $paymentPayload = $this->voucherService->buildPaymentPayload(
            $invoiceId,
            $amount,
            $formType,
            $validated['voucher_for'] ?? null,
            'bank_portal',
            $receiptNumber,
            $transactionDate,
            $academicYear
        );

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'nationality' => $validated['nationality'],
            'form_type_id' => $validated['form_type_id'],
            'role' => 'user',
            'password' => Hash::make($pin),
            'pin' => $pin,
            'serial_number' => $serialNumber,
            'pin_expires_at' => $pinExpiry,
            'created_by' => $bankUser->id,
            'invoice_id' => $invoiceId,
            'payment' => json_encode($paymentPayload),
        ]);

        return redirect()->route('bank.dashboard')
            ->with('success', 'User created successfully. Invoice ID: ' . $invoiceId . ', Amount: GHS ' . number_format($amount, 2) . ', PIN: ' . $pin . ', Serial Number: ' . $serialNumber);
    }

    public function downloadReceipt($userId)
    {
        $bankUser = Auth::user();
        $user = User::where('id', $userId)
            ->where('created_by', $bankUser->id)
            ->firstOrFail();
        
        $formType = $user->formType;
        if (!$formType) {
            return redirect()->route('bank.dashboard')
                ->with('error', 'Form type not found for this user.');
        }

        $paymentData = BankVoucherService::resolvePaymentData($user->payment);
        $amount = BankVoucherService::resolvePaymentAmount($user->payment);

        if ($amount === null) {
            $amount = $this->voucherService->calculateAmount($formType, $user->nationality ?? 'Ghana');
        }

        $transactionDate = $paymentData['transaction_date'] ?? $user->created_at->format('Y-m-d H:i:s');
        $academicYear = $paymentData['academic_year'] ?? SiteSetting::currentAcademicYear();
        $voucherFor = $paymentData['voucher_for'] ?? null;
        $receiptNumber = $paymentData['receipt_number'] ?? $this->voucherService->generateReceiptNumber();

        // Ensure invoice_id exists (backfill for older bank-created users)
        $invoiceId = $user->invoice_id ?: ($paymentData['invoice_id'] ?? null);
        if (!$invoiceId) {
            $invoiceId = $this->voucherService->generateUniqueInvoiceId();
            $user->invoice_id = $invoiceId;
        }

        $needsSave = $user->isDirty('invoice_id')
            || !isset($paymentData['receipt_number'])
            || !isset($paymentData['amount'])
            || !isset($paymentData['invoice_id']);

        if ($needsSave) {
            $user->payment = json_encode($this->voucherService->buildPaymentPayload(
                $invoiceId,
                (float) $amount,
                $formType,
                $voucherFor,
                $paymentData['created_via'] ?? 'bank_portal',
                $receiptNumber,
                $transactionDate,
                $academicYear
            ));
            $user->save();
        }

        $paymentDescription = 'Payment of Voucher';
        if ($voucherFor) {
            $paymentDescription .= ' for ' . $voucherFor;
        } else {
            $paymentDescription .= ' for ' . $user->name;
        }

        $data = [
            'receipt_number' => $receiptNumber,
            'invoice_id' => $invoiceId,
            'institution' => 'Delexes University College',
            'form_type' => $formType->name,
            'serial_number' => $user->serial_number,
            'pin' => $user->pin,
            'bank_name' => $bankUser->bank_name,
            'branch' => $bankUser->branch,
            'bank_logo' => $bankUser->logo ? asset('storage/' . $bankUser->logo) : null,
            'academic_year' => $academicYear,
            'transaction_date' => $transactionDate,
            'payment_description' => $paymentDescription,
            'amount_paid' => number_format((float) $amount, 2),
            'paid_by' => $user->name,
            'voucher_for' => $voucherFor,
        ];

        return view('bank.receipt', $data);
    }
}
