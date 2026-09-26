<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'type',
        'question_text',
        'marks',
        'sequence',
        'correct_answer',
        'code_language',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'sequence' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options()
    {
        return $this->hasMany(QuizQuestionOption::class)->orderBy('sequence')->orderBy('id');
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->type, [
            'single_choice',
            'multiple_choice',
            'true_false',
            'fill_blank',
        ], true);
    }

    public function typeLabel(): string
    {
        return Quiz::QUESTION_TYPES[$this->type] ?? $this->type;
    }
}
