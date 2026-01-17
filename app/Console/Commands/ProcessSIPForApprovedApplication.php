<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Application;
use App\Services\SIPAutomationService;

class ProcessSIPForApprovedApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sip:process {application_id : The ID of the approved application}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually process SIP automation for an approved application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $applicationId = $this->argument('application_id');
        
        $application = Application::with(['user', 'admissionForm'])->find($applicationId);
        
        if (!$application) {
            $this->error("Application with ID {$applicationId} not found.");
            return 1;
        }

        if ($application->registrar_status !== 'approved') {
            $this->error("Application is not approved by registrar. Current status: {$application->registrar_status}");
            return 1;
        }

        // Check if student already exists
        $existingStudent = \App\Models\Student::where('user_id', $application->user_id)->first();
        if ($existingStudent) {
            $this->warn("Student account already exists for this application (Student ID: {$existingStudent->student_id})");
            if (!$this->confirm('Do you want to continue anyway?', false)) {
                return 0;
            }
        }

        $this->info("Processing SIP automation for application #{$applicationId}...");

        try {
            $sipService = app(SIPAutomationService::class);
            $student = $sipService->processAdmissionApproval($application);
            
            $this->info("✓ SIP account created successfully!");
            $this->info("  Student ID: {$student->student_id}");
            $this->info("  User: {$application->user->name} ({$application->user->email})");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error processing SIP automation: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

