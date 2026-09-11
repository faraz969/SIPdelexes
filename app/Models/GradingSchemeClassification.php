<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingSchemeClassification extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_scheme_id',
        'name',
        'min_cgpa',
        'max_cgpa',
        'sequence',
    ];

    protected $casts = [
        'min_cgpa' => 'decimal:2',
        'max_cgpa' => 'decimal:2',
    ];

    public function scheme()
    {
        return $this->belongsTo(GradingScheme::class, 'grading_scheme_id');
    }
}
