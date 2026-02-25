<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\Session;
use Illuminate\Console\Command;

class BackfillStudentPreferredSession extends Command
{
    protected $signature = 'students:backfill-preferred-session';

    protected $description = 'Backfill preferred_session_id for existing students from their application admission form';

    public function handle(): int
    {
        $students = Student::whereNull('preferred_session_id')
            ->whereNotNull('application_id')
            ->with('application.admissionForm')
            ->get();

        $updated = 0;
        foreach ($students as $student) {
            $sessionName = $student->application?->admissionForm?->preferred_session
                ?? $student->application?->data['preferred_session'] ?? null;

            if (!$sessionName) {
                continue;
            }

            $session = Session::where('name', $sessionName)->first();
            if ($session) {
                $student->update(['preferred_session_id' => $session->id]);
                $updated++;
            }
        }

        $this->info("Backfilled preferred_session_id for {$updated} student(s).");
        return self::SUCCESS;
    }
}
