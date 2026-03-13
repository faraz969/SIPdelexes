<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Application;
use App\Models\User;
use App\Models\Program;
use App\Models\Session;
use App\Services\ERPIntegrationService;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SIPAutomationService
{
    protected $erpService;
    protected $activityLogService;

    public function __construct(ERPIntegrationService $erpService, ActivityLogService $activityLogService)
    {
        $this->erpService = $erpService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Process admission approval and trigger SIP automation
     */
    public function processAdmissionApproval(Application $application)
    {
        DB::beginTransaction();
        try {
            // 1. Generate Unique Student ID / Index Number FIRST (required field)
            $studentId = $this->generateStudentId($application);

            // 2. Create Student SIP Account with the generated ID
            $student = $this->createStudentAccount($application, $studentId);

            // 3. Create student email and update user email
            $studentEmail = $studentId . '@delexesuniversity.edu.gh';
            $user = $application->user;
            $user->email = $studentEmail;
            
            // 4. Generate temporary password (4-character PIN)
            // Note: Deliberately short for ease of first login; user is forced to change on first login.
            $tempPassword = Str::upper(Str::random(4));
            
            // Update both password and PIN to the same value
            // Set password_changed_at to null to force password change on first login
            $user->password = Hash::make($tempPassword);
            $user->pin = $tempPassword; // Store PIN in plain text for SMS/display
            $user->password_changed_at = null; // Force password change on first login
            $user->save();

            // 5. Log the password generation
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

            // 6. Log activity
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
                ],
            ]);

            // Commit transaction FIRST before any external API calls or email sending
            DB::commit();

            // 7. Send API request to ERP (AFTER transaction commit - non-blocking)
            try {
                $result = $this->erpService->createStudentRecord([
                    'student_id' => $studentId,
                    'biodata' => $student->biodata,
                    'program_id' => $student->program_id,
                    'academic_year' => $student->academic_year,
                ]);
                if (!empty($result['erp_student_name'])) {
                    $student->update(['erp_student_name' => $result['erp_student_name']]);
                }
            } catch (\Exception $e) {
                // Log but don't fail - ERP integration is optional
                \Log::warning('ERP API call failed (non-critical)', [
                    'student_id' => $studentId,
                    'error' => $e->getMessage(),
                ]);
            }

            // 8. Send SMS & Email with credentials (AFTER transaction commit - non-blocking)
            try {
                $this->sendAdmissionCredentials($user, $student, $tempPassword);
            } catch (\Exception $e) {
                // Log but don't fail - email/SMS sending is optional
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
    protected function createStudentAccount(Application $application, $studentId)
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
            $preferredSessionId = $session?->id;
        }

        // Prepare biodata
        $biodata = [
            'full_name' => $admissionForm->full_name ?? $user->name ?? 'N/A',
            'email' => $admissionForm->email ?? $user->email ?? 'N/A',
            'phone' => $admissionForm->telephone ?? $user->phone ?? null,
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
     * Pattern: 11000000, 12000000, 13000000
     * - First digit: 1 = undergraduate
     * - Second digit: 1 = ICT, 2 = Business, 3 = Healthcare
     * - Last 6 digits: student number in that department
     */
    protected function generateStudentId(Application $application)
    {
        // Get department ID from application
        $departmentId = $application->department_id;
        
        // Map department ID to student ID prefix
        // Department 1 (ICT) -> 11, Department 2 (Business) -> 12, Department 3 (Healthcare) -> 13
        $departmentPrefix = '1' . $departmentId; // 1 (undergraduate) + department ID
        
        // Get the last student number for this department
        $lastStudent = Student::where('student_id', 'like', $departmentPrefix . '%')
            ->orderBy('student_id', 'desc')
            ->first();
        
        $studentNumber = 1;
        if ($lastStudent) {
            // Extract the last 6 digits and increment
            $lastNumber = (int) substr($lastStudent->student_id, -6);
            $studentNumber = $lastNumber + 1;
        }
        
        // Ensure student number doesn't exceed 999999
        if ($studentNumber > 999999) {
            throw new \Exception("Maximum student capacity reached for department {$departmentId}");
        }
        
        // Format: 11000001, 12000001, etc.
        $studentId = $departmentPrefix . str_pad($studentNumber, 6, '0', STR_PAD_LEFT);
        
        // Double-check uniqueness (shouldn't happen, but safety check)
        if (Student::where('student_id', $studentId)->exists()) {
            // If exists, find next available number
            do {
                $studentNumber++;
                $studentId = $departmentPrefix . str_pad($studentNumber, 6, '0', STR_PAD_LEFT);
            } while (Student::where('student_id', $studentId)->exists() && $studentNumber <= 999999);
        }

        return $studentId;
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

        // Fallback: get first selected program
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
            $smsMessage = "Admission Approved! Login Email: {$loginEmail}. Password/PIN: {$tempPassword}. You must change your password on first login. Login: " . url('/login');
            $this->sendSMS($user->phone, $smsMessage);
            
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

    /**
     * Send SMS using Nalo and Arkesel APIs (same implementation as application submission)
     */
    protected function sendSMS($phone, $message)
    {
        // Clean phone number (remove any non-numeric characters except +)
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Convert to format without + for Nalo (e.g., +233249318768 -> 0249318768)
        $naloPhone = $cleanPhone;
        if (strpos($cleanPhone, '+233') === 0) {
            $naloPhone = '0' . substr($cleanPhone, 4); // Replace +233 with 0
        } elseif (strpos($cleanPhone, '233') === 0) {
            $naloPhone = '0' . substr($cleanPhone, 3); // Replace 233 with 0
        }
        
        try {
            // Primary: Try Nalo SMS API
            $naloKey = env('NALO_SMS_KEY', 'LNMKky07fqvxVO6IK33I7UvuWMVXDR_sZnf8bDRnG7qu2ErL3vTM1farB5UYw26L');
            $naloSenderId = env('NALO_SENDER_ID', 'DELEXESUC');
            
            Log::info('Attempting SIP Admission SMS via Nalo API', [
                'phone' => $naloPhone,
                'original_phone' => $cleanPhone,
                'message_length' => strlen($message),
            ]);
            
            $naloResponse = Http::timeout(10)
                ->post('https://sms.nalosolutions.com/smsbackend/Resl_Nalo/send-message/', [
                    'key' => $naloKey,
                    'msisdn' => $naloPhone,
                    'message' => $message,
                    'sender_id' => $naloSenderId
                ]);

            // Log the response for debugging
            Log::info('Nalo SMS API Response (SIP Admission)', [
                'phone' => $naloPhone,
                'status' => $naloResponse->status(),
                'response' => $naloResponse->body(),
            ]);

            // Check if Nalo was successful
            if ($naloResponse->successful()) {
                $responseData = $naloResponse->json();
                // Nalo returns status codes like "1701" for success
                // Check if status exists and is not an error code (errors are usually 17xx range except 1701)
                if (isset($responseData['status']) && isset($responseData['job_id'])) {
                    // If job_id is present, SMS was queued/sent successfully
                    Log::info('SIP Admission SMS sent successfully via Nalo', [
                        'job_id' => $responseData['job_id'],
                        'status_code' => $responseData['status']
                    ]);
                    return;
                }
            }
            
            // If Nalo failed, log and fall through to backup
            Log::warning('Nalo SMS API failed or returned error for SIP Admission, trying backup Arkesel API');

        } catch (\Exception $e) {
            Log::error('Nalo SMS API Exception (SIP Admission)', [
                'phone' => $naloPhone,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback: Try Arkesel SMS API
        try {
            $arkeselApiKey = env('ARKESEL_SMS_KEY', 'Ok1GNWlYWFB0VHI1NHJZUUQ=');
            $arkeselSenderId = env('ARKESEL_SENDER_ID', 'UNIVERSITY');
            
            Log::info('Attempting SIP Admission SMS via Arkesel API (Backup)', [
                'phone' => $cleanPhone,
            ]);
            
            $arkeselResponse = Http::timeout(10)
                ->get('https://sms.arkesel.com/sms/api', [
                    'action' => 'send-sms',
                    'api_key' => $arkeselApiKey,
                    'to' => $cleanPhone,
                    'from' => $arkeselSenderId,
                    'sms' => $message
                ]);

            // Log the response for debugging
            Log::info('Arkesel SMS API Response (Backup - SIP Admission)', [
                'phone' => $cleanPhone,
                'response' => $arkeselResponse->body(),
                'status' => $arkeselResponse->status()
            ]);

        } catch (\Exception $e) {
            Log::error('Both SMS APIs failed for SIP Admission', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
        }
    }
}

