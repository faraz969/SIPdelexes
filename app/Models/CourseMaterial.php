<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'lecturer_id',
        'uploaded_by',
        'academic_year',
        'semester',
        'title',
        'description',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'file_size' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderByDesc('created_at');
    }

    public function fileSizeLabel(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function fileTypeLabel(): string
    {
        $ext = strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION));
        $map = [
            'pdf' => 'PDF',
            'doc' => 'Word',
            'docx' => 'Word',
            'ppt' => 'PowerPoint',
            'pptx' => 'PowerPoint',
            'xls' => 'Excel',
            'xlsx' => 'Excel',
            'jpg' => 'Image',
            'jpeg' => 'Image',
            'png' => 'Image',
            'gif' => 'Image',
            'webp' => 'Image',
            'mp4' => 'Video',
            'mov' => 'Video',
            'avi' => 'Video',
            'webm' => 'Video',
            'mkv' => 'Video',
            'zip' => 'Archive',
        ];

        return $map[$ext] ?? strtoupper($ext ?: 'File');
    }
}
