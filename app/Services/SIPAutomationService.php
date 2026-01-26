<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Application;
use App\Models\User;
use App\Models\Program;
use App\Services\ERPIntegrationService;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

            // 3. Send API request to ERP
            $erpResponse = $this->erpService->createStudentRecord([
                'student_id' => $studentId,
                'biodata' => $student->biodata,
                'program_id' => $student->program_id,
                'academic_year' => $student->academic_year,
            ]);

            // 4. Create student email and update user email
            $studentEmail = $studentId . '@delexesuniversity.edu.gh';
            $user = $application->user;
            $user->email = $studentEmail;
            
            // 5. Generate temporary password
            $tempPassword = Str::random(12);
            
            // Update both password and PIN to the same value
            // Set password_changed_at to null to force password change on first login
            $user->password = Hash::make($tempPassword);
            $user->pin = $tempPassword; // Store PIN in plain text for SMS/display
            $user->password_changed_at = null; // Force password change on first login
            $user->save();

            // 6. Log the password generation
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

            // 7. Send SMS & Email with credentials
            $this->sendAdmissionCredentials($user, $student, $tempPassword);

            // 8. Log activity
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

            DB::commit();
            return $student;
        } catch (\Exception $e) {
            DB::rollBack();
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
     */
    protected function sendAdmissionCredentials(User $user, Student $student, string $tempPassword)
    {
        // Student must login with Student_ID@delexesuniversity.edu.gh
        $loginEmail = $student->student_id . '@delexesuniversity.edu.gh';
        
        // Send Email
        try {
            Mail::send('emails.admission-approval', [
                'user' => $user,
                'student' => $student,
                'password' => $tempPassword,
                'login_email' => $loginEmail,
            ], function ($message) use ($user, $loginEmail) {
                // Try to send to both old and new email
                $message->to($loginEmail)
                    ->subject('Admission Approved - SIP Login Credentials');
                
                // Also try to send to original email if different
                if ($user->email !== $loginEmail) {
                    try {
                        $message->cc($user->email);
                    } catch (\Exception $e) {
                        // Ignore if original email is invalid
                    }
                }
            });
            
            \Log::info("Admission approval email sent successfully", [
                'student_id' => $student->student_id,
                'student_email' => $loginEmail,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send admission email', [
                'student_id' => $student->student_id,
                'student_email' => $loginEmail,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Send SMS with credentials
        try {
            $smsMessage = "Admission Approved! Login Email: {$loginEmail}. Password/PIN: {$tempPassword}. You must change your password on first login. Login: " . url('/login');
            $this->sendSMS($user->phone, $smsMessage);
            
            \Log::info("Admission approval SMS sent successfully", [
                'student_id' => $student->student_id,
                'user_phone' => $user->phone,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send admission SMS', [
                'student_id' => $student->student_id,
                'user_phone' => $user->phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send SMS (placeholder - implement with your SMS provider)
     */
    protected function sendSMS($phone, $message)
    {
        // TODO: Implement SMS sending logic
        // Example: Use Twilio, Nexmo, or your SMS provider
        
        // Log SMS details
        \Log::info("SMS Notification", [
            'phone' => $phone,
            'message' => $message,
            'message_length' => strlen($message),
            'timestamp' => now()->toDateTimeString(),
        ]);
        
        // Placeholder for actual SMS sending
        // Uncomment and configure when SMS provider is set up:
        /*
        try {
            // Example with Twilio:
            // $twilio = new \Twilio\Rest\Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
            // $twilio->messages->create($phone, [
            //     'from' => env('TWILIO_FROM'),
            //     'body' => $message
            // ]);
            
            // Example with HTTP API:
            // Http::post('https://your-sms-provider.com/api/send', [
            //     'phone' => $phone,
            //     'message' => $message,
            //     'api_key' => env('SMS_API_KEY'),
            // ]);
        } catch (\Exception $e) {
            \Log::error('SMS sending failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
        */
    }
}

