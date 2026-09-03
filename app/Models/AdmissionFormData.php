<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionFormData extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'application_id',
        'offer_type',
        'conditional_subject',
        'offer_accepted_at',
        'total_fees',
        'minimum_fee_percentage',
        'balance_percentage',
        'paid_fees_by_date',
        'registration_begins',
        'orientation_new_students',
        'faculty_orientation',
        'lectures_begin',
        'generated_file_path',
    ];

    public const OFFER_TYPES = ['regular', 'conditional', 'mature', 'top-up'];

    public static function normalizeOfferType(?string $type): string
    {
        $type = strtolower(trim((string) $type));
        return in_array($type, self::OFFER_TYPES, true) ? $type : 'regular';
    }

    protected $casts = [
        'total_fees' => 'decimal:2',
        'minimum_fee_percentage' => 'decimal:2',
        'balance_percentage' => 'decimal:2',
        'paid_fees_by_date' => 'string',
        'registration_begins' => 'string',
        'orientation_new_students' => 'string',
        'faculty_orientation' => 'string',
        'lectures_begin' => 'string',
        'offer_accepted_at' => 'datetime',
    ];

    public function isOfferAccepted(): bool
    {
        return $this->offer_accepted_at !== null;
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}