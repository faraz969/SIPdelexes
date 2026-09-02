<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\FormType;
use App\Models\User;

use App\Helpers\CountryCodes;
use Illuminate\Support\Facades\Http;

class RegistrationController extends Controller
{
    public function show()
    {
        $formTypes = FormType::active()->orderBy('name')->get();
        $countries = CountryCodes::getCountries();
        return view('registration.create', compact('formTypes', 'countries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'country_code' => 'required|string',
            'phone' => 'required|string|max:20',
            'nationality' => 'required|string|max:255',
            'form_type' => 'required|exists:form_types,id',
        ]);

        // Automatically determine student type based on nationality
        $validated['student_type'] = $validated['nationality'] === 'Ghana' ? 'local' : 'international';

        $pin = Str::upper(Str::random(8));
        $serialNumber = $this->generateUniqueSerialNumber();
        $pinExpiry = Carbon::now()->addMonths(3);

        $user = User::updateOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $validated['full_name'],
                'phone' => $validated['country_code'] . $validated['phone'],
                'nationality' => $validated['nationality'],
                'form_type_id' => (int) $validated['form_type'],
                'password' => Hash::make($pin),
                'pin' => $pin,
                'serial_number' => $serialNumber,
                'pin_expires_at' => $pinExpiry,
                'role' => 'user',
            ]
        );

        // Get form type details
        $formType = FormType::find($validated['form_type']);
        $price = $validated['student_type'] === 'local' ? $formType->local_price : $formType->international_price;
        $currency = $validated['student_type'] === 'local' ? '₵' : '$';

        // Send SMS with PIN
        $this->sendSMS($validated['country_code'] . $validated['phone'], $pin, $validated['full_name']);

        return view('registration.success', [
            'pin' => $pin,
            'serial_number' => $serialNumber,
            'email' => $user->email,
            'user' => $user,
            'pin_expires_at' => $pinExpiry,
            'form_type' => $formType->name,
            'price' => $price,
            'currency' => $currency,
            'student_type' => $validated['student_type'],
            'nationality' => $validated['nationality'],
        ]);
    }

    private function sendSMS($phoneNumber, $pin, $fullName)
    {
        $message = "Hello {$fullName}, your registration PIN is: {$pin}. This PIN expires in 3 months. Use this PIN to login to your dashboard.";
        app(\App\Services\SmsService::class)->send($phoneNumber, $message);
    }

    /**
     * Generate a unique serial number: DUC + 6 random digits
     *
     * @return string
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
