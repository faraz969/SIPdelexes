<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionFormDefault;
use Illuminate\Http\Request;

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
            'minimum_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'balance_percentage' => 'nullable|numeric|min:0|max:100',
            'paid_fees_by_date' => 'nullable|date',
            'registration_begins' => 'nullable|date',
            'orientation_new_students' => 'nullable|date',
            'faculty_orientation' => 'nullable|date',
            'lectures_begin' => 'nullable|date',
        ]);

        $settings = AdmissionFormDefault::firstOrNew([
            'academic_year' => $validated['academic_year'],
        ]);

        $settings->fill($validated);
        $settings->save();

        return redirect()
            ->route('admin.admission-form-settings.edit', ['academic_year' => $settings->academic_year])
            ->with('success', 'Admission form defaults updated successfully.');
    }
}

