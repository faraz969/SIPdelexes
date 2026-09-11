<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradingScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'affiliation',
        'code',
        'is_default',
        'is_active',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(GradingSchemeLine::class)->orderBy('sequence')->orderByDesc('min_mark');
    }

    public function classifications()
    {
        return $this->hasMany(GradingSchemeClassification::class)->orderBy('sequence')->orderByDesc('min_cgpa');
    }

    public static function defaultScheme(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->with(['lines', 'classifications'])->first()
            ?: static::where('is_active', true)->with(['lines', 'classifications'])->first();
    }
}
