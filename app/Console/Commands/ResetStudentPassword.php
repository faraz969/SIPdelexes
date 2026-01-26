<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetStudentPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sip:reset-password {student_id : Student ID or User ID} {--new-password= : Optional new password, otherwise generates random}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset password for a student (SIP account)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $identifier = $this->argument('student_id');
        
        // Try to find by student_id first, then by user_id
        $student = Student::where('student_id', $identifier)
            ->orWhere('user_id', $identifier)
            ->with('user')
            ->first();
        
        if (!$student) {
            $this->error("Student not found with ID: {$identifier}");
            return 1;
        }

        $user = $student->user;
        
        // Generate or use provided password
        $newPassword = $this->option('new-password') ?? Str::random(12);
        
        // Update both password and PIN to the same value
        // Set password_changed_at to null to force password change on next login
        $user->password = Hash::make($newPassword);
        $user->pin = $newPassword; // Store PIN in plain text for SMS/display
        $user->password_changed_at = null; // Force password change on next login
        $user->save();
        
        // Log the password reset
        \Log::info("Student Password Reset", [
            'student_id' => $student->student_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'serial_number' => $user->serial_number,
            'new_password' => $newPassword,
            'new_pin' => $newPassword,
            'reset_by' => 'command',
            'reset_at' => now()->toDateTimeString(),
        ]);
        
        $this->info("Password reset successfully!");
        $this->info("Student ID: {$student->student_id}");
        $this->info("Name: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Serial Number: {$user->serial_number}");
        $this->newLine();
        $this->info("═══════════════════════════════════════");
        $this->info("NEW LOGIN CREDENTIALS:");
        $this->info("═══════════════════════════════════════");
        $this->info("Login with:");
        $this->line("  Email: {$user->email}");
        $this->line("  OR Serial Number: {$user->serial_number}");
        $this->newLine();
        $this->info("Password: {$newPassword}");
        $this->info("═══════════════════════════════════════");
        $this->newLine();
        $this->warn("Please share these credentials with the student securely!");
        
        return 0;
    }
}

