<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_result_sheet_id',
        'course_assessment_component_id',
        'filename',
        'uploaded_by',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'imported_rows',
        'status',
    ];

    public function sheet()
    {
        return $this->belongsTo(CourseResultSheet::class, 'course_result_sheet_id');
    }

    public function component()
    {
        return $this->belongsTo(CourseAssessmentComponent::class, 'course_assessment_component_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function errors()
    {
        return $this->hasMany(ResultImportError::class);
    }
}
