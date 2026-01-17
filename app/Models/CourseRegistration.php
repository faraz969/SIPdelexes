<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'semester',
        'academic_year',
        'courses',
        'status',
        'late_fee',
        'is_late_registration',
        'registered_at',
    ];

    protected $casts = [
        'courses' => 'array',
        'late_fee' => 'decimal:2',
        'is_late_registration' => 'boolean',
        'registered_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

