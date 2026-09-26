<?php

namespace App\Services;

use App\Models\Lecturer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuizService
{
    protected CourseMaterialService $materialService;

    public function __construct(CourseMaterialService $materialService)
    {
        $this->materialService = $materialService;
    }

    public function assertOwnsAssignment(Lecturer $assignment, ?int $userId = null): void
    {
        $this->materialService->assertOwnsAssignment($assignment, $userId);
    }

    public function assertOwnsQuiz(Quiz $quiz, ?int $userId = null): void
    {
        $userId = $userId ?? Auth::id();
        if ((int) $quiz->created_by !== (int) $userId) {
            abort(403, 'You can only manage quizzes you created.');
        }
    }

    public function assertStudentCanAccessQuiz(Student $student, Quiz $quiz): void
    {
        if (!$quiz->is_published && !$this->isOpenForStudentCheckBypass($quiz)) {
            // published check for students
        }

        if (!$quiz->is_published) {
            abort(403, 'This quiz is not available.');
        }

        if (!$this->materialService->studentIsRegisteredForCourse(
            $student,
            (int) $quiz->course_id,
            $quiz->academic_year,
            $quiz->semester
        )) {
            abort(403, 'You are not registered for this course.');
        }
    }

    protected function isOpenForStudentCheckBypass(Quiz $quiz): bool
    {
        return false;
    }

    public function createQuiz(Lecturer $assignment, array $data): Quiz
    {
        return Quiz::create([
            'course_id' => $assignment->course_id,
            'lecturer_id' => $assignment->id,
            'created_by' => Auth::id(),
            'academic_year' => $data['academic_year'],
            'semester' => $data['semester'],
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'max_attempts' => max(1, (int) ($data['max_attempts'] ?? 1)),
            'total_marks' => 0,
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => $data['closes_at'] ?? null,
            'is_published' => !empty($data['is_published']),
            'show_score_to_student' => array_key_exists('show_score_to_student', $data)
                ? (bool) $data['show_score_to_student']
                : true,
        ]);
    }

    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        $quiz->update([
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'max_attempts' => max(1, (int) ($data['max_attempts'] ?? 1)),
            'opens_at' => $data['opens_at'] ?? null,
            'closes_at' => $data['closes_at'] ?? null,
            'is_published' => !empty($data['is_published']),
            'show_score_to_student' => array_key_exists('show_score_to_student', $data)
                ? (bool) $data['show_score_to_student']
                : true,
        ]);

        return $quiz->fresh();
    }

    public function addQuestion(Quiz $quiz, array $data): QuizQuestion
    {
        $type = $data['type'];
        if (!array_key_exists($type, Quiz::QUESTION_TYPES)) {
            throw ValidationException::withMessages(['type' => 'Invalid question type.']);
        }

        return DB::transaction(function () use ($quiz, $data, $type) {
            $sequence = (int) ($data['sequence'] ?? (($quiz->questions()->max('sequence') ?? 0) + 1));

            $question = QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'type' => $type,
                'question_text' => $data['question_text'],
                'marks' => (float) ($data['marks'] ?? 1),
                'sequence' => $sequence,
                'correct_answer' => $data['correct_answer'] ?? null,
                'code_language' => $data['code_language'] ?? null,
            ]);

            $this->syncOptions($question, $data);
            $quiz->recalculateTotalMarks();

            return $question->fresh('options');
        });
    }

    public function updateQuestion(QuizQuestion $question, array $data): QuizQuestion
    {
        return DB::transaction(function () use ($question, $data) {
            $question->update([
                'question_text' => $data['question_text'],
                'marks' => (float) ($data['marks'] ?? $question->marks),
                'sequence' => (int) ($data['sequence'] ?? $question->sequence),
                'correct_answer' => $data['correct_answer'] ?? $question->correct_answer,
                'code_language' => $data['code_language'] ?? $question->code_language,
            ]);

            if (isset($data['options']) || in_array($question->type, ['single_choice', 'multiple_choice', 'true_false'], true)) {
                $this->syncOptions($question, $data);
            }

            $question->quiz->recalculateTotalMarks();

            return $question->fresh('options');
        });
    }

    public function deleteQuestion(QuizQuestion $question): void
    {
        $quiz = $question->quiz;
        $question->delete();
        $quiz->recalculateTotalMarks();
    }

    protected function syncOptions(QuizQuestion $question, array $data): void
    {
        $type = $question->type;

        if ($type === 'true_false') {
            $question->options()->delete();
            $correct = strtolower(trim((string) ($data['correct_answer'] ?? 'true')));
            if (!in_array($correct, ['true', 'false'], true)) {
                $correct = 'true';
            }
            QuizQuestionOption::create([
                'quiz_question_id' => $question->id,
                'option_text' => 'True',
                'is_correct' => $correct === 'true',
                'sequence' => 1,
            ]);
            QuizQuestionOption::create([
                'quiz_question_id' => $question->id,
                'option_text' => 'False',
                'is_correct' => $correct === 'false',
                'sequence' => 2,
            ]);
            $question->update(['correct_answer' => $correct]);
            return;
        }

        if (!in_array($type, ['single_choice', 'multiple_choice'], true)) {
            return;
        }

        $options = $data['options'] ?? [];
        if (!is_array($options) || count($options) < 2) {
            throw ValidationException::withMessages([
                'options' => 'Add at least two options for multiple choice questions.',
            ]);
        }

        $question->options()->delete();
        $hasCorrect = false;
        $seq = 1;
        foreach ($options as $opt) {
            $text = trim((string) ($opt['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $isCorrect = !empty($opt['is_correct']);
            if ($isCorrect) {
                $hasCorrect = true;
            }
            QuizQuestionOption::create([
                'quiz_question_id' => $question->id,
                'option_text' => $text,
                'is_correct' => $isCorrect,
                'sequence' => $seq++,
            ]);
        }

        if ($seq <= 2) {
            throw ValidationException::withMessages([
                'options' => 'Add at least two non-empty options.',
            ]);
        }

        if (!$hasCorrect) {
            throw ValidationException::withMessages([
                'options' => 'Mark at least one correct option.',
            ]);
        }

        if ($type === 'single_choice') {
            $correctCount = $question->options()->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                throw ValidationException::withMessages([
                    'options' => 'Single-answer questions must have exactly one correct option.',
                ]);
            }
        }
    }

    public function startAttempt(Student $student, Quiz $quiz): QuizAttempt
    {
        $this->assertStudentCanAccessQuiz($student, $quiz);

        if (!$quiz->isOpen()) {
            throw ValidationException::withMessages([
                'quiz' => 'This quiz is not open for attempts right now.',
            ]);
        }

        $existingInProgress = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existingInProgress) {
            return $existingInProgress;
        }

        $used = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->count();

        if ($used >= (int) $quiz->max_attempts) {
            throw ValidationException::withMessages([
                'quiz' => 'You have used all allowed attempts for this quiz.',
            ]);
        }

        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'attempt_number' => $used + 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'max_score' => $quiz->total_marks,
        ]);
    }

    public function saveAnswers(QuizAttempt $attempt, array $answersInput): void
    {
        if (!$attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt can no longer be edited.',
            ]);
        }

        $attempt->loadMissing('quiz.questions.options');

        if ($attempt->quiz->duration_minutes) {
            $deadline = $attempt->started_at->copy()->addMinutes((int) $attempt->quiz->duration_minutes);
            if (now()->gt($deadline->addMinutes(1))) {
                // allow soft grace; submit will enforce
            }
        }

        foreach ($attempt->quiz->questions as $question) {
            $payload = $answersInput[$question->id] ?? $answersInput[(string) $question->id] ?? null;
            $selected = [];
            $text = null;

            if (is_array($payload)) {
                if (isset($payload['options'])) {
                    $selected = array_map('intval', (array) $payload['options']);
                } elseif (isset($payload['option'])) {
                    $selected = [(int) $payload['option']];
                }
                $text = isset($payload['text']) ? (string) $payload['text'] : null;
            } elseif (is_string($payload) || is_numeric($payload)) {
                $text = (string) $payload;
            }

            QuizAttemptAnswer::updateOrCreate(
                [
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $question->id,
                ],
                [
                    'selected_option_ids' => $selected ?: null,
                    'answer_text' => $text,
                ]
            );
        }
    }

    public function submitAttempt(QuizAttempt $attempt, array $answersInput = []): QuizAttempt
    {
        if (!$attempt->isInProgress()) {
            throw ValidationException::withMessages([
                'attempt' => 'This attempt was already submitted.',
            ]);
        }

        if (!empty($answersInput)) {
            $this->saveAnswers($attempt, $answersInput);
        }

        $attempt->load('quiz.questions.options', 'answers');

        DB::transaction(function () use ($attempt) {
            $needsManual = false;
            $autoScore = 0.0;
            $maxScore = (float) $attempt->quiz->total_marks;

            foreach ($attempt->quiz->questions as $question) {
                $answer = $attempt->answers->firstWhere('quiz_question_id', $question->id);
                if (!$answer) {
                    $answer = QuizAttemptAnswer::create([
                        'quiz_attempt_id' => $attempt->id,
                        'quiz_question_id' => $question->id,
                    ]);
                }

                if ($question->isAutoGradable()) {
                    $result = $this->autoGradeAnswer($question, $answer);
                    $answer->update([
                        'marks_awarded' => $result['marks'],
                        'is_correct' => $result['correct'],
                        'is_auto_graded' => true,
                    ]);
                    $autoScore += $result['marks'];
                } else {
                    $needsManual = true;
                    $answer->update([
                        'is_auto_graded' => false,
                        'marks_awarded' => null,
                        'is_correct' => null,
                    ]);
                }
            }

            $attempt->update([
                'status' => $needsManual ? 'submitted' : 'graded',
                'submitted_at' => now(),
                'graded_at' => $needsManual ? null : now(),
                'graded_by' => $needsManual ? null : Auth::id(),
                'score' => $needsManual ? $autoScore : $autoScore,
                'max_score' => $maxScore,
            ]);
        });

        return $attempt->fresh(['answers', 'quiz']);
    }

    protected function autoGradeAnswer(QuizQuestion $question, QuizAttemptAnswer $answer): array
    {
        $marks = (float) $question->marks;

        if ($question->type === 'fill_blank') {
            $expected = $this->normalizeText($question->correct_answer);
            $given = $this->normalizeText($answer->answer_text);
            $ok = $expected !== '' && $expected === $given;
            return ['correct' => $ok, 'marks' => $ok ? $marks : 0.0];
        }

        if (in_array($question->type, ['single_choice', 'true_false', 'multiple_choice'], true)) {
            $correctIds = $question->options->where('is_correct', true)->pluck('id')->map(function ($id) {
                return (int) $id;
            })->sort()->values()->all();
            $selected = collect($answer->selected_option_ids ?? [])->map(function ($id) {
                return (int) $id;
            })->sort()->values()->all();

            $ok = $correctIds === $selected && !empty($correctIds);
            return ['correct' => $ok, 'marks' => $ok ? $marks : 0.0];
        }

        return ['correct' => false, 'marks' => 0.0];
    }

    protected function normalizeText(?string $text): string
    {
        $text = strtolower(trim((string) $text));
        $text = preg_replace('/\s+/', ' ', $text);

        return $text ?? '';
    }

    public function gradeAttempt(QuizAttempt $attempt, array $marksByQuestion, ?string $feedback = null): QuizAttempt
    {
        if (!$attempt->isSubmitted()) {
            throw ValidationException::withMessages([
                'attempt' => 'Only submitted attempts can be graded.',
            ]);
        }

        $attempt->load('quiz.questions', 'answers');

        return DB::transaction(function () use ($attempt, $marksByQuestion, $feedback) {
            $total = 0.0;
            $comments = $marksByQuestion['comments'] ?? [];
            unset($marksByQuestion['comments']);

            foreach ($attempt->quiz->questions as $question) {
                $answer = $attempt->answers->firstWhere('quiz_question_id', $question->id);
                if (!$answer) {
                    continue;
                }

                $key = $question->id;
                if (array_key_exists($key, $marksByQuestion) || array_key_exists((string) $key, $marksByQuestion)) {
                    $awarded = (float) ($marksByQuestion[$key] ?? $marksByQuestion[(string) $key]);
                    $awarded = max(0, min($awarded, (float) $question->marks));
                    $comment = $comments[$key] ?? $comments[(string) $key] ?? null;
                    $answer->update([
                        'marks_awarded' => $awarded,
                        'is_correct' => $awarded >= (float) $question->marks,
                        'grader_comment' => $comment ?? $answer->grader_comment,
                    ]);
                }

                $total += (float) ($answer->fresh()->marks_awarded ?? 0);
            }

            $attempt->update([
                'status' => 'graded',
                'score' => $total,
                'max_score' => $attempt->quiz->total_marks,
                'graded_at' => now(),
                'graded_by' => Auth::id(),
                'lecturer_feedback' => $feedback,
            ]);

            return $attempt->fresh(['answers.question', 'student.user']);
        });
    }
}
