<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_result_sheet_id',
        'stage',
        'action',
        'user_id',
        'comment',
    ];

    public function sheet()
    {
        return $this->belongsTo(CourseResultSheet::class, 'course_result_sheet_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
