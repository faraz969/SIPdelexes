<?php

namespace App\Services;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class TranscriptPdfService
{
    protected ResultsApprovalService $resultsService;

    public function __construct(ResultsApprovalService $resultsService)
    {
        $this->resultsService = $resultsService;
    }

    /**
     * Build view data for official transcript (HTML or PDF).
     *
     * @return array{student: Student, results: array, meta: array, logoSrc: ?string, photoSrc: ?string, printedOn: string, verificationCode: string}|null
     */
    public function buildPayload(Student $student): ?array
    {
        $student->loadMissing(['user', 'program', 'application.admissionForm']);
        $results = $this->resultsService->studentPublishedResults($student);

        if (empty($results['semesters'])) {
            return null;
        }

        $admissionForm = optional($student->application)->admissionForm;
        $biodata = is_array($student->biodata) ? $student->biodata : [];

        $dobRaw = $biodata['dob'] ?? optional($admissionForm)->dob;
        $dob = '—';
        if ($dobRaw) {
            try {
                $dob = Carbon::parse($dobRaw)->format('j/n/Y');
            } catch (\Exception $e) {
                $dob = (string) $dobRaw;
            }
        }

        $genderRaw = $biodata['gender'] ?? optional($admissionForm)->gender;
        $sex = '—';
        if ($genderRaw) {
            $g = strtoupper(substr(trim((string) $genderRaw), 0, 1));
            $sex = $g === 'M' ? 'MALE' : ($g === 'F' ? 'FEMALE' : strtoupper((string) $genderRaw));
        }

        $periodStart = $student->admission_date
            ? strtoupper($student->admission_date->format('MY'))
            : '—';
        $lastSemester = end($results['semesters']);
        $periodEnd = $lastSemester['academic_year'] ?? now()->format('Y');
        if (preg_match('/(\d{4})\s*\/\s*(\d{2,4})/', (string) $periodEnd, $m)) {
            $endYear = strlen($m[2]) === 2 ? ('20' . $m[2]) : $m[2];
            $periodEnd = 'MAY' . $endYear;
        } else {
            $periodEnd = strtoupper(now()->format('MY'));
        }

        $meta = [
            'name' => $biodata['full_name'] ?? ($student->user->name ?? '—'),
            'dob' => $dob,
            'sex' => $sex,
            'programme' => $student->program->name ?? '—',
            'student_number' => $student->student_id,
            'period' => $periodStart . ' - ' . $periodEnd,
        ];

        $logoPath = public_path('images/logo_blue.png');
        if (!file_exists($logoPath)) {
            $logoPath = public_path('images/logo.png');
        }

        $verificationCode = strtoupper(substr(hash(
            'sha256',
            $student->id . '|' . $student->student_id . '|' . now()->format('YmdHis')
        ), 0, 32));
        $verificationCode = implode('-', str_split($verificationCode, 8));

        return [
            'student' => $student,
            'results' => $results,
            'meta' => $meta,
            'logoSrc' => file_exists($logoPath) ? $logoPath : null,
            'photoSrc' => $this->resolveStudentPhotoSrc($admissionForm),
            'printedOn' => now()->format('l, F j, Y'),
            'verificationCode' => $verificationCode,
        ];
    }

    public function downloadPdf(Student $student)
    {
        $payload = $this->buildPayload($student);
        if (!$payload) {
            return null;
        }

        return Pdf::loadView('sip.results.pdf', $payload)
            ->setPaper('a4', 'portrait')
            ->setOption('enable-remote', false)
            ->download('Official_Transcript_' . $student->student_id . '.pdf');
    }

    protected function resolveStudentPhotoSrc($admissionForm): ?string
    {
        if (!$admissionForm || !is_array($admissionForm->uploads ?? null)) {
            return null;
        }

        $relative = $admissionForm->uploads['passport_picture'] ?? null;
        if (empty($relative)) {
            return null;
        }

        $fullPath = storage_path('app/public/' . ltrim($relative, '/'));
        if (!file_exists($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: 'image/jpeg';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
}
