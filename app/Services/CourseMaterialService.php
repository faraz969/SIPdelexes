<?php

namespace App\Services;

use App\Models\CourseMaterial;
use App\Models\CourseRegistration;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CourseMaterialService
{
    public const ALLOWED_MIMES = 'pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,gif,webp,mp4,mov,avi,webm,mkv,zip';

    /** Max upload size in kilobytes (100 MB). */
    public const MAX_KILOBYTES = 102400;

    public function assertOwnsAssignment(Lecturer $assignment, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if ((int) $assignment->user_id !== (int) $userId) {
            abort(403, 'Unauthorized lecturer assignment.');
        }
    }

    public function assertOwnsMaterial(CourseMaterial $material, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if ((int) $material->uploaded_by !== (int) $userId) {
            abort(403, 'You can only manage materials you uploaded.');
        }
    }

    public function studentCanAccess(Student $student, CourseMaterial $material): bool
    {
        if (!$material->is_published) {
            return false;
        }

        return $this->studentIsRegisteredForCourse(
            $student,
            (int) $material->course_id,
            $material->academic_year,
            $material->semester
        );
    }

    public function assertStudentCanAccess(Student $student, CourseMaterial $material): void
    {
        if (!$this->studentCanAccess($student, $material)) {
            abort(403, 'You are not registered for this course material.');
        }
    }

    /**
     * Student has a registration containing this course (optionally matching year/semester).
     */
    public function studentIsRegisteredForCourse(
        Student $student,
        int $courseId,
        ?string $academicYear = null,
        ?string $semester = null
    ): bool {
        $query = CourseRegistration::where('student_id', $student->id)
            ->whereIn('status', ['registered', 'late', 'pending']);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }
        if ($semester) {
            $query->where('semester', $semester);
        }

        $registrations = $query->get();
        foreach ($registrations as $registration) {
            foreach (($registration->courses ?? []) as $item) {
                if ((int) ($item['id'] ?? 0) === $courseId) {
                    return true;
                }
            }
        }

        return false;
    }

    public function storeUpload(
        Lecturer $assignment,
        UploadedFile $file,
        array $data
    ): CourseMaterial {
        $path = $file->store(
            'course-materials/' . $assignment->course_id,
            'public'
        );

        return CourseMaterial::create([
            'course_id' => $assignment->course_id,
            'lecturer_id' => $assignment->id,
            'uploaded_by' => Auth::id(),
            'academic_year' => $data['academic_year'] ?? null,
            'semester' => $data['semester'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'is_published' => array_key_exists('is_published', $data)
                ? (bool) $data['is_published']
                : true,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
    }

    public function deleteMaterial(CourseMaterial $material): void
    {
        $this->deleteStoredFile($material->file_path);
        $material->delete();
    }

    public function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function downloadResponse(CourseMaterial $material)
    {
        if (!Storage::disk('public')->exists($material->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download(
            $material->file_path,
            $material->original_filename
        );
    }
}
