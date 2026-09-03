<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionFormDefault;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdmissionFormSettingsController extends Controller
{
    /**
     * Show the admission form default settings for a given academic year.
     */
    public function edit(Request $request)
    {
        $academicYear = $request->get('academic_year');

        if ($academicYear) {
            $settings = AdmissionFormDefault::where('academic_year', $academicYear)->first();
        } else {
            $settings = AdmissionFormDefault::orderBy('academic_year', 'desc')->first();
            $academicYear = $settings->academic_year ?? '';
        }

        if (!$settings) {
            $settings = new AdmissionFormDefault(['academic_year' => $academicYear]);
        }

        $availableYears = AdmissionFormDefault::whereNotNull('academic_year')
            ->orderBy('academic_year')
            ->pluck('academic_year')
            ->unique();

        return view('admin.admission-form-settings.edit', [
            'settings' => $settings,
            'academicYear' => $academicYear,
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Update the admission form default settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => 'required|string|max:50',
            'level' => 'nullable|string|max:50',
            'minimum_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'balance_percentage' => 'nullable|numeric|min:0|max:100',
            'paid_fees_by_date' => 'nullable|string|max:255',
            'registration_begins' => 'nullable|string|max:255',
            'orientation_new_students' => 'nullable|string|max:255',
            'faculty_orientation' => 'nullable|string|max:255',
            'lectures_begin' => 'nullable|string|max:255',
            'registrar_name' => 'nullable|string|max:255',
            'registrar_signature' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'bank_account_no' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'bank_name_2' => 'nullable|string|max:255',
            'bank_account_name_2' => 'nullable|string|max:255',
            'bank_branch_2' => 'nullable|string|max:255',
            'bank_account_no_2' => 'nullable|string|max:255',
            'payment_reference_2' => 'nullable|string|max:255',
        ]);

        $settings = AdmissionFormDefault::firstOrNew([
            'academic_year' => $validated['academic_year'],
        ]);

        if ($request->hasFile('registrar_signature') && $request->file('registrar_signature')->isValid()) {
            if ($settings->registrar_signature && Storage::disk('public')->exists($settings->registrar_signature)) {
                Storage::disk('public')->delete($settings->registrar_signature);
            }
            $validated['registrar_signature'] = $request->file('registrar_signature')->store('registrar_signatures', 'public');
        } else {
            unset($validated['registrar_signature']);
        }

        $settings->fill($validated);
        $settings->save();

        return redirect()
            ->route('admin.admission-form-settings.edit', ['academic_year' => $settings->academic_year])
            ->with('success', 'Admission form defaults updated successfully.');
    }
}

