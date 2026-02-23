<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('program');
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }
        if ($request->filled('type') && $request->type !== 'all') {
            if ($request->type === 'core') {
                $query->where('is_elective', false);
            } else {
                $query->where('is_elective', true);
            }
        }
        $courses = $query->orderBy('program_id')->orderBy('sort_order')->orderBy('course_code')->get();
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        return view('admin.courses.index', compact('courses', 'programs'));
    }

    public function create()
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        return view('admin.courses.create', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_code' => 'required|string|max:50|unique:courses,course_code',
            'course_title' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'academic_year' => 'nullable|string|max:50',
            'semester' => 'nullable|string|max:100',
            'credit_units' => 'required|numeric|min:0|max:99',
            'total_credit_units' => 'nullable|numeric|min:0|max:99',
            'assessment_split' => 'nullable|string|max:255',
            'is_elective' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);
        $validated['is_elective'] = $request->boolean('is_elective');
        $validated['is_active'] = $request->boolean('is_active', true);
        Course::create($validated);
        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully.');
    }

    public function show(Course $course)
    {
        $course->load('program');
        return view('admin.courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        return view('admin.courses.edit', compact('course', 'programs'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'course_code' => 'required|string|max:50|unique:courses,course_code,' . $course->id,
            'course_title' => 'required|string|max:255',
            'program_id' => 'required|exists:programs,id',
            'academic_year' => 'nullable|string|max:50',
            'semester' => 'nullable|string|max:100',
            'credit_units' => 'required|numeric|min:0|max:99',
            'total_credit_units' => 'nullable|numeric|min:0|max:99',
            'assessment_split' => 'nullable|string|max:255',
            'is_elective' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);
        $validated['is_elective'] = $request->boolean('is_elective');
        $validated['is_active'] = $request->boolean('is_active', true);
        $course->update($validated);
        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }

    /**
     * Show CSV upload form
     */
    public function uploadForm()
    {
        $programs = Program::where('is_active', true)->orderBy('name')->get();
        return view('admin.courses.upload', compact('programs'));
    }

    /**
     * Process CSV upload and create/update courses
     */
    public function uploadProcess(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return redirect()->route('admin.courses.upload')
                ->with('error', 'Could not read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return redirect()->route('admin.courses.upload')
                ->with('error', 'The CSV file is empty or has no header row.');
        }

        $header = array_map('trim', $header);
        $programsByName = Program::where('is_active', true)->pluck('id', 'name')->toArray();

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($row) < 3) {
                $skipped++;
                continue;
            }

            $data = array_combine(array_slice($header, 0, count($row)), $row);
            if ($data === false) {
                $errors[] = "Row {$rowNum}: column count does not match header.";
                continue;
            }

            $data = array_map('trim', $data);

            $courseCode = $data['course_code'] ?? $data['course code'] ?? '';
            $courseTitle = $data['course_title'] ?? $data['course title'] ?? $data['course_title'] ?? '';
            $programId = $data['program_id'] ?? null;
            if (!$programId && !empty($data['program'] ?? $data['program_name'] ?? null)) {
                $programName = $data['program'] ?? $data['program_name'] ?? '';
                $programId = $programsByName[$programName] ?? null;
            }
            $programId = $programId ? (int) $programId : null;
            $academicYear = $data['academic_year'] ?? $data['academic year'] ?? null;
            $semester = $data['semester'] ?? null;
            $creditUnits = isset($data['credit_units']) ? (float) $data['credit_units'] : (isset($data['credit units']) ? (float) $data['credit units'] : 0);
            $totalCreditUnits = !empty($data['total_credit_units']) ? (float) $data['total_credit_units'] : null;
            $assessmentSplit = $data['assessment_split'] ?? $data['assessment split'] ?? null;
            $isElective = $this->parseBool($data['is_elective'] ?? $data['elective'] ?? '0');
            $isActive = $this->parseBool($data['is_active'] ?? $data['active'] ?? '1');
            $sortOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : 0;

            if (empty($courseCode) || empty($courseTitle)) {
                $errors[] = "Row {$rowNum}: course_code and course_title are required.";
                continue;
            }
            if (!$programId || !Program::where('id', $programId)->exists()) {
                $errors[] = "Row {$rowNum}: invalid or missing program_id/program.";
                continue;
            }

            $validator = Validator::make([
                'course_code' => $courseCode,
                'course_title' => $courseTitle,
                'credit_units' => $creditUnits,
            ], [
                'course_code' => 'required|string|max:50',
                'course_title' => 'required|string|max:255',
                'credit_units' => 'numeric|min:0|max:99',
            ]);
            if ($validator->fails()) {
                $errors[] = "Row {$rowNum}: " . implode(' ', $validator->errors()->all());
                continue;
            }

            $existing = Course::where('course_code', $courseCode)->first();
            $payload = [
                'course_code' => $courseCode,
                'course_title' => $courseTitle,
                'program_id' => $programId,
                'academic_year' => $academicYear ?: null,
                'semester' => $semester ?: null,
                'credit_units' => $creditUnits,
                'total_credit_units' => $totalCreditUnits,
                'assessment_split' => $assessmentSplit ?: null,
                'is_elective' => $isElective,
                'is_active' => $isActive,
                'sort_order' => $sortOrder,
            ];

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                Course::create($payload);
                $created++;
            }
        }
        fclose($handle);

        $message = "Import complete: {$created} created, {$updated} updated.";
        if ($skipped) {
            $message .= " {$skipped} rows skipped.";
        }
        if (count($errors) > 0) {
            $message .= ' ' . count($errors) . ' error(s).';
            return redirect()->route('admin.courses.upload')
                ->with('warning', $message)
                ->with('import_errors', array_slice($errors, 0, 20));
        }

        return redirect()->route('admin.courses.index')->with('success', $message);
    }

    private function parseBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $v = strtolower(trim((string) $value));
        return in_array($v, ['1', 'true', 'yes', 'y', 'on'], true);
    }

    /**
     * Download sample CSV for course import
     */
    public function downloadSampleCsv(): StreamedResponse
    {
        $program = Program::where('is_active', true)->first();
        $programId = $program ? $program->id : 1;

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="courses_sample.csv"',
        ];

        return new StreamedResponse(function () use ($programId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'course_code', 'course_title', 'program_id', 'academic_year', 'semester',
                'credit_units', 'total_credit_units', 'assessment_split', 'is_elective', 'is_active', 'sort_order',
            ]);
            fputcsv($handle, [
                'CS101', 'Introduction to Computer Science', $programId, '2025-2026', 'First Semester',
                3, null, 'Class 30%, Exam 70%', 0, 1, 0,
            ]);
            fputcsv($handle, [
                'CS102', 'Programming Fundamentals', $programId, '2025-2026', 'First Semester',
                3, null, 'Class 30%, Exam 70%', 0, 1, 1,
            ]);
            fputcsv($handle, [
                'MATH201', 'Mathematics for Computing', $programId, '2025-2026', 'First Semester',
                4, null, 'Class 25%, Exam 75%', 0, 1, 2,
            ]);
            fclose($handle);
        }, 200, $headers);
    }
}
