<?php

namespace App\Services;

use App\Models\Student;
use App\Models\AdmissionFormData;
use App\Models\AdmissionFormDefault;
use Illuminate\Support\Facades\Log;

class AdmissionFormService
{
    /**
     * Get admission form data for display
     */
    public function getAdmissionFormData(Student $student, AdmissionFormData $formData)
    {
        // Get student and application data
        $student->load(['user', 'program', 'department', 'application.admissionForm']);
        $application = $student->application;
        $admissionForm = $application->admissionForm ?? null;
        $user = $student->user;

        // Determine level (100, 200, etc.) - can be extracted from program or academic year
        $level = '100'; // Default to 100, can be made dynamic based on program/academic year
        
        // Get preferred session and campus from admission form
        $preferredSession = $admissionForm->preferred_session ?? '';
        $preferredCampus = $admissionForm->preferred_campus ?? '';
        
        // Format gender
        $genderDisplay = '';
        if ($admissionForm->gender) {
            $genderDisplay = strtoupper(substr($admissionForm->gender, 0, 1)) === 'M' ? 'M' : 'F';
        }

        // Determine total fees from program (price) or fall back to admission form data
        $programPrice = $student->program && $student->program->price !== null
            ? $student->program->price
            : $formData->total_fees;

        $defaults = $this->getDefaultsForStudent($student);
        $registrarName = $defaults ? trim((string) $defaults->registrar_name) : '';

        return [
            // Header Information
            'university_name' => 'DELEXES UNIVERSITY COLLEGE, GHANA',
            'address' => 'P.O.Box Co 3538,Tema | Peace Bee Junction C25 Tema-Aflao Road, Ningo-Prampram, Greater Accra, Ghana',
            'contact' => 'Tel: +233 (0) 55 1126 448 +233 (0) 55 1198 100 GPS: GN-0603-8481',
            
            // Student Information
            'student_name' => $user->name ?? ($admissionForm->full_name ?? ''),
            'student_id' => $student->student_id ?? '',
            'email' => $user->email ?? ($admissionForm->email ?? ''),
            'phone' => $user->phone ?? ($admissionForm->telephone ?? ''),
            'program' => $student->program->name ?? '',
            'course_title' => $student->program->name ?? '', // For "OFFER OF ADMISSION FOR { Course Title} DEGREE"
            'department' => $student->department->name ?? '',
            'academic_year' => $student->academic_year ?? '',
            'level' => $level,
            'date_of_birth' => $admissionForm->dob ? \Carbon\Carbon::parse($admissionForm->dob)->format('d/m/Y') : '',
            'gender' => $genderDisplay,
            'nationality' => $admissionForm->nationality ?? ($user->nationality ?? ''),
            'address_full' => $admissionForm->mailing_address ?? '',
            'admission_date' => $student->admission_date ? $student->admission_date->format('d/m/Y') : now()->format('d/m/Y'),
            'preferred_session' => $preferredSession,
            'preferred_campus' => $preferredCampus,
            'date' => now()->format('d/m/Y'), // For the offer date
            
            // Admission Form Data
            'total_fees' => $programPrice ? number_format($programPrice, 2) : '',
            'minimum_fee_percentage' => $formData->minimum_fee_percentage ? number_format($formData->minimum_fee_percentage, 2) . '%' : '60%',
            'minimum_fee_amount' => $formData->total_fees && $formData->minimum_fee_percentage 
                ? number_format(($formData->total_fees * $formData->minimum_fee_percentage / 100), 2) 
                : ($formData->total_fees ? number_format(($formData->total_fees * 60 / 100), 2) : ''),
            'balance_percentage' => $formData->balance_percentage ? number_format($formData->balance_percentage, 2) . '%' : '',
            'balance_amount' => $formData->total_fees && $formData->balance_percentage 
                ? number_format(($formData->total_fees * $formData->balance_percentage / 100), 2) 
                : '',
            'paid_fees_by_date' => $formData->paid_fees_by_date ? $formData->paid_fees_by_date->format('d/m/Y') : '',
            'registration_begins' => $formData->registration_begins ? $formData->registration_begins->format('d/m/Y') : '',
            'orientation_new_students' => $formData->orientation_new_students ? $formData->orientation_new_students->format('d/m/Y') : '',
            'faculty_orientation' => $formData->faculty_orientation ? $formData->faculty_orientation->format('d/m/Y') : '',
            'lectures_begin' => $formData->lectures_begin ? $formData->lectures_begin->format('d/m/Y') : '',
            
            // Additional fields
            'application_pin' => $user->serial_number ?? '',
            'registrar_name' => $registrarName !== '' ? $registrarName : 'A TEYE ABERMOR',
            'registrar_title' => 'Registrar',
            'registrar_signature' => $defaults ? $defaults->registrarSignatureSrc() : null,
        ];
    }

    protected function getDefaultsForStudent(Student $student): ?AdmissionFormDefault
    {
        $academicYear = $student->academic_year;
        $defaults = null;

        if ($academicYear) {
            $defaults = AdmissionFormDefault::where('academic_year', $academicYear)->first();
        }

        if (!$defaults) {
            $defaults = AdmissionFormDefault::first();
        }

        return $defaults;
    }

    /**
     * Create download record for admission form (HTML/PDF - no file path needed)
     */
    public function createDownloadRecord(Student $student)
    {
        return \App\Models\Download::create([
            'student_id' => $student->id,
            'document_type' => 'admission_form',
            'file_path' => 'html', // Mark as HTML view
            'file_name' => 'Admission Form - ' . $student->student_id,
            'academic_year' => $student->academic_year,
        ]);
    }
}
