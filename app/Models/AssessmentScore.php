<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_result_sheet_id',
        'course_assessment_component_id',
        'student_id',
        'raw_mark',
        'contribution_mark',
        'special_status',
        'source',
        'entered_by',
        'remarks',
    ];

    protected $casts = [
        'raw_mark' => 'decimal:2',
        'contribution_mark' => 'decimal:2',
    ];

    public function sheet()
    {
        return $this->belongsTo(CourseResultSheet::class, 'course_result_sheet_id');
    }

    public function component()
    {
        return $this->belongsTo(CourseAssessmentComponent::class, 'course_assessment_component_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function enteredBy()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
