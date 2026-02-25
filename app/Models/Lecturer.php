<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'session_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get students assigned to this lecturer (same course in registration + same preferred session)
     */
    public function getAssignedStudents()
    {
        return Student::where('preferred_session_id', $this->session_id)
            ->where('academic_status', 'active')
            ->whereHas('courseRegistrations', function ($query) {
                $query->whereJsonContains('courses', ['id' => $this->course_id]);
            })
            ->with(['user', 'program'])
            ->get();
    }
}
