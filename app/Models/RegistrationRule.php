<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_name',
        'minimum_payment_percentage',
        'late_registration_fee',
        'late_registration_days',
        'is_active',
    ];

    protected $casts = [
        'minimum_payment_percentage' => 'decimal:2',
        'late_registration_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function getActiveRule()
    {
        return self::where('is_active', true)->first();
    }
}

