<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_code',
        'course_title',
        'program_id',
        'academic_year',
        'semester',
        'credit_units',
        'total_credit_units',
        'assessment_split',
        'is_elective',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'credit_units' => 'decimal:2',
        'total_credit_units' => 'decimal:2',
        'is_elective' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /** Credit units to use for registration (total_credit_units if set, else credit_units). */
    public function getEffectiveCreditUnitsAttribute(): float
    {
        return (float) ($this->total_credit_units ?? $this->credit_units);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->is_elective ? 'Elective' : 'Core';
    }
}
