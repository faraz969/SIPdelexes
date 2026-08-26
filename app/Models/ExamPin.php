<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExamPin extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'pin',
        'semester',
        'academic_year',
        'level',
        'expires_at',
        'is_used',
        'used_at',
        'used_for_exam',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public static function generateUniquePin()
    {
        do {
            $pin = strtoupper(Str::random(12));
        } while (self::where('pin', $pin)->exists());

        return $pin;
    }

    public function isExpired()
    {
        return $this->expires_at < now();
    }

    public function isValid()
    {
        return !$this->is_used && !$this->isExpired();
    }
}

