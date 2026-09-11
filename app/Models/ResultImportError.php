<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultImportError extends Model
{
    use HasFactory;

    protected $fillable = [
        'result_import_batch_id',
        'row_number',
        'student_id_value',
        'error_code',
        'message',
    ];

    public function batch()
    {
        return $this->belongsTo(ResultImportBatch::class, 'result_import_batch_id');
    }
}
