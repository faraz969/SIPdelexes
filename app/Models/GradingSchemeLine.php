<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingSchemeLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_scheme_id',
        'grade',
        'min_mark',
        'max_mark',
        'grade_point',
        'sequence',
    ];

    protected $casts = [
        'min_mark' => 'decimal:2',
        'max_mark' => 'decimal:2',
        'grade_point' => 'decimal:2',
    ];

    public function scheme()
    {
        return $this->belongsTo(GradingScheme::class, 'grading_scheme_id');
    }
}
