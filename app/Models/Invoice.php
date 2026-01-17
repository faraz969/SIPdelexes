<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'invoice_number',
        'erp_invoice_id',
        'invoice_type',
        'academic_year',
        'semester',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'due_date',
        'issued_date',
        'line_items',
        'synced_from_erp',
        'synced_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'due_date' => 'date',
        'issued_date' => 'date',
        'line_items' => 'array',
        'synced_from_erp' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function updateBalance()
    {
        $paid = $this->payments()->where('status', 'completed')->sum('amount');
        $this->paid_amount = $paid;
        $this->balance = $this->total_amount - $paid;
        
        if ($this->balance <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }
        
        $this->save();
    }
}

