<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\AdmissionForm;
use App\Models\AdmissionFormDefault;
use App\Models\SiteSetting;
use App\Models\Student;
use Illuminate\Support\Facades\Redirect;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $query = Application::with(['user', 'examRecords.subjects']);
        
        // Apply search filter if provided
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('application_number', 'like', '%' . $searchTerm . '%')
                  ->orWhere('academic_year', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                      $userQuery->where('name', 'like', '%' . $searchTerm . '%')
                                 ->orWhere('email', 'like', '%' . $searchTerm . '%')
                                 ->orWhere('phone', 'like', '%' . $searchTerm . '%')
                                 ->orWhere('serial_number', 'like', '%' . $searchTerm . '%');
                  });
            });
        }
        
        $applications = $query->latest()->paginate(20)->withQueryString();
        
        return view('admin.dashboard', compact('applications'));
    }

    public function show($id)
    {
        $application = Application::with(['user', 'department'])->findOrFail($id);
        $form = AdmissionForm::where('application_id', $application->id)->first();
        $examRecords = \App\Models\ExamRecord::with('subjects')
            ->where('application_id', $application->id)
            ->get();

        $programFields = $application->getProgramFieldsFromData();

        $availableAcademicYears = AdmissionFormDefault::whereNotNull('academic_year')
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year')
            ->push(SiteSetting::currentAcademicYear(), $application->academic_year)
            ->filter()
            ->unique()
            ->values();

        $canEditAcademicProgram = $application->registrar_status !== 'approved'
            && !Student::where('application_id', $application->id)->exists();

        return view('admin.show', compact(
            'application',
            'form',
            'examRecords',
            'programFields',
            'availableAcademicYears',
            'canEditAcademicProgram'
        ));
    }

    public function updateAcademicProgram(Request $request, $id)
    {
        $application = Application::findOrFail($id);

        if ($application->registrar_status === 'approved') {
            return Redirect::route('admin.applications.show', $application->id)
                ->with('error', 'Cannot update academic year or program after registrar approval.');
        }

        if (Student::where('application_id', $application->id)->exists()) {
            return Redirect::route('admin.applications.show', $application->id)
                ->with('error', 'Cannot update academic year or program after a student account has been created.');
        }

        $validated = $request->validate([
            'academic_year' => 'required|string|max:50',
            'programs' => 'nullable|array',
            'programs.*' => 'nullable|string|max:255',
            'program_modes' => 'nullable|array',
            'program_modes.*' => 'nullable|string|max:255',
        ]);

        $data = is_array($application->data) ? $application->data : [];

        foreach ($request->input('programs', []) as $key => $name) {
            if (strpos($key, 'prog_') !== 0 || strpos($key, '_mode') !== false) {
                continue;
            }

            $name = trim((string) $name);
            if ($name === '') {
                unset($data[$key], $data[$key . '_mode']);
                continue;
            }

            $data[$key] = $name;

            $mode = $request->input('program_modes.' . $key);
            if ($mode !== null && trim((string) $mode) !== '') {
                $data[$key . '_mode'] = trim((string) $mode);
            } else {
                unset($data[$key . '_mode']);
            }
        }

        $application->academic_year = $validated['academic_year'];
        $application->data = $data;
        $application->syncDepartmentsFromProgramData();
        $application->save();

        if ($form = AdmissionForm::where('application_id', $application->id)->first()) {
            $this->syncAdmissionFormFromData($form, $data);
            $form->save();
        }

        return Redirect::route('admin.applications.show', $application->id)
            ->with('status', 'Academic year and program names updated. These values will be used when the registrar approves and syncs to ERPNext.');
    }

    private function syncAdmissionFormFromData(AdmissionForm $form, array $data): void
    {
        $legacyMap = [
            1 => ['prog_eng', 'prog_eng_mode'],
            2 => ['prog_focis', 'prog_focis_mode'],
            3 => ['prog_business', 'prog_business_mode'],
        ];

        foreach ($legacyMap as $deptId => $fields) {
            $key = 'prog_' . $deptId;
            $form->{$fields[0]} = $data[$key] ?? null;
            $form->{$fields[1]} = $data[$key . '_mode'] ?? null;
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:successful,not_successful'
        ]);
        $application = Application::findOrFail($id);
        $application->status = $request->input('status') === 'successful' ? 'successful' : 'not_successful';
        $application->save();
        return Redirect::route('admin.dashboard')->with('status', 'Application status updated');
    }

    public function destroy($id)
    {
        $application = Application::findOrFail($id);
        
        // Delete related records
        // Get exam records first
        $examRecords = \App\Models\ExamRecord::where('application_id', $application->id)->get();
        
        // Delete exam subject grades for each exam record
        foreach ($examRecords as $examRecord) {
            \App\Models\ExamSubjectGrade::where('exam_record_id', $examRecord->id)->delete();
        }
        
        // Delete exam records
        \App\Models\ExamRecord::where('application_id', $application->id)->delete();
        
        // Delete admission form
        AdmissionForm::where('application_id', $application->id)->delete();
        
        // Delete the application
        $application->delete();
        
        return Redirect::route('admin.dashboard')
            ->with('status', 'Application deleted successfully.');
    }
}
