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
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => 'required|string|max:50',
        ]);

        SiteSetting::set(SiteSetting::KEY_CURRENT_ACADEMIC_YEAR, $validated['academic_year']);

        return redirect()
            ->route('admin.academic-year-settings.edit')
            ->with('success', 'Academic year updated successfully.');
    }
}
