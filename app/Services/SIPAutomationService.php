<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Application;
use App\Models\User;
use App\Models\Program;
use App\Models\Session;
use App\Models\Department;
use App\Models\AdmissionFormData;
use App\Services\ERPIntegrationService;
use App\Services\ActivityLogService;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SIPAutomationService
{
    protected $erpService;
    protected $activityLogService;
    protected $smsService;

    public function __construct(
        ERPIntegrationService $erpService,
        ActivityLogService $activityLogService,
        SmsService $smsService
    ) {
        $this->erpService = $erpService;
        $this->activityLogService = $activityLogService;
        $this->smsService = $smsService;
    }

    /**
     * Process admission approval: create ERP applicant, SIP account, then mark approved.
     *
     * @throws \Exception If ERP or SIP setup fails (application remains unapproved)
     */
    public function processAdmissionApproval(
        Application $application,
        $registrarComments = null,
        ?string $level = '100',
        ?string $preferredStudentId = null,
        ?Program $forcedProgram = null
    ) {
        if (Student::where('application_id', $application->id)->exists()) {
            throw new \RuntimeException('A SIP student account already exists for this application.');
        }

        $level = Student::normalizeLevel($level);

        DB::beginTransaction();
        try {
            // 1. Generate Unique Student ID / Index Number FIRST (required field)
            $studentId = $this->generateStudentId($application, $preferredStudentId);

            // 2. Create Student SIP Account with the generated ID
            $student = $this->createStudentAccount($application, $studentId, $level, $forcedProgram);

            // 3. Create student email and update user email
            $studentEmail = $studentId . '@delexesuniversity.edu.gh';
            $user = $application->user;
            $user->email = $studentEmail;
            
            // 4. Generate temporary password (4-character PIN)
            $tempPassword = Str::upper(Str::random(4));
            
            $user->password = Hash::make($tempPassword);
            $user->pin = $tempPassword;
            $user->password_changed_at = null;
            $user->save();

            \Log::info("SIP Account Created - Login Credentials", [
                'student_id' => $studentId,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'student_email' => $studentEmail,
                'old_email' => $application->admissionForm->email ?? $user->email ?? 'N/A',
                'serial_number' => $user->serial_number,
                'password' => $tempPassword,
                'pin' => $tempPassword,
                'created_by' => auth()->id() ?? 'system',
                'created_at' => now()->toDateTimeString(),
            ]);

            // 5. Create student applicant in ERPNext (must succeed before approval is finalized)
            $programName = $forcedProgram
                ? $forcedProgram->name
                : $application->getPrimaryProgramName();

            $result = $this->erpService->createStudentRecord([
                'student_id' => $studentId,
                'biodata' => $student->biodata,
                'program_id' => $student->program_id,
                'program_name' => $programName,
                'academic_year' => $student->academic_year,
            ]);

            if (!empty($result['erp_student_name'])) {
                $student->erp_student_name = $result['erp_student_name'];
                $student->save();
            }

            // 6. Mark application approved by registrar
            if ($application->registrar_status !== 'approved') {
                $applicationData = is_array($application->data) ? $application->data : [];
                unset($applicationData['_erp_last_error'], $applicationData['_erp_last_error_at']);
                $application->data = $applicationData;

                $application->registrar_status = 'approved';
                $application->registrar_comments = $registrarComments;
                $application->registrar_reviewed_at = now();
                $application->updateMainStatus();
            }

            $this->activityLogService->log([
                'user_id' => auth()->id(),
                'role' => auth()->user()->role ?? 'system',
                'action' => 'sip_account_created',
                'model_type' => Student::class,
                'model_id' => $student->id,
                'system_source' => 'SIP',
                'description' => "SIP account created for student {$studentId}. Password/PIN: {$tempPassword}",
                'metadata' => [
                    'student_id' => $studentId,
                    'password_generated' => true,
                    'erp_student_name' => $result['erp_student_name'] ?? null,
                ],
            ]);

            DB::commit();

            // 7. Send SMS & Email with credentials (after commit - non-blocking)
            try {
                $this->sendAdmissionCredentials($user, $student, $tempPassword);
            } catch (\Exception $e) {
                \Log::warning('Failed to send admission credentials (non-critical)', [
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ]);
            }

            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SIP Automation Failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Change admitted student's program: delete SIP + ERP student, re-admit with new program.
     */
    public function changeProgramAndReadmit(
        Application $application,
        Program $newProgram,
        ?string $level = null,
        ?string $offerType = null,
        ?string $conditionalSubject = null,
        ?string $comments = null
    ): Student {
        $oldStudent = $application->student;

        // Recovery path: approved but SIP student already missing (failed mid-change).
        if (!$oldStudent) {
            return $this->completeReadmitForApprovedApplication(
                $application,
                $newProgram,
                $level,
                $offerType,
                $conditionalSubject,
                $comments
            );
        }

        if ((int) $oldStudent->program_id === (int) $newProgram->id) {
            throw new \RuntimeException('Student is already on the selected program.');
        }

        $completedPayments = $oldStudent->payments()
            ->where('status', 'completed')
            ->count();
        if ($completedPayments > 0) {
            throw new \RuntimeException(
                "Cannot change program: this student has {$completedPayments} completed payment(s). "
                . 'Resolve or reverse those payments first to avoid losing the financial record.'
            );
        }

        $oldStudent->loadMissing(['admissionFormData', 'program']);
        $preservedLevel = Student::normalizeLevel($level ?: ($oldStudent->level ?: '100'));
        $preservedOfferType = AdmissionFormData::normalizeOfferType(
            $offerType ?: optional($oldStudent->admissionFormData)->offer_type
        );
        $preservedSubject = $preservedOfferType === 'conditional'
            ? trim((string) ($conditionalSubject !== null
                ? $conditionalSubject
                : optional($oldStudent->admissionFormData)->conditional_subject))
            : null;

        $oldErpName = $oldStudent->erp_student_name;
        $oldStudentId = $oldStudent->student_id;
        $oldProgramName = optional($oldStudent->program)->name;
        $oldEmail = $oldStudentId . '@delexesuniversity.edu.gh';

        // 1. Delete ERP student (and related enrollments/applicant)
        try {
            $this->erpService->deleteStudentRecord($oldErpName, $oldStudentId, $oldEmail);
        } catch (\Exception $e) {
            \Log::error('ERP delete failed during program change', [
                'application_id' => $application->id,
                'old_student_id' => $oldStudentId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'Could not delete the existing ERP student before re-admission: ' . $e->getMessage()
            );
        }

        // 2. Delete SIP student (cascades invoices, payments, downloads, letter data, etc.)
        $oldStudent->delete();

        // 3. Point application at the new program + stash retry payload if ERP create fails next.
        $application->assignAdmissionProgram($newProgram);
        $application->refresh();

        if ($comments) {
            $note = '[Program change ' . now()->format('Y-m-d H:i') . '] '
                . ($oldProgramName ?: 'previous') . ' → ' . $newProgram->name
                . '. ' . $comments;
            $application->registrar_comments = trim(
                trim((string) $application->registrar_comments) . "\n" . $note
            );
            $application->save();
        }

        $this->storePendingReadmit($application, [
            'program_id' => $newProgram->id,
            'level' => $preservedLevel,
            'offer_type' => $preservedOfferType,
            'conditional_subject' => $preservedSubject,
            'preferred_student_id' => $oldStudentId,
            'old_program' => $oldProgramName,
        ]);

        try {
            return $this->finalizeReadmit(
                $application,
                $newProgram,
                $preservedLevel,
                $preservedOfferType,
                $preservedSubject,
                $oldStudentId,
                $oldProgramName,
                $oldErpName
            );
        } catch (\Exception $e) {
            $this->storePendingReadmit($application, [
                'program_id' => $newProgram->id,
                'level' => $preservedLevel,
                'offer_type' => $preservedOfferType,
                'conditional_subject' => $preservedSubject,
                'preferred_student_id' => $oldStudentId,
                'old_program' => $oldProgramName,
                'error' => $e->getMessage(),
                'failed_at' => now()->toDateTimeString(),
            ]);

            throw new \RuntimeException(
                'SIP/ERP student was removed, but re-admission failed: ' . $e->getMessage()
                . ' Open this application again and use “Complete Re-admission” after fixing the ERP program.'
            );
        }
    }

    /**
     * Complete admission for an approved application that has no SIP student
     * (e.g. program-change failed after delete).
     */
    public function completeReadmitForApprovedApplication(
        Application $application,
        ?Program $program = null,
        ?string $level = null,
        ?string $offerType = null,
        ?string $conditionalSubject = null,
        ?string $comments = null
    ): Student {
        if ($application->registrar_status !== 'approved') {
            throw new \RuntimeException('Application is not registrar-approved.');
        }

        if ($application->student) {
            throw new \RuntimeException('A SIP student already exists for this application.');
        }

        $pending = $this->getPendingReadmit($application);

        if (!$program) {
            $programId = $pending['program_id'] ?? null;
            if ($programId) {
                $program = Program::find($programId);
            }
            if (!$program) {
                $program = $this->getProgramFromApplication($application);
            }
        }

        if (!$program) {
            throw new \RuntimeException('Select a program to complete re-admission.');
        }

        $application->assignAdmissionProgram($program);
        $application->refresh();

        $preservedLevel = Student::normalizeLevel(
            $level ?: ($pending['level'] ?? '100')
        );
        $preservedOfferType = AdmissionFormData::normalizeOfferType(
            $offerType ?: ($pending['offer_type'] ?? 'regular')
        );
        $preservedSubject = $preservedOfferType === 'conditional'
            ? trim((string) ($conditionalSubject !== null
                ? $conditionalSubject
                : ($pending['conditional_subject'] ?? '')))
            : null;
        $preferredStudentId = $pending['preferred_student_id'] ?? null;

        if ($comments) {
            $note = '[Complete re-admission ' . now()->format('Y-m-d H:i') . '] ' . $comments;
            $application->registrar_comments = trim(
                trim((string) $application->registrar_comments) . "\n" . $note
            );
            $application->save();
        }

        // Best-effort cleanup if a partial ERP record exists from a previous attempt.
        try {
            $email = $preferredStudentId
                ? ($preferredStudentId . '@delexesuniversity.edu.gh')
                : null;
            $this->erpService->deleteStudentRecord(null, $preferredStudentId, $email);
        } catch (\Exception $e) {
            \Log::warning('Complete readmit: ERP cleanup skipped/failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            return $this->finalizeReadmit(
                $application,
                $program,
                $preservedLevel,
                $preservedOfferType,
                $preservedSubject,
                $preferredStudentId,
                $pending['old_program'] ?? null,
                null
            );
        } catch (\Exception $e) {
            $this->storePendingReadmit($application, [
                'program_id' => $program->id,
                'level' => $preservedLevel,
                'offer_type' => $preservedOfferType,
                'conditional_subject' => $preservedSubject,
                'preferred_student_id' => $preferredStudentId,
                'old_program' => $pending['old_program'] ?? null,
                'error' => $e->getMessage(),
                'failed_at' => now()->toDateTimeString(),
            ]);

            throw new \RuntimeException(
                'Re-admission failed: ' . $e->getMessage()
                . ' Ensure the program exists in ERP, then try Complete Re-admission again.'
            );
        }
    }

    protected function finalizeReadmit(
        Application $application,
        Program $newProgram,
        string $preservedLevel,
        string $preservedOfferType,
        ?string $preservedSubject,
        ?string $preferredStudentId,
        ?string $oldProgramName = null,
        ?string $oldErpName = null
    ): Student {
        $student = $this->processAdmissionApproval(
            $application,
            $application->registrar_comments,
            $preservedLevel,
            $preferredStudentId,
            $newProgram
        );

        $academicYear = $application->academic_year;
        $defaults = \App\Models\AdmissionFormDefault::where('academic_year', $academicYear)->first()
            ?: \App\Models\AdmissionFormDefault::first();
        $totalFees = $newProgram->price !== null ? $newProgram->price : null;

        AdmissionFormData::updateOrCreate(
            ['student_id' => $student->id],
            [
                'application_id' => $application->id,
                'offer_type' => $preservedOfferType,
                'conditional_subject' => $preservedSubject,
                'offer_accepted_at' => null,
                'total_fees' => $totalFees,
                'minimum_fee_percentage' => $defaults->minimum_fee_percentage ?? null,
                'balance_percentage' => $defaults->balance_percentage ?? null,
                'paid_fees_by_date' => $defaults->paid_fees_by_date ?? null,
                'registration_begins' => $defaults->registration_begins ?? null,
                'orientation_new_students' => $defaults->orientation_new_students ?? null,
                'faculty_orientation' => $defaults->faculty_orientation ?? null,
                'lectures_begin' => $defaults->lectures_begin ?? null,
            ]
        );

        try {
            \App\Models\Download::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'document_type' => 'admission_form',
                ],
                [
                    'file_path' => 'html',
                    'file_name' => 'Admission Form - ' . $student->student_id,
                    'academic_year' => $student->academic_year,
                ]
            );
        } catch (\Exception $e) {
            \Log::warning('Readmit: admission download record failed', [
                'student_id' => $student->student_id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->clearPendingReadmit($application);

        $this->activityLogService->log([
            'user_id' => auth()->id(),
            'role' => auth()->user()->role ?? 'system',
            'action' => 'student_program_changed_readmit',
            'model_type' => Student::class,
            'model_id' => $student->id,
            'system_source' => 'SIP',
            'description' => "Student re-admitted after program change: "
                . ($oldProgramName ?: 'previous') . " → {$newProgram->name}",
            'metadata' => [
                'preferred_student_id' => $preferredStudentId,
                'new_student_id' => $student->student_id,
                'old_program' => $oldProgramName,
                'new_program' => $newProgram->name,
                'old_erp_student_name' => $oldErpName,
                'new_erp_student_name' => $student->erp_student_name,
            ],
        ]);

        return $student;
    }

    protected function storePendingReadmit(Application $application, array $payload): void
    {
        $data = is_array($application->data) ? $application->data : [];
        $data['_pending_readmit'] = $payload;
        $application->data = $data;
        $application->save();
    }

    protected function clearPendingReadmit(Application $application): void
    {
        $data = is_array($application->data) ? $application->data : [];
        if (isset($data['_pending_readmit'])) {
            unset($data['_pending_readmit']);
            $application->data = $data;
            $application->save();
        }
    }

    protected function getPendingReadmit(Application $application): array
    {
        $data = is_array($application->data) ? $application->data : [];
        $pending = $data['_pending_readmit'] ?? [];

        return is_array($pending) ? $pending : [];
    }

    /**
     * Create Student SIP Account
     */
    protected function createStudentAccount(
        Application $application,
        $studentId,
        string $level = '100',
        ?Program $forcedProgram = null
    ) {
        $user = $application->user;
        if (!$user) {
            throw new \Exception('Application does not have an associated user.');
        }

        $admissionForm = $application->admissionForm;

        // Get program from application
        $program = $forcedProgram ?: $this->getProgramFromApplication($application);

        // Resolve preferred session from admission form (stored as session name)
        $preferredSessionId = null;
        $preferredSessionName = $admissionForm->preferred_session ?? $application->data['preferred_session'] ?? null;
        if ($preferredSessionName) {
            $session = Session::where('name', $preferredSessionName)->first();
            $preferredSessionId = $session ? $session->id : null;
        }

        // Prepare biodata
        $biodata = [
            'full_name' => $admissionForm->full_name ?? $user->name ?? 'N/A',
            'email' => $admissionForm->email ?? $user->email ?? 'N/A',
            'phone' => $user->phone ?? null,
            'dob' => $admissionForm->dob ?? null,
            'gender' => $admissionForm->gender ?? null,
            'nationality' => $admissionForm->nationality ?? $user->nationality ?? null,
            'address' => $admissionForm->mailing_address ?? null,
        ];

        $student = Student::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'student_id' => $studentId,
            'program_id' => $program->id ?? null,
            'department_id' => $program->department_id ?? $application->department_id,
            'preferred_session_id' => $preferredSessionId,
            'academic_year' => $application->academic_year,
            'level' => Student::normalizeLevel($level),
            'academic_status' => 'active',
            'admission_date' => now(),
            'biodata' => $biodata,
            'sip_account_created' => true,
            'sip_account_created_at' => now(),
        ]);

        return $student;
    }

    /**
     * Generate Unique Student ID / Index Number
     * Pattern: 110000526
     * - Digit 1: degree type (1 = undergraduate, 2 = postgraduate)
     * - Digit 2: department code (from departments.code, e.g. 1, 2, 3)
     * - Digits 3-7: sequential student number within dept/year (00001-99999)
     * - Digits 8-9: admission year suffix (e.g. 26 for 2026)
     */
    protected function generateStudentId(Application $application, ?string $preferredStudentId = null)
    {
        $application->loadMissing(['department', 'user.formType']);

        $degreeTypeDigit = $this->resolveDegreeTypeDigit($application);
        $departmentCodeDigit = $this->resolveDepartmentCodeDigit($application);
        $yearSuffix = $this->resolveAdmissionYearSuffix($application);
        $prefix = $degreeTypeDigit . $departmentCodeDigit;

        $preferredStudentId = trim((string) $preferredStudentId);
        if (
            $preferredStudentId !== ''
            && !Student::where('student_id', $preferredStudentId)->exists()
            && strlen($preferredStudentId) >= 4
            && substr($preferredStudentId, 0, 2) === $prefix
            && substr($preferredStudentId, -2) === $yearSuffix
        ) {
            return $preferredStudentId;
        }

        $likePattern = $prefix . '_____' . $yearSuffix;

        $lastStudent = Student::where('student_id', 'like', $likePattern)
            ->orderBy('student_id', 'desc')
            ->first();

        $studentNumber = 1;
        if ($lastStudent) {
            $studentNumber = (int) substr($lastStudent->student_id, 2, 5) + 1;
        }

        if ($studentNumber > 99999) {
            throw new \Exception('Maximum student capacity reached for this department and admission year.');
        }

        $studentId = $prefix
            . str_pad((string) $studentNumber, 5, '0', STR_PAD_LEFT)
            . $yearSuffix;

        if (Student::where('student_id', $studentId)->exists()) {
            do {
                $studentNumber++;
                $studentId = $prefix
                    . str_pad((string) $studentNumber, 5, '0', STR_PAD_LEFT)
                    . $yearSuffix;
            } while (Student::where('student_id', $studentId)->exists() && $studentNumber <= 99999);
        }

        if ($studentNumber > 99999) {
            throw new \Exception('Maximum student capacity reached for this department and admission year.');
        }

        return $studentId;
    }

    protected function resolveDegreeTypeDigit(Application $application): string
    {
        $formType = strtolower((string) ($application->form_type ?? ''));

        if (
            str_contains($formType, 'postgraduate')
            || str_contains($formType, 'post graduate')
            || str_contains($formType, 'masters')
            || str_contains($formType, 'phd')
        ) {
            return '2';
        }

        $userFormType = strtolower((string) (optional(optional($application->user)->formType)->name ?? ''));
        if (str_contains($userFormType, 'postgraduate')) {
            return '2';
        }

        return '1';
    }

    protected function resolveDepartmentCodeDigit(Application $application): string
    {
        $department = $application->department;
        if (!$department && $application->department_id) {
            $department = Department::find($application->department_id);
        }

        $code = trim((string) ($department->code ?? ''));
        if ($code !== '' && preg_match('/^\d$/', $code)) {
            return $code;
        }

        $departmentId = (string) ($application->department_id ?? '');
        if (preg_match('/^\d$/', $departmentId)) {
            return $departmentId;
        }

        throw new \Exception(
            'Department code must be a single digit (1-9) to generate a student ID. Please set it in Admin > Departments.'
        );
    }

    protected function resolveAdmissionYearSuffix(Application $application): string
    {
        $academicYear = trim((string) ($application->academic_year ?? ''));
        if (preg_match('/(\d{4})/', $academicYear, $matches)) {
            return substr($matches[1], -2);
        }

        return now()->format('y');
    }

    /**
     * Get program from application
     */
    protected function getProgramFromApplication(Application $application)
    {
        $qualifiedPrograms = $application->getQualifiedPrograms();
        if ($qualifiedPrograms->isNotEmpty()) {
            return $qualifiedPrograms->first();
        }

        $selectedPrograms = $application->getSelectedPrograms();
        if ($selectedPrograms->isNotEmpty()) {
            return $selectedPrograms->first();
        }

        return null;
    }

    /**
     * Send admission credentials via SMS and Email
     * Note: This should be called AFTER database transaction is committed
     */
    protected function sendAdmissionCredentials(User $user, Student $student, string $tempPassword)
    {
        // Student must login with Student_ID@delexesuniversity.edu.gh
        $loginEmail = $student->student_id . '@delexesuniversity.edu.gh';
        
        // Send Email (with timeout to prevent hanging)
        try {
            // Use queue or timeout to prevent blocking
            Mail::send('emails.admission-approval', [
                'user' => $user,
                'student' => $student,
                'password' => $tempPassword,
                'login_email' => $loginEmail,
            ], function ($message) use ($user, $loginEmail) {
                $message->to($loginEmail)
                    ->subject('Admission Approved - SIP Login Credentials');
            });
            
            \Log::info("Admission approval email sent successfully", [
                'student_id' => $student->student_id,
                'student_email' => $loginEmail,
            ]);
        } catch (\Exception $e) {
            // Log but don't throw - email failure shouldn't block student creation
            \Log::error('Failed to send admission email', [
                'student_id' => $student->student_id,
                'student_email' => $loginEmail,
                'error' => $e->getMessage(),
            ]);
        }

        // Send SMS with credentials (non-blocking)
        try {
            $studentName = $user->name ?? 'Student';
            $programName = optional($student->program)->name ?? 'your programme';
            $loginUrl = url('/login');

            $smsMessage = "CONGRATULATIONS MR/MS {$studentName}! You have been admitted to BSc. {$programName}. Login: Student ID {$student->student_id}, PIN {$tempPassword}. Change your password on first login: {$loginUrl}. Go to DOWNLOAD >Click ACCEPTANCE to download your admission letter.";
            $this->smsService->send($user->phone, $smsMessage);
            
            \Log::info("Admission approval SMS sent successfully", [
                'student_id' => $student->student_id,
                'user_phone' => $user->phone,
            ]);
        } catch (\Exception $e) {
            // Log but don't throw - SMS failure shouldn't block student creation
            \Log::error('Failed to send admission SMS', [
                'student_id' => $student->student_id,
                'user_phone' => $user->phone,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

