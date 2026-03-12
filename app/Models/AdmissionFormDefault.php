<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionFormDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'minimum_fee_percentage',
        'balance_percentage',
        'paid_fees_by_date',
        'registration_begins',
        'orientation_new_students',
        'faculty_orientation',
        'lectures_begin',
    ];

    protected $casts = [
        'academic_year' => 'string',
        'minimum_fee_percentage' => 'decimal:2',
        'balance_percentage' => 'decimal:2',
        'paid_fees_by_date' => 'date',
        'registration_begins' => 'date',
        'orientation_new_students' => 'date',
        'faculty_orientation' => 'date',
        'lectures_begin' => 'date',
    ];
}

