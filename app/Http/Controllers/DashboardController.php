<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Application;
use App\Models\AdmissionForm;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use App\Models\ExamRecord;
use App\Models\ExamSubjectGrade;
use App\Models\SiteSetting;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user()->load('formType');
        $application = $user->applications()->latest()->first();
        return view('portal.dashboard', compact('user', 'application'));
    }

    public function applicationForm()
    {
        $user = Auth::user();
        $application = $user->applications()->latest()->first();
        $submitted = $application && in_array($application->status, ['submitted','successful','not_successful']);
        $action = $submitted ? null : route('portal.application.submit');

        $prefill = [];
        $uploadedFiles = [];
        if ($application && is_array($application->data)) {
            $prefill = $application->data;
            $uploadedFiles = $application->data['_files'] ?? [];
            unset($prefill['_files']);
        }

        $admissionForm = $application
            ? AdmissionForm::where('application_id', $application->id)->first()
            : null;

        if ($admissionForm) {
            if ($submitted) {
                $prefill['street_address'] = $admissionForm->street_address ?? ($prefill['street_address'] ?? '');
                $prefill['post_code'] = $admissionForm->post_code ?? ($prefill['post_code'] ?? '');
                $prefill['city'] = $admissionForm->city ?? ($prefill['city'] ?? '');
                $prefill['country'] = $admissionForm->country ?? ($prefill['country'] ?? '');
            }

            if (is_array($admissionForm->uploads)) {
                $uploadedFiles = array_replace_recursive($admissionForm->uploads, $uploadedFiles);
            }
        }

        // Fetch departments with their active programs
        $departments = Department::where('is_active', true)
            ->with(['activePrograms' => function($query) {
                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        // Fetch all active programs for order of preferences
        $allPrograms = \App\Models\Program::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        // Fetch dynamic sessions, campuses, and intakes
        $sessions = \App\Models\Session::active()->ordered()->get();
        $campuses = \App\Models\Campus::active()->ordered()->get();
        $intakes = \App\Models\Intake::active()->ordered()->get();

        // Load exam records for submitted view
        $examRecords = [];
        if ($submitted && $application) {
            $examRecords = \App\Models\ExamRecord::with('subjects')
                ->where('application_id', $application->id)
                ->get();
        }

        return view('admission.form', compact('action', 'prefill', 'submitted', 'uploadedFiles', 'departments', 'application', 'examRecords', 'allPrograms', 'sessions', 'campuses', 'intakes'));
    }

    public function applicationSave(Request $request)
    {
        $user = Auth::user();
        $application = $user->applications()->latest()->first();

        if (! $application) {
            $application = new Application();
            $application->user_id = $user->id;
            $application->application_number = (string) random_int(1000000000, 1999999999);
            $application->academic_year = SiteSetting::currentAcademicYear();
            $application->form_type = $request->input('form_type', 'undergraduate');
        }

        $isSubmittedApplication = in_array($application->status, ['submitted', 'successful', 'not_successful'], true);
        $updateSection = $request->input('update_section');

        if ($isSubmittedApplication && $updateSection === 'documents') {
            return $this->updateApplicationDocuments($request, $application, $user);
        }

        $data = $request->except(['_token', 'update_section']);
        $existing = is_array($application->data) ? $application->data : [];
        $application->data = array_replace_recursive($existing, $data);

        if (! $isSubmittedApplication) {
            $application->status = 'draft';
        }

        $application->save();

        if ($application->id) {
            $form = AdmissionForm::where('application_id', $application->id)->first();
            if ($form) {
                $form->update([
                    'street_address' => $request->input('street_address'),
                    'post_code' => $request->input('post_code'),
                    'city' => $request->input('city'),
                    'country' => $request->input('country'),
                    'full_name' => $request->input('full_name', $form->full_name),
                    'dob' => $request->input('dob', $form->dob),
                    'age' => $request->input('age', $form->age),
                    'gender' => $request->input('gender', $form->gender),
                    'birth_place' => $request->input('birth_place', $form->birth_place),
                    'marital_status' => $request->input('marital_status', $form->marital_status),
                    'nationality' => $request->input('nationality', $form->nationality),
                    'passport_number' => $request->input('passport_number', $form->passport_number),
                    'mailing_address' => $request->input('mailing_address', $form->mailing_address),
                    'emergency_contact' => $request->input('emergency_contact', $form->emergency_contact),
                    'telephone' => $request->input('telephone', $form->telephone),
                    'email' => $request->input('email', $form->email),
                    'hostel_required' => $request->input('hostel_required') === 'Yes' ? true : ($request->input('hostel_required') === 'No' ? false : $form->hostel_required),
                    'has_disability' => $request->input('has_disability') === 'Yes' ? true : ($request->input('has_disability') === 'No' ? false : $form->has_disability),
                    'disability_details' => $request->input('disability_details', $form->disability_details),
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Personal data updated successfully');
    }

    protected function updateApplicationDocuments(Request $request, Application $application, User $user)
    {
        $existing = is_array($application->data) ? $application->data : [];
        $existingFiles = $existing['_files'] ?? [];

        $admissionForm = AdmissionForm::where('application_id', $application->id)->first();
        if ($admissionForm && is_array($admissionForm->uploads)) {
            $existingFiles = array_replace_recursive($admissionForm->uploads, $existingFiles);
        }

        $newFiles = $this->storeUploadedFilesRecursively($request->allFiles(), $user->id) ?? [];
        if (empty($newFiles)) {
            return back()->with('error', 'Please select at least one document to upload.');
        }

        $mergedFiles = $this->mergeUploadedFiles($existingFiles, $newFiles);
        $missingRequired = $this->missingRequiredDocumentKeys($mergedFiles);
        if (! empty($missingRequired)) {
            return back()->with('error', 'The following required documents are still missing: ' . implode(', ', $missingRequired));
        }

        $existing['_files'] = $mergedFiles;
        $application->data = $existing;
        $application->save();

        if ($admissionForm) {
            $admissionForm->update(['uploads' => $mergedFiles]);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Documents updated successfully');
    }

    protected function mergeUploadedFiles(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if ($key === 'other_academic_records' && is_array($value)) {
                $current = $existing[$key] ?? [];
                $current = is_array($current) ? $current : ($current ? [$current] : []);
                $existing[$key] = array_values(array_merge($current, $value));
                continue;
            }

            if (is_array($value) && isset($existing[$key]) && is_array($existing[$key])) {
                $existing[$key] = $this->mergeUploadedFiles($existing[$key], $value);
                continue;
            }

            $existing[$key] = $value;
        }

        return $existing;
    }

    protected function missingRequiredDocumentKeys(array $files): array
    {
        $labels = [
            'ghana_card_front' => 'Ghana Card (Front)',
            'ghana_card_back' => 'Ghana Card (Back)',
            'passport_picture' => 'Passport Picture',
        ];

        $missing = [];
        foreach ($labels as $key => $label) {
            if (empty($files[$key])) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    public function applicationSubmit(Request $request)
    {
        $user = Auth::user();
        $application = $user->applications()->latest()->first();
        if (! $application) {
            $application = new Application();
            $application->user_id = $user->id;
            $application->application_number = (string) random_int(1000000000, 1999999999);
            $application->academic_year = SiteSetting::currentAcademicYear();
            $application->form_type = 'undergraduate';
        }

        $payload = $request->except(['_token']);
        $files = $this->storeUploadedFilesRecursively($request->allFiles(), $user->id);
        $payload['_files'] = $files;

        // Determine departments based on selected programs
        $departmentIds = $this->determineDepartmentsFromApplication($payload);
        $primaryDepartmentId = !empty($departmentIds) ? $departmentIds[0] : null;

        $application->data = $payload;
        $application->status = 'submitted';
        $application->department_id = $primaryDepartmentId; // Keep for backward compatibility
        $application->department_ids = $departmentIds; // Store all departments
        $application->hod_status = 'pending';
        $application->president_status = 'pending';
        $application->registrar_status = 'pending';
        $application->save();

        // Save to structured AdmissionForm
        $form = AdmissionForm::updateOrCreate(
            ['user_id' => $user->id, 'application_id' => $application->id],
            [
                'full_name' => $request->input('full_name', $user->name),
                'dob' => $request->input('dob'),
                'age' => $request->input('age'),
                'gender' => $request->input('gender'),
                'birth_place' => $request->input('birth_place'),
                'marital_status' => $request->input('marital_status'),
                'nationality' => $request->input('nationality'),
                'passport_number' => $request->input('passport_number'),
                'mailing_address' => $request->input('mailing_address'),
                'street_address' => $request->input('street_address'),
                'post_code' => $request->input('post_code'),
                'city' => $request->input('city'),
                'country' => $request->input('country'),
                'emergency_contact' => $request->input('emergency_contact'),
                'telephone' => $request->input('telephone'),
                'email' => $request->input('email', $user->email),
                'hostel_required' => $request->input('hostel_required') === 'Yes',
                'has_disability' => $request->input('has_disability') === 'Yes',
                'disability_details' => $request->input('disability_details'),
                'prog_eng' => $request->input('prog_eng'),
                'prog_eng_mode' => $request->input('prog_eng_mode'),
                'prog_focis' => $request->input('prog_focis'),
                'prog_focis_mode' => $request->input('prog_focis_mode'),
                'prog_business' => $request->input('prog_business'),
                'prog_business_mode' => $request->input('prog_business_mode'),
                'pref1' => $request->input('pref1'),
                'pref2' => $request->input('pref2'),
                'pref3' => $request->input('pref3'),
                'entry_wassce' => (bool) $request->input('entry_wassce'),
                'entry_sssce' => (bool) $request->input('entry_sssce'),
                'entry_ib' => (bool) $request->input('entry_ib'),
                'entry_transfer' => (bool) $request->input('entry_transfer'),
                'entry_other' => (bool) $request->input('entry_other'),
                'other_entry_detail' => $request->input('other_entry_detail'),
                'preferred_session' => $request->input('preferred_session'),
                'preferred_campus' => $request->input('preferred_campus'),
                'intake_option' => $request->input('intake_option'),
                'english_level' => $request->input('english_level'),
                'mother_tongue' => $request->input('mother_tongue'),
                'other_languages' => $request->input('other_languages'),
                'institutions' => $request->input('institutions'),
                'employment' => $request->input('employment'),
                'uploads' => $files,
            ]
        );

        // Persist Exam Sections (if provided)
        $examSections = $request->input('exam_sections', []);
        if (is_array($examSections)) {
            // Remove previous records for this application to keep data in sync
            ExamRecord::where('application_id', $application->id)->delete();
            foreach ($examSections as $section) {
                if (!is_array($section)) { continue; }
                $examRecord = ExamRecord::create([
                    'application_id' => $application->id,
                    'exam_type' => $section['exam_type'] ?? null,
                    'sitting_exam' => $section['sitting_exam'] ?? null,
                    'year' => $section['year'] ?? null,
                    'index_number' => $section['index_number'] ?? null,
                ]);

                $subjects = $section['subjects'] ?? [];
                if (is_array($subjects)) {
                    $rows = [];
                    foreach ($subjects as $row) {
                        if (!is_array($row)) { continue; }
                        $rows[] = new ExamSubjectGrade([
                            'subject' => $row['subject'] ?? null,
                            'grade_letter' => $row['grade_letter'] ?? null,
                            'grade_number' => isset($row['grade_number']) && $row['grade_number'] !== '' ? (int)$row['grade_number'] : null,
                            'is_best_six' => !empty($row['is_best_six']),
                        ]);
                    }
                    if (!empty($rows)) {
                        $examRecord->subjects()->saveMany($rows);
                    }
                }
            }
        }
        
        // Send SMS notification to user
        // Use telephone from application data if available, otherwise use user's phone
        $phoneNumber = $request->input('telephone') ?? $user->phone;
        if ($phoneNumber) {
            $username = $request->input('full_name', $user->name);
            $applicationId = $application->application_number;
            $this->sendApplicationSMS($phoneNumber, $username, $applicationId);
        }
        
        return redirect()->route('portal.dashboard')->with('status', 'Application submitted');
    }

    /**
     * Determine all departments based on selected programs in the application
     */
    private function determineDepartmentsFromApplication($data)
    {
        $departmentIds = [];
        
        // Get all departments with their programs
        $departments = Department::with('programs')->get();
        
        // Check each department's programs against the application data
        foreach ($departments as $department) {
            foreach ($department->programs as $program) {
                // Check if this program is selected in any of the program fields
                foreach ($data as $key => $value) {
                    if (strpos($key, 'prog_') === 0 && $value === $program->name) {
                        $departmentIds[] = $department->id;
                        break 2; // Break out of both inner loops for this department
                    }
                }
            }
        }
        
        // Remove duplicates
        $departmentIds = array_unique($departmentIds);
        
        // If no specific programs found, return the first department as default
        if (empty($departmentIds)) {
            $firstDepartment = Department::first();
            if ($firstDepartment) {
                $departmentIds = [$firstDepartment->id];
            }
        }
        
        return $departmentIds;
    }

    /**
     * Recursively store uploaded files preserving nested array structure.
     *
     * @param mixed $node
     * @param int $userId
     * @return mixed
     */
    private function storeUploadedFilesRecursively($node, int $userId)
    {
        if ($node instanceof UploadedFile) {
            return $node->store('applications/'.$userId, 'public');
        }

        if (is_array($node)) {
            $result = [];
            foreach ($node as $key => $child) {
                $stored = $this->storeUploadedFilesRecursively($child, $userId);
                if ($stored !== null && $stored !== []) {
                    $result[$key] = $stored;
                }
            }
            return $result;
        }

        return null;
    }

    public function results()
    {
        $user = Auth::user();
        $application = $user->applications()->latest()->first();
        return view('portal.results', compact('application'));
    }

    public function applicationPrint()
    {
        $user = Auth::user();
        $application = $user->applications()->latest()->first();
        
        if (!$application) {
            return redirect()->route('portal.dashboard')->with('error', 'No application found.');
        }
        
        $prefill = [];
        $uploadedFiles = [];
        if ($application && is_array($application->data)) {
            $prefill = $application->data;
            $uploadedFiles = $application->data['_files'] ?? [];
            unset($prefill['_files']);
        }
        
        // Load exam records for the declaration
        $examRecords = ExamRecord::with('subjects')
            ->where('application_id', $application->id)
            ->get();
        
        // Load admission form data for additional fields
        $admissionForm = AdmissionForm::where('application_id', $application->id)->first();
        if ($admissionForm) {
            $prefill['street_address'] = $admissionForm->street_address ?? ($prefill['street_address'] ?? '');
            $prefill['post_code'] = $admissionForm->post_code ?? ($prefill['post_code'] ?? '');
            $prefill['city'] = $admissionForm->city ?? ($prefill['city'] ?? '');
            $prefill['country'] = $admissionForm->country ?? ($prefill['country'] ?? '');
        }
        
        return view('admission.declaration', compact('application', 'prefill', 'uploadedFiles', 'examRecords', 'user'));
    }

    /**
     * Send SMS notification when application is submitted
     */
    private function sendApplicationSMS($phone, $username, $applicationId)
    {
        $message = "Dear {$username} your application has been submitted successfully, your application number is {$applicationId}";
        app(\App\Services\SmsService::class)->send($phone, $message);
    }
}
