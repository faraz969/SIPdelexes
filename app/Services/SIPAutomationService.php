<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Application;
use App\Models\User;
use App\Models\Program;
use App\Models\Session;
use App\Models\Department;
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
    public function processAdmissionApproval(Application $application, $registrarComments = null, ?string $level = '100')
    {
        if (Student::where('application_id', $application->id)->exists()) {
            throw new \RuntimeException('A SIP student account already exists for this application.');
        }

        $level = Student::normalizeLevel($level);

        DB::beginTransaction();
        try {
            // 1. Generate Unique Student ID / Index Number FIRST (required field)
            $studentId = $this->generateStudentId($application);

            // 2. Create Student SIP Account with the generated ID
            $student = $this->createStudentAccount($application, $studentId, $level);

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
            $result = $this->erpService->createStudentRecord([
                'student_id' => $studentId,
                'biodata' => $student->biodata,
                'program_id' => $student->program_id,
                'program_name' => $application->getPrimaryProgramName(),
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
     * Create Student SIP Account
     */
    protected function createStudentAccount(Application $application, $studentId, string $level = '100')
    {
        $user = $application->user;
        if (!$user) {
            throw new \Exception('Application does not have an associated user.');
        }

        $admissionForm = $application->admissionForm;

        // Get program from application
        $program = $this->getProgramFromApplication($application);

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
            'department_id' => $application->department_id,
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
    protected function generateStudentId(Application $application)
    {
        $application->loadMissing(['department', 'user.formType']);

        $degreeTypeDigit = $this->resolveDegreeTypeDigit($application);
        $departmentCodeDigit = $this->resolveDepartmentCodeDigit($application);
        $yearSuffix = $this->resolveAdmissionYearSuffix($application);

        $prefix = $degreeTypeDigit . $departmentCodeDigit;
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

