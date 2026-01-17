<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAcademicRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'semester',
        'academic_year',
        'registered_courses',
        'results',
        'gpa',
        'resits_history',
        'is_approved',
        'approved_at',
    ];

    protected $casts = [
        'registered_courses' => 'array',
        'results' => 'array',
        'resits_history' => 'array',
        'gpa' => 'decimal:2',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

