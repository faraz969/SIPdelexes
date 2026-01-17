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

            // 4. Generate temporary password
            $tempPassword = Str::random(12);
            $user = $application->user;
            
            // Update both password and PIN to the same value
            $user->password = Hash::make($tempPassword);
            $user->pin = $tempPassword; // Store PIN in plain text for SMS/display
            $user->save();

            // 5. Log the password generation
            \Log::info("SIP Account Created - Login Credentials", [
                'student_id' => $studentId,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'serial_number' => $user->serial_number,
                'password' => $tempPassword,
                'pin' => $tempPassword,
                'created_by' => auth()->id() ?? 'system',
                'created_at' => now()->toDateTimeString(),
            ]);

            // 6. Send SMS & Email with credentials
            $this->sendAdmissionCredentials($user, $student, $tempPassword);

            // 7. Log activity
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
     */
    protected function generateStudentId(Application $application)
    {
        $year = date('y');
        $prefix = 'STU';
        
        do {
            $random = str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $studentId = $prefix . $year . $random;
        } while (Student::where('student_id', $studentId)->exists());

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
        // Prepare login options message
        $loginOptions = "Login with Student ID: {$student->student_id}, Email: {$user->email}, or Serial: {$user->serial_number}";
        
        // Send Email
        try {
            Mail::send('emails.admission-approval', [
                'user' => $user,
                'student' => $student,
                'password' => $tempPassword,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Admission Approved - SIP Login Credentials');
            });
            
            \Log::info("Admission approval email sent successfully", [
                'student_id' => $student->student_id,
                'user_email' => $user->email,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send admission email', [
                'student_id' => $student->student_id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Send SMS with credentials
        try {
            $smsMessage = "Admission Approved! Student ID: {$student->student_id}. Password/PIN: {$tempPassword}. Login: " . url('/login') . " - {$loginOptions}";
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

