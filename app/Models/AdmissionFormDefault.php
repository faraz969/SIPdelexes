<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionFormDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'minimum_fee_percentage',
        'balance_percentage',
        'paid_fees_by_date',
        'registration_begins',
        'orientation_new_students',
        'faculty_orientation',
        'lectures_begin',
        'registrar_name',
        'registrar_signature',
    ];

    protected $casts = [
        'academic_year' => 'string',
        'minimum_fee_percentage' => 'decimal:2',
        'balance_percentage' => 'decimal:2',
        'paid_fees_by_date' => 'date',
        'registration_begins' => 'date',
        'orientation_new_students' => 'date',
        'faculty_orientation' => 'date',
        'lectures_begin' => 'date',
    ];

    /**
     * Data URI for the registrar signature image (works in HTML, print, and PDF).
     */
    public function registrarSignatureSrc(): ?string
    {
        if (empty($this->registrar_signature)) {
            return null;
        }

        $fullPath = storage_path('app/public/' . $this->registrar_signature);
        if (!file_exists($fullPath)) {
            return null;
        }

        $mime = mime_content_type($fullPath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    }
}

