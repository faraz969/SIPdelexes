<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttemptAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_attempt_id',
        'quiz_question_id',
        'selected_option_ids',
        'answer_text',
        'marks_awarded',
        'is_correct',
        'is_auto_graded',
        'grader_comment',
    ];

    protected $casts = [
        'selected_option_ids' => 'array',
        'marks_awarded' => 'decimal:2',
        'is_correct' => 'boolean',
        'is_auto_graded' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
