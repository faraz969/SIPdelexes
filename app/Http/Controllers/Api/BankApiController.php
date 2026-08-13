<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CountryCodes;
use App\Http\Controllers\Controller;
use App\Models\FormType;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BankApiController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Authenticate a bank user and issue a Sanctum API token.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->isBank()) {
            return response()->json([
                'success' => false,
                'message' => 'Only bank accounts can access this API.',
            ], 403);
        }

        $deviceName = $validated['device_name'] ?? 'bank-api';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'bank' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'bank_name' => $user->bank_name,
                    'branch' => $user->branch,
                ],
            ],
        ]);
    }

    /**
     * Revoke the current API token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Authenticated bank profile.
     */
    public function me(Request $request)
    {
        $bank = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $bank->id,
                'name' => $bank->name,
                'email' => $bank->email,
                'bank_name' => $bank->bank_name,
                'branch' => $bank->branch,
                'logo' => $bank->logo ? asset('storage/' . $bank->logo) : null,
            ],
        ]);
    }

    /**
     * List active form types (for voucher purchase).
     */
    public function formTypes()
    {
        $formTypes = FormType::active()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $formTypes->map(function ($formType) {
                return [
                    'id' => $formType->id,
                    'name' => $formType->name,
                    'local_price' => round((float) $formType->local_price, 2),
                    'international_price' => round((float) $formType->international_price, 2),
                    'conversion_rate' => $formType->conversion_rate
                        ? round((float) $formType->conversion_rate, 4)
                        : null,
                    'description' => $formType->description,
                ];
            }),
        ]);
    }

    /**
     * List countries / nationalities available for applicant creation.
     */
    public function countries()
    {
        return response()->json([
            'success' => true,
            'data' => CountryCodes::getCountries(),
        ]);
    }

    /**
     * Create an applicant (same as bank dashboard "Create User").
     */
    public function createUser(Request $request)
    {
        $bankUser = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'nationality' => 'required|string|max:255',
            'form_type_id' => 'required|exists:form_types,id',
            'voucher_for' => 'nullable|string|max:255',
            'send_sms' => 'nullable|boolean',
        ]);

        if (empty($validated['email'])) {
            $validated['email'] = 'user_' . time() . '_' . rand(1000, 9999) . '@bank.created';
        }

        $formType = FormType::find($validated['form_type_id']);
        if (!$formType || !$formType->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Selected form type is not available.',
            ], 404);
        }

        $isLocal = strtolower(trim($validated['nationality'])) === 'ghana';
        $amount = $isLocal ? (float) $formType->local_price : (float) $formType->international_price;
        if (!$isLocal && $formType->conversion_rate) {
            $amount = $amount * (float) $formType->conversion_rate;
        }
        $amount = round($amount, 2);

        $pin = Str::upper(Str::random(8));
        $pinExpiry = Carbon::now()->addMonths(3);
        $serialNumber = $this->generateUniqueSerialNumber();
        $receiptNumber = $this->generateReceiptNumber();
        $academicYear = SiteSetting::currentAcademicYear();
        $transactionDate = now()->format('Y-m-d H:i:s');

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
            'payment' => json_encode([
                'receipt_number' => $receiptNumber,
                'amount' => $amount,
                'form_type' => $formType->name,
                'transaction_date' => $transactionDate,
                'academic_year' => $academicYear,
                'voucher_for' => $validated['voucher_for'] ?? null,
                'created_via' => 'bank_api',
            ]),
        ]);

        $shouldSendSms = $request->boolean('send_sms', true);
        if ($shouldSendSms && !empty($user->phone)) {
            try {
                $message = "Hello {$user->name}, your registration Serial Number is: {$serialNumber} and PIN is: {$pin}. This PIN expires in 3 months. Use this PIN to login to your dashboard.";
                $this->smsService->send($user->phone, $message);
            } catch (\Exception $e) {
                Log::error('Bank API SMS failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $this->formatCreatedUser($user, $formType, $amount, $receiptNumber, $academicYear, $transactionDate),
        ], 201);
    }

    /**
     * List applicants created by the authenticated bank.
     */
    public function listUsers(Request $request)
    {
        $bankUser = $request->user();
        $perPage = min((int) $request->get('per_page', 20), 100);
        $search = trim((string) $request->get('search', ''));

        $query = User::with('formType')
            ->where('created_by', $bankUser->id)
            ->where('role', 'user');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users->getCollection()->map(function ($user) {
                return $this->formatUserSummary($user);
            })->values(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Get a single applicant created by this bank.
     */
    public function getUser(Request $request, $id)
    {
        $user = $this->findBankCreatedUser($request->user(), $id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatUserSummary($user, true),
        ]);
    }

    /**
     * Get receipt details for a bank-created applicant.
     */
    public function getReceipt(Request $request, $id)
    {
        $bankUser = $request->user();
        $user = $this->findBankCreatedUser($bankUser, $id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $formType = $user->formType;
        if (!$formType) {
            return response()->json([
                'success' => false,
                'message' => 'Form type not found for this user.',
            ], 404);
        }

        $paymentData = $user->payment ? json_decode($user->payment, true) : [];
        $receiptNumber = $paymentData['receipt_number'] ?? $this->generateReceiptNumber();
        $isLocal = strtolower(trim($user->nationality ?? '')) === 'ghana';
        $amount = $paymentData['amount'] ?? ($isLocal ? $formType->local_price : $formType->international_price);

        if (!isset($paymentData['amount']) && !$isLocal && $formType->conversion_rate) {
            $amount = $amount * $formType->conversion_rate;
        }

        $transactionDate = $paymentData['transaction_date'] ?? $user->created_at->format('Y-m-d H:i:s');
        $academicYear = $paymentData['academic_year'] ?? SiteSetting::currentAcademicYear();
        $voucherFor = $paymentData['voucher_for'] ?? null;

        if (!isset($paymentData['receipt_number'])) {
            $user->payment = json_encode([
                'receipt_number' => $receiptNumber,
                'amount' => $amount,
                'form_type' => $formType->name,
                'transaction_date' => $transactionDate,
                'academic_year' => $academicYear,
                'voucher_for' => $voucherFor,
            ]);
            $user->save();
        }

        $paymentDescription = 'Payment of Voucher';
        if ($voucherFor) {
            $paymentDescription .= ' for ' . $voucherFor;
        } else {
            $paymentDescription .= ' for ' . $user->name;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'receipt_number' => $receiptNumber,
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
                'amount_paid' => round((float) $amount, 2),
                'currency' => 'GHS',
                'paid_by' => $user->name,
                'voucher_for' => $voucherFor,
                'receipt_url' => url('/bank/users/' . $user->id . '/receipt'),
            ],
        ]);
    }

    private function findBankCreatedUser(User $bankUser, $id)
    {
        return User::with('formType')
            ->where('id', $id)
            ->where('created_by', $bankUser->id)
            ->where('role', 'user')
            ->first();
    }

    private function formatCreatedUser(User $user, FormType $formType, $amount, $receiptNumber, $academicYear, $transactionDate)
    {
        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'nationality' => $user->nationality,
            'form_type' => [
                'id' => $formType->id,
                'name' => $formType->name,
            ],
            'serial_number' => $user->serial_number,
            'pin' => $user->pin,
            'pin_expires_at' => optional($user->pin_expires_at)->format('Y-m-d H:i:s'),
            'receipt_number' => $receiptNumber,
            'amount_paid' => $amount,
            'currency' => 'GHS',
            'academic_year' => $academicYear,
            'transaction_date' => $transactionDate,
            'created_at' => optional($user->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function formatUserSummary(User $user, $includePin = false)
    {
        $paymentData = $user->payment ? json_decode($user->payment, true) : [];

        $data = [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'nationality' => $user->nationality,
            'form_type' => $user->formType->name ?? null,
            'form_type_id' => $user->form_type_id,
            'serial_number' => $user->serial_number,
            'pin_expires_at' => optional($user->pin_expires_at)->format('Y-m-d H:i:s'),
            'receipt_number' => $paymentData['receipt_number'] ?? null,
            'amount_paid' => isset($paymentData['amount']) ? round((float) $paymentData['amount'], 2) : null,
            'academic_year' => $paymentData['academic_year'] ?? null,
            'voucher_for' => $paymentData['voucher_for'] ?? null,
            'created_at' => optional($user->created_at)->format('Y-m-d H:i:s'),
        ];

        if ($includePin) {
            $data['pin'] = $user->pin;
        }

        return $data;
    }

    private function generateUniqueSerialNumber()
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $randomNumber = rand(100000, 999999);
            $serialNumber = 'DUC' . $randomNumber;
            $exists = User::where('serial_number', $serialNumber)->exists();
            $attempt++;

            if (!$exists) {
                return $serialNumber;
            }

            if ($attempt >= $maxAttempts) {
                return 'DUC' . substr((string) time(), -6);
            }
        } while ($exists);

        return $serialNumber;
    }

    private function generateReceiptNumber()
    {
        return strtoupper(Str::random(20));
    }
}
