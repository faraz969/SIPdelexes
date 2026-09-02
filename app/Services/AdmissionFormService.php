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

        // Get preferred session and campus from admission form
        $preferredSession = $admissionForm->preferred_session ?? '';
        $preferredCampus = $admissionForm->preferred_campus ?? '';
        
        // Format gender
        $genderDisplay = '';
        if ($admissionForm->gender) {
            $genderDisplay = strtoupper(substr($admissionForm->gender, 0, 1)) === 'M' ? 'M' : 'F';
        }

        // Determine total fees from program (price) or fall back to admission form data
        $totalFees = $student->program && $student->program->price !== null
            ? (float) $student->program->price
            : ($formData->total_fees !== null ? (float) $formData->total_fees : null);

        $defaults = $this->getDefaultsForStudent($student);
        $registrarName = $defaults ? trim((string) $defaults->registrar_name) : '';
        $level = \App\Models\Student::normalizeLevel($student->level ?? null);

        $minimumFeePercentage = $formData->minimum_fee_percentage !== null
            ? (float) $formData->minimum_fee_percentage
            : 60.0;
        $balancePercentage = $formData->balance_percentage !== null
            ? (float) $formData->balance_percentage
            : ($totalFees !== null ? max(0, 100 - $minimumFeePercentage) : null);

        $minimumFeeAmount = $totalFees !== null
            ? round($totalFees * $minimumFeePercentage / 100, 2)
            : null;
        $balanceAmount = $totalFees !== null && $balancePercentage !== null
            ? round($totalFees * $balancePercentage / 100, 2)
            : null;

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
            'programme_start_date' => $this->formatLongDate(
                $formData->lectures_begin ?? $student->admission_date ?? now()
            ),
            'preferred_session' => $preferredSession,
            'preferred_campus' => $preferredCampus,
            'date' => ($formData->created_at ?? $student->admission_date ?? now())->format('d/m/Y'),
            'offer_type' => AdmissionFormData::normalizeOfferType($formData->offer_type ?? 'regular'),
            'conditional_subject' => trim((string) ($formData->conditional_subject ?? '')),
            
            // Admission Form Data
            'total_fees' => $totalFees !== null ? number_format($totalFees, 2) : '',
            'minimum_fee_percentage' => number_format($minimumFeePercentage, 2) . '%',
            'minimum_fee_amount' => $minimumFeeAmount !== null ? number_format($minimumFeeAmount, 2) : '',
            'balance_percentage' => $balancePercentage !== null ? number_format($balancePercentage, 2) . '%' : '',
            'balance_amount' => $balanceAmount !== null ? number_format($balanceAmount, 2) : '',
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
            'bank_payment_lines' => $this->buildBankPaymentLines($defaults),
            'bank_name' => $defaults ? trim((string) $defaults->bank_name) : '',
            'bank_account_name' => $defaults ? trim((string) $defaults->bank_account_name) : '',
            'bank_branch' => $defaults ? trim((string) $defaults->bank_branch) : '',
            'bank_account_no' => $defaults ? trim((string) $defaults->bank_account_no) : '',
            'payment_reference' => $defaults ? trim((string) $defaults->payment_reference) : '',
            'bank_name_2' => $defaults ? trim((string) $defaults->bank_name_2) : '',
            'bank_account_name_2' => $defaults ? trim((string) $defaults->bank_account_name_2) : '',
            'bank_branch_2' => $defaults ? trim((string) $defaults->bank_branch_2) : '',
            'bank_account_no_2' => $defaults ? trim((string) $defaults->bank_account_no_2) : '',
            'payment_reference_2' => $defaults ? trim((string) $defaults->payment_reference_2) : '',
        ];
    }

    protected function formatLongDate($date): string
    {
        if (!$date) {
            return '';
        }

        $carbon = $date instanceof \Carbon\Carbon
            ? $date
            : \Carbon\Carbon::parse($date);

        return $carbon->format('jS F, Y');
    }

    protected function buildBankPaymentLines(?AdmissionFormDefault $defaults): array
    {
        if (!$defaults) {
            return [];
        }

        return array_values(array_filter([
            $this->formatBankPaymentLine(
                $defaults->bank_name,
                $defaults->bank_account_name,
                $defaults->bank_account_no,
                $defaults->bank_branch
            ),
            $this->formatBankPaymentLine(
                $defaults->bank_name_2,
                $defaults->bank_account_name_2,
                $defaults->bank_account_no_2,
                $defaults->bank_branch_2
            ),
        ]));
    }

    protected function formatBankPaymentLine(?string $bankName, ?string $accountName, ?string $accountNo, ?string $branch): ?string
    {
        $bankName = trim((string) $bankName);
        if ($bankName === '') {
            return null;
        }

        $accountName = trim((string) $accountName) ?: 'Delexes University College';
        $accountNo = trim((string) $accountNo);
        $branch = trim((string) $branch);

        return "{$bankName} (Account Name: {$accountName} and Account Number: {$accountNo} Branch: {$branch})";
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
