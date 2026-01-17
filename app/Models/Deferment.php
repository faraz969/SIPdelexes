<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deferment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'reason',
        'defer_from',
        'defer_to',
        'status',
        'registrar_comments',
        'approved_by',
        'approved_at',
        'reactivated_at',
    ];

    protected $casts = [
        'defer_from' => 'date',
        'defer_to' => 'date',
        'approved_at' => 'datetime',
        'reactivated_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isReactivated()
    {
        return $this->status === 'reactivated';
    }
}

