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
        'hod_status',
        'hod_comments',
        'hod_reviewed_by',
        'hod_reviewed_at',
        'registrar_status',
        'registrar_comments',
        'approved_by',
        'approved_at',
        'reactivated_at',
    ];

    protected $casts = [
        'defer_from' => 'date',
        'defer_to' => 'date',
        'hod_reviewed_at' => 'datetime',
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

    public function hodReviewer()
    {
        return $this->belongsTo(User::class, 'hod_reviewed_by');
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

    public function isPendingHodReview()
    {
        return $this->isPending() && $this->hod_status === 'pending';
    }

    public function isPendingRegistrarReview()
    {
        return $this->isPending() && $this->hod_status === 'approved' && $this->registrar_status === 'pending';
    }

    public function isRejectedByHod()
    {
        return $this->hod_status === 'rejected';
    }

    public function displayStatusLabel(): string
    {
        if ($this->isApproved()) {
            return 'Approved';
        }

        if ($this->isRejected()) {
            return $this->isRejectedByHod() ? 'Rejected by HOD' : 'Rejected by Registrar';
        }

        if ($this->isReactivated()) {
            return 'Reactivated';
        }

        if ($this->isPendingRegistrarReview()) {
            return 'Awaiting Registrar Review';
        }

        return 'Awaiting HOD Review';
    }

    public function displayStatusClass(): string
    {
        if ($this->isApproved()) {
            return 'success';
        }

        if ($this->isRejected()) {
            return 'danger';
        }

        if ($this->isReactivated()) {
            return 'info';
        }

        if ($this->isPendingRegistrarReview()) {
            return 'primary';
        }

        return 'warning';
    }
}
