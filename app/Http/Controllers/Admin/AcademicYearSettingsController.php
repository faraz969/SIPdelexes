<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class AcademicYearSettingsController extends Controller
{
    public function edit()
    {
        return view('admin.academic-year-settings.edit', [
            'academic_year' => SiteSetting::currentAcademicYear(),
            'transcript_fee' => SiteSetting::transcriptFeeAmount(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => 'required|string|max:50',
            'transcript_fee' => 'required|numeric|min:0',
        ]);

        SiteSetting::set(SiteSetting::KEY_CURRENT_ACADEMIC_YEAR, $validated['academic_year']);
        SiteSetting::set(SiteSetting::KEY_TRANSCRIPT_FEE, number_format((float) $validated['transcript_fee'], 2, '.', ''));

        return redirect()
            ->route('admin.academic-year-settings.edit')
            ->with('success', 'Academic settings updated successfully.');
    }
}
