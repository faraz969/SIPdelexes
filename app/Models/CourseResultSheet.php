<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseResultSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'academic_year',
        'semester',
        'lecturer_user_id',
        'status',
        'version',
        'return_comments',
        'submitted_at',
        'hod_approved_at',
        'approved_at',
        'published_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'hod_approved_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const STATUSES = [
        'draft',
        'submitted',
        'returned',
        'hod_approved',
        'approved',
        'published',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_user_id');
    }

    public function scores()
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function records()
    {
        return $this->hasMany(CourseResultRecord::class);
    }

    public function approvals()
    {
        return $this->hasMany(ResultApproval::class);
    }

    public function isEditableByLecturer(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true);
    }
}
