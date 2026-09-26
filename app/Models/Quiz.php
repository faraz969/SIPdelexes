<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    public const QUESTION_TYPES = [
        'single_choice' => 'Multiple Choice (Single Answer)',
        'multiple_choice' => 'Multiple Choice (Multiple Answers)',
        'true_false' => 'True / False',
        'fill_blank' => 'Fill in the Blank',
        'short_answer' => 'Short Answer',
        'essay' => 'Essay / Long Answer',
        'code' => 'Code / Programming',
    ];

    protected $fillable = [
        'course_id',
        'lecturer_id',
        'created_by',
        'academic_year',
        'semester',
        'title',
        'instructions',
        'duration_minutes',
        'max_attempts',
        'total_marks',
        'opens_at',
        'closes_at',
        'is_published',
        'show_score_to_student',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'max_attempts' => 'integer',
        'total_marks' => 'decimal:2',
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'is_published' => 'boolean',
        'show_score_to_student' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sequence')->orderBy('id');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isOpen(): bool
    {
        return $this->availabilityStatus() === 'open';
    }

    /**
     * open | unpublished | scheduled | closed
     */
    public function availabilityStatus(): string
    {
        if (!$this->is_published) {
            return 'unpublished';
        }

        $now = now();

        if ($this->opens_at && $now->lt($this->opens_at)) {
            return 'scheduled';
        }

        if ($this->closes_at && $now->gt($this->closes_at)) {
            return 'closed';
        }

        return 'open';
    }

    public function availabilityMessage(): string
    {
        switch ($this->availabilityStatus()) {
            case 'unpublished':
                return 'This quiz is not published yet.';
            case 'scheduled':
                return 'This quiz opens on '
                    . optional($this->opens_at)->timezone(config('app.timezone'))->format('d M Y H:i')
                    . ' (' . config('app.timezone') . ').';
            case 'closed':
                return 'This quiz closed on '
                    . optional($this->closes_at)->timezone(config('app.timezone'))->format('d M Y H:i')
                    . ' (' . config('app.timezone') . ').';
            default:
                return 'This quiz is open.';
        }
    }

    public function recalculateTotalMarks(): void
    {
        $this->total_marks = (float) $this->questions()->sum('marks');
        $this->save();
    }
}
