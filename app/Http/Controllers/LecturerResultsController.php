<?php

namespace App\Http\Controllers;

use App\Models\CourseAssessmentComponent;
use App\Models\CourseResultSheet;
use App\Models\Lecturer;
use App\Models\SiteSetting;
use App\Services\CourseEnrollmentService;
use App\Services\LecturerResultsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LecturerResultsController extends Controller
{
    protected LecturerResultsService $resultsService;
    protected CourseEnrollmentService $enrollmentService;

    public function __construct(
        LecturerResultsService $resultsService,
        CourseEnrollmentService $enrollmentService
    ) {
        $this->resultsService = $resultsService;
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * List lecturer assignments with result shortcuts.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $assignments = Lecturer::with(['course.assessmentComponents', 'session'])
            ->where('user_id', $user->id)
            ->orderBy('course_id')
            ->get();

        $options = $this->enrollmentService->filterOptions();
        $academicYear = trim((string) $request->get('academic_year', SiteSetting::currentAcademicYear()));
        $semester = trim((string) $request->get('semester', ''));

        $cards = $assignments->map(function (Lecturer $assignment) use ($academicYear, $semester) {
            $year = $academicYear !== ''
                ? $academicYear
                : ($assignment->academic_year ?: $assignment->course->academic_year ?: SiteSetting::currentAcademicYear());
            $sem = $semester !== ''
                ? $semester
                : ($assignment->semester ?: $assignment->course->semester ?: 'First Semester');

            $sheet = CourseResultSheet::where('course_id', $assignment->course_id)
                ->where('academic_year', $year)
                ->where('semester', $sem)
                ->orderByDesc('version')
                ->first();

            $roster = collect();
            $progress = null;
            try {
                $roster = $this->resultsService->getRoster($assignment, $year, $sem);
                if ($sheet) {
                    $progress = $this->resultsService->progress($sheet, $roster);
                }
            } catch (\Throwable $e) {
                // ignore preview errors
            }

            return [
                'assignment' => $assignment,
                'academic_year' => $year,
                'semester' => $sem,
                'sheet' => $sheet,
                'student_count' => $roster->count(),
                'progress' => $progress,
                'component_count' => $assignment->course->assessmentComponents->where('is_active', true)->count(),
            ];
        });

        return view('lecturer.results.index', [
            'cards' => $cards,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'semesters' => $options['semesters'],
            'academicYears' => $options['academicYears'],
        ]);
    }

    /**
     * Open / create result sheet for an assignment.
     */
    public function open(Request $request, Lecturer $lecturer)
    {
        $this->resultsService->assertOwnsAssignment($lecturer);

        $request->validate([
            'academic_year' => 'required|string|max:50',
            'semester' => 'required|string|max:100',
        ]);

        try {
            $sheet = $this->resultsService->getOrCreateSheet(
                $lecturer,
                $request->academic_year,
                $request->semester
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('lecturer.results.index')
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('lecturer.results.sheet', [$lecturer, $sheet]);
    }

    public function sheet(Lecturer $lecturer, CourseResultSheet $sheet)
    {
        $this->authorizeSheet($lecturer, $sheet);

        $roster = $this->resultsService->getRoster($lecturer, $sheet->academic_year, $sheet->semester);
        $grid = $this->resultsService->buildResultGrid($sheet, $roster);
        $progress = $this->resultsService->progress($sheet, $roster);

        return view('lecturer.results.sheet', [
            'lecturer' => $lecturer->load(['course', 'session']),
            'sheet' => $sheet,
            'components' => $grid['components'],
            'rows' => $grid['rows'],
            'progress' => $progress,
        ]);
    }

    public function enterForm(Lecturer $lecturer, CourseResultSheet $sheet, CourseAssessmentComponent $component)
    {
        $this->authorizeSheet($lecturer, $sheet);
        $this->authorizeComponent($sheet, $component);

        if (!$sheet->isEditableByLecturer()) {
            return redirect()->route('lecturer.results.sheet', [$lecturer, $sheet])
                ->with('error', 'This sheet is locked and cannot be edited.');
        }

        $roster = $this->resultsService->getRoster($lecturer, $sheet->academic_year, $sheet->semester);
        $sheet->load('scores');
        $existing = $sheet->scores
            ->where('course_assessment_component_id', $component->id)
            ->keyBy('student_id');

        return view('lecturer.results.enter', compact('lecturer', 'sheet', 'component', 'roster', 'existing'));
    }

    public function saveMarks(Request $request, Lecturer $lecturer, CourseResultSheet $sheet, CourseAssessmentComponent $component)
    {
        $this->authorizeSheet($lecturer, $sheet);
        $this->authorizeComponent($sheet, $component);

        $request->validate([
            'marks' => 'nullable|array',
        ]);

        $roster = $this->resultsService->getRoster($lecturer, $sheet->academic_year, $sheet->semester);
        $allowedIds = $roster->pluck('id')->all();
        $marks = [];
        foreach ($request->input('marks', []) as $studentId => $value) {
            if (!in_array((int) $studentId, $allowedIds, true)) {
                continue;
            }
            $marks[(int) $studentId] = $value;
        }

        try {
            $saved = $this->resultsService->saveComponentMarks($sheet, $component, $marks, 'manual');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors())->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('lecturer.results.sheet', [$lecturer, $sheet])
            ->with('success', "Saved marks for {$saved} student(s) on {$component->name}.");
    }

    public function csvForm(Lecturer $lecturer, CourseResultSheet $sheet, CourseAssessmentComponent $component)
    {
        $this->authorizeSheet($lecturer, $sheet);
        $this->authorizeComponent($sheet, $component);

        if (!$sheet->isEditableByLecturer()) {
            return redirect()->route('lecturer.results.sheet', [$lecturer, $sheet])
                ->with('error', 'This sheet is locked and cannot be edited.');
        }

        return view('lecturer.results.csv', compact('lecturer', 'sheet', 'component'));
    }

    public function downloadTemplate(Lecturer $lecturer, CourseResultSheet $sheet, CourseAssessmentComponent $component): StreamedResponse
    {
        $this->authorizeSheet($lecturer, $sheet);
        $this->authorizeComponent($sheet, $component);

        $roster = $this->resultsService->getRoster($lecturer, $sheet->academic_year, $sheet->semester);
        $csv = $this->resultsService->generateCsvTemplate($sheet, $component, $roster);
        $filename = 'marks_' . $sheet->course->course_code . '_' . $component->code . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function validateCsv(Request $request, Lecturer $lecturer, CourseResultSheet $sheet, CourseAssessmentComponent $component)
    {
        $this->authorizeSheet($lecturer, $sheet);
        $this->authorizeComponent($sheet, $component);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $file = $request->file('csv_file');
        $contents = file_get_contents($file->getRealPath());
        $roster = $this->resultsService->getRoster($lecturer, $sheet->academic_year, $sheet->semester);

        try {
            $preview = $this->resultsService->validateCsv($sheet, $component, $roster, $contents);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        // Store CSV temporarily in session for confirm step
        $token = uniqid('csv_', true);
        $request->session()->put('lecturer_csv_' . $token, [
            'contents' => $contents,
            'filename' => $file->getClientOriginalName(),
            'sheet_id' => $sheet->id,
            'component_id' => $component->id,
        ]);

        return view('lecturer.results.csv-preview', [
            'lecturer' => $lecturer,
            'sheet' => $sheet,
            'component' => $component,
            'preview' => $preview,
            'token' => $token,
        ]);
    }

    public function importCsv(Request $request, Lecturer $lecturer, CourseResultSheet $sheet, CourseAssessmentComponent $component)
    {
        $this->authorizeSheet($lecturer, $sheet);
        $this->authorizeComponent($sheet, $component);

        $request->validate([
            'token' => 'required|string',
            'allow_overwrite' => 'nullable|boolean',
        ]);

        $payload = $request->session()->get('lecturer_csv_' . $request->token);
        if (!$payload || (int) $payload['sheet_id'] !== (int) $sheet->id || (int) $payload['component_id'] !== (int) $component->id) {
            return redirect()->route('lecturer.results.csv', [$lecturer, $sheet, $component])
                ->with('error', 'CSV preview expired. Please upload again.');
        }

        $roster = $this->resultsService->getRoster($lecturer, $sheet->academic_year, $sheet->semester);

        try {
            $batch = $this->resultsService->importCsv(
                $sheet,
                $component,
                $roster,
                $payload['contents'],
                $payload['filename'],
                $request->boolean('allow_overwrite')
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('lecturer.results.csv', [$lecturer, $sheet, $component])
                ->with('error', collect($e->errors())->flatten()->first());
        }

        $request->session()->forget('lecturer_csv_' . $request->token);

        return redirect()->route('lecturer.results.sheet', [$lecturer, $sheet])
            ->with('success', "CSV imported: {$batch->imported_rows} row(s). Batch #{$batch->id}.");
    }

    public function submit(Request $request, Lecturer $lecturer, CourseResultSheet $sheet)
    {
        $this->authorizeSheet($lecturer, $sheet);

        try {
            $this->resultsService->submitSheet($sheet);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('lecturer.results.sheet', [$lecturer, $sheet])
            ->with('success', 'Result sheet submitted for HOD approval. Editing is now locked.');
    }

    protected function authorizeSheet(Lecturer $lecturer, CourseResultSheet $sheet): void
    {
        $this->resultsService->assertOwnsAssignment($lecturer);
        if ((int) $sheet->course_id !== (int) $lecturer->course_id) {
            abort(403, 'Result sheet does not belong to this assignment.');
        }
        $lecturer->loadMissing(['course', 'session']);
        $sheet->loadMissing('course');
    }

    protected function authorizeComponent(CourseResultSheet $sheet, CourseAssessmentComponent $component): void
    {
        if ((int) $component->course_id !== (int) $sheet->course_id || !$component->is_active) {
            abort(404, 'Assessment component not found for this course.');
        }
    }
}
