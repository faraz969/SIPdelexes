<?php

namespace App\Http\Controllers;

use App\Models\CourseMaterial;
use App\Models\Lecturer;
use App\Models\SiteSetting;
use App\Services\CourseEnrollmentService;
use App\Services\CourseMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LecturerCourseMaterialController extends Controller
{
    protected CourseMaterialService $materialService;
    protected CourseEnrollmentService $enrollmentService;

    public function __construct(
        CourseMaterialService $materialService,
        CourseEnrollmentService $enrollmentService
    ) {
        $this->materialService = $materialService;
        $this->enrollmentService = $enrollmentService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $assignments = Lecturer::with(['course', 'session'])
            ->where('user_id', $user->id)
            ->orderBy('course_id')
            ->get();

        $options = $this->enrollmentService->filterOptions();
        $academicYear = trim((string) $request->get('academic_year', SiteSetting::currentAcademicYear()));
        $semester = trim((string) $request->get('semester', ''));

        $cards = $assignments->map(function (Lecturer $assignment) use ($academicYear, $semester) {
            $year = $academicYear !== ''
                ? $academicYear
                : ($assignment->academic_year ?: SiteSetting::currentAcademicYear());
            $sem = $semester !== ''
                ? $semester
                : ($assignment->semester ?: 'First Semester');

            $materials = CourseMaterial::where('course_id', $assignment->course_id)
                ->where('lecturer_id', $assignment->id)
                ->where('academic_year', $year)
                ->where('semester', $sem)
                ->ordered()
                ->get();

            return [
                'assignment' => $assignment,
                'academic_year' => $year,
                'semester' => $sem,
                'materials' => $materials,
                'count' => $materials->count(),
            ];
        });

        return view('lecturer.materials.index', [
            'cards' => $cards,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'semesters' => $options['semesters'],
            'academicYears' => $options['academicYears'],
        ]);
    }

    public function show(Request $request, Lecturer $lecturer)
    {
        $this->materialService->assertOwnsAssignment($lecturer);
        $lecturer->load(['course', 'session']);

        $options = $this->enrollmentService->filterOptions();
        $academicYear = trim((string) $request->get('academic_year', SiteSetting::currentAcademicYear()));
        $semester = trim((string) $request->get('semester', $lecturer->semester ?: 'First Semester'));

        $materials = CourseMaterial::where('course_id', $lecturer->course_id)
            ->where('lecturer_id', $lecturer->id)
            ->when($academicYear !== '', function ($q) use ($academicYear) {
                $q->where('academic_year', $academicYear);
            })
            ->when($semester !== '', function ($q) use ($semester) {
                $q->where('semester', $semester);
            })
            ->ordered()
            ->get();

        return view('lecturer.materials.show', [
            'lecturer' => $lecturer,
            'materials' => $materials,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'semesters' => $options['semesters'],
            'academicYears' => $options['academicYears'],
        ]);
    }

    public function store(Request $request, Lecturer $lecturer)
    {
        $this->materialService->assertOwnsAssignment($lecturer);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'academic_year' => 'required|string|max:50',
            'semester' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
            'file' => 'required|file|mimes:' . CourseMaterialService::ALLOWED_MIMES
                . '|max:' . CourseMaterialService::MAX_KILOBYTES,
        ]);

        $this->materialService->storeUpload(
            $lecturer,
            $request->file('file'),
            [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'academic_year' => $validated['academic_year'],
                'semester' => $validated['semester'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_published' => $request->boolean('is_published', true),
            ]
        );

        return redirect()
            ->route('lecturer.materials.show', [
                'lecturer' => $lecturer,
                'academic_year' => $validated['academic_year'],
                'semester' => $validated['semester'],
            ])
            ->with('success', 'Course material uploaded successfully.');
    }

    public function update(Request $request, Lecturer $lecturer, CourseMaterial $material)
    {
        $this->materialService->assertOwnsAssignment($lecturer);
        $this->assertMaterialBelongsToAssignment($lecturer, $material);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
        ]);

        $material->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_published' => $request->boolean('is_published', true),
        ]);

        return back()->with('success', 'Material updated.');
    }

    public function destroy(Lecturer $lecturer, CourseMaterial $material)
    {
        $this->materialService->assertOwnsAssignment($lecturer);
        $this->assertMaterialBelongsToAssignment($lecturer, $material);

        $year = $material->academic_year;
        $sem = $material->semester;
        $this->materialService->deleteMaterial($material);

        return redirect()
            ->route('lecturer.materials.show', [
                'lecturer' => $lecturer,
                'academic_year' => $year,
                'semester' => $sem,
            ])
            ->with('success', 'Material deleted.');
    }

    public function download(Lecturer $lecturer, CourseMaterial $material)
    {
        $this->materialService->assertOwnsAssignment($lecturer);
        $this->assertMaterialBelongsToAssignment($lecturer, $material);

        return $this->materialService->downloadResponse($material);
    }

    protected function assertMaterialBelongsToAssignment(Lecturer $lecturer, CourseMaterial $material): void
    {
        if ((int) $material->lecturer_id !== (int) $lecturer->id
            || (int) $material->course_id !== (int) $lecturer->course_id) {
            abort(403, 'This material does not belong to your course assignment.');
        }
    }
}
