<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'duration',
        'mode',
        'price',
        'is_active',
        'sort_order',
        'cut_off_grade'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class)->orderBy('sort_order')->orderBy('course_code');
    }

    public function activeCourses()
    {
        return $this->hasMany(Course::class)->where('is_active', true)->orderBy('sort_order')->orderBy('course_code');
    }
}
