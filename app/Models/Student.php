<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'application_id',
        'student_id',
        'program_id',
        'department_id',
        'academic_year',
        'academic_status',
        'admission_date',
        'deferred_at',
        'reactivated_at',
        'biodata',
        'sip_account_created',
        'sip_account_created_at',
    ];

    protected $casts = [
        'biodata' => 'array',
        'admission_date' => 'date',
        'deferred_at' => 'date',
        'reactivated_at' => 'date',
        'sip_account_created' => 'boolean',
        'sip_account_created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function academicRecords()
    {
        return $this->hasMany(StudentAcademicRecord::class);
    }

    public function courseRegistrations()
    {
        return $this->hasMany(CourseRegistration::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function examPins()
    {
        return $this->hasMany(ExamPin::class);
    }

    public function deferments()
    {
        return $this->hasMany(Deferment::class);
    }

    public function downloads()
    {
        return $this->hasMany(Download::class);
    }

    public function isActive()
    {
        return $this->academic_status === 'active';
    }

    public function isDeferred()
    {
        return $this->academic_status === 'deferred';
    }

    public function getTotalBalance()
    {
        return $this->invoices()->sum('balance');
    }

    public function getTotalPaid()
    {
        return $this->payments()->where('status', 'completed')->sum('amount');
    }

    public function getPaymentPercentage()
    {
        $totalInvoiced = $this->invoices()->sum('total_amount');
        if ($totalInvoiced == 0) {
            return 0;
        }
        return ($this->getTotalPaid() / $totalInvoiced) * 100;
    }

    public function canRegisterForCourses()
    {
        if ($this->isDeferred()) {
            return false;
        }

        $rule = RegistrationRule::where('is_active', true)->first();
        if (!$rule) {
            return true; // No rule means allowed
        }

        return $this->getPaymentPercentage() >= $rule->minimum_payment_percentage;
    }

    public function canGenerateExamPin()
    {
        if ($this->isDeferred()) {
            return false;
        }

        // Must have 100% fees paid
        return $this->getTotalBalance() == 0;
    }
}

