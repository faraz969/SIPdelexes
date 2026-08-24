<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SemesterCourseOffering extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'academic_year',
        'semester',
        'course_ids',
        'is_published',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'course_ids' => 'array',
        'is_published' => 'boolean',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    /**
     * Active Course models for this offering, in the stored order.
     */
    public function courses()
    {
        $ids = collect($this->course_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $courses = Course::whereIn('id', $ids->all())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return $ids->map(fn ($id) => $courses->get($id))->filter()->values();
    }

    public function totalCreditUnits(): float
    {
        return (float) $this->courses()->sum(function (Course $course) {
            return (float) ($course->total_credit_units ?? $course->credit_units);
        });
    }

    public function coursesPayload(): array
    {
        return $this->courses()->map(function (Course $course) {
            return [
                'id' => $course->id,
                'course_code' => $course->course_code,
                'course_title' => $course->course_title,
                'credit_units' => (float) ($course->total_credit_units ?? $course->credit_units),
                'is_elective' => (bool) $course->is_elective,
            ];
        })->values()->toArray();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForProgramSemester($query, int $programId, string $semester, string $academicYear)
    {
        return $query->where('program_id', $programId)
            ->where('semester', $semester)
            ->where('academic_year', $academicYear);
    }
}
