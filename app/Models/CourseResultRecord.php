<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseResultRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_result_sheet_id',
        'student_id',
        'class_mark',
        'exam_mark',
        'final_mark',
        'grade',
        'grade_point',
        'credit_hours',
        'quality_points',
        'special_status',
        'version',
    ];

    protected $casts = [
        'class_mark' => 'decimal:2',
        'exam_mark' => 'decimal:2',
        'final_mark' => 'decimal:2',
        'grade_point' => 'decimal:2',
        'credit_hours' => 'decimal:2',
        'quality_points' => 'decimal:2',
    ];

    public function sheet()
    {
        return $this->belongsTo(CourseResultSheet::class, 'course_result_sheet_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
