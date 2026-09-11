<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAssessmentComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'code',
        'name',
        'category',
        'max_mark',
        'contribution',
        'sequence',
        'is_active',
    ];

    protected $casts = [
        'max_mark' => 'decimal:2',
        'contribution' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public const CATEGORIES = ['class', 'exam'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function scores()
    {
        return $this->hasMany(AssessmentScore::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeClassCategory($query)
    {
        return $query->where('category', 'class');
    }

    public function scopeExamCategory($query)
    {
        return $query->where('category', 'exam');
    }
}
