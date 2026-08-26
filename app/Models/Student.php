<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    public const LEVELS = ['100', '200', '300', '400'];

    protected $fillable = [
        'user_id',
        'application_id',
        'student_id',
        'masters_student_id_reference',
        'erp_student_name',
        'program_id',
        'department_id',
        'preferred_session_id',
        'academic_year',
        'level',
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

    public static function normalizeLevel(?string $level): string
    {
        $level = trim((string) $level);
        return in_array($level, self::LEVELS, true) ? $level : '100';
    }

    public function getLevelLabelAttribute(): string
    {
        return 'Level ' . self::normalizeLevel($this->level);
    }

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

    public function preferredSession()
    {
        return $this->belongsTo(Session::class, 'preferred_session_id');
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

    public function admissionFormData()
    {
        return $this->hasOne(AdmissionFormData::class);
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
        return (float) $this->invoices()->sum('paid_amount');
    }

    public function getPaymentPercentage()
    {
        $totalInvoiced = (float) $this->invoices()->sum('total_amount');
        if ($totalInvoiced <= 0) {
            return 0;
        }

        $totalPaid = (float) $this->invoices()->sum('paid_amount');

        return ($totalPaid / $totalInvoiced) * 100;
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

