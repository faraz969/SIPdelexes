<?php

namespace App\Http\Controllers;

use App\Models\CourseRegistration;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Student;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SIPQuizController extends Controller
{
    protected QuizService $quizService;

    public function __construct(QuizService $quizService)
    {
        $this->middleware('auth');
        $this->quizService = $quizService;
    }

    protected function getStudent(): Student
    {
        $student = Student::where('user_id', Auth::id())->first();
        if (!$student) {
            abort(403, 'SIP account not found.');
        }

        return $student;
    }

    public function index()
    {
        $student = $this->getStudent();

        $registrations = CourseRegistration::where('student_id', $student->id)
            ->whereIn('status', ['registered', 'late', 'pending'])
            ->get();

        $courseIdsByTerm = [];
        foreach ($registrations as $registration) {
            foreach (($registration->courses ?? []) as $item) {
                $courseId = (int) ($item['id'] ?? 0);
                if ($courseId <= 0) {
                    continue;
                }
                $key = $registration->academic_year . '|' . $registration->semester;
                $courseIdsByTerm[$key]['year'] = $registration->academic_year;
                $courseIdsByTerm[$key]['semester'] = $registration->semester;
                $courseIdsByTerm[$key]['course_ids'][$courseId] = true;
            }
        }

        $quizzes = collect();
        foreach ($courseIdsByTerm as $term) {
            $termQuizzes = Quiz::with('course')
                ->withCount('questions')
                ->where('is_published', true)
                ->where('academic_year', $term['year'])
                ->where('semester', $term['semester'])
                ->whereIn('course_id', array_keys($term['course_ids']))
                ->orderByDesc('id')
                ->get();
            $quizzes = $quizzes->merge($termQuizzes);
        }

        $quizzes = $quizzes->unique('id')->values();

        $attemptCounts = QuizAttempt::where('student_id', $student->id)
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->groupBy('quiz_id');

        return view('sip.quizzes.index', compact('student', 'quizzes', 'attemptCounts'));
    }

    public function show(Quiz $quiz)
    {
        $student = $this->getStudent();
        $this->quizService->assertStudentCanAccessQuiz($student, $quiz);

        $quiz->load(['course', 'questions']);
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->orderByDesc('attempt_number')
            ->get();

        $inProgress = $attempts->firstWhere('status', 'in_progress');

        return view('sip.quizzes.show', compact('student', 'quiz', 'attempts', 'inProgress'));
    }

    public function start(Quiz $quiz)
    {
        $student = $this->getStudent();

        try {
            $attempt = $this->quizService->startAttempt($student, $quiz);
        } catch (ValidationException $e) {
            return redirect()->route('sip.quizzes.show', $quiz)
                ->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('sip.quizzes.attempt', [$quiz, $attempt]);
    }

    public function attempt(Quiz $quiz, QuizAttempt $attempt)
    {
        $student = $this->getStudent();
        $this->assertOwnAttempt($student, $quiz, $attempt);

        $quiz->load(['questions.options', 'course']);
        $attempt->load('answers');

        if ($attempt->isSubmitted()) {
            return redirect()->route('sip.quizzes.result', [$quiz, $attempt]);
        }

        $answersByQuestion = $attempt->answers->keyBy('quiz_question_id');

        return view('sip.quizzes.attempt', compact('student', 'quiz', 'attempt', 'answersByQuestion'));
    }

    public function save(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $student = $this->getStudent();
        $this->assertOwnAttempt($student, $quiz, $attempt);

        try {
            $this->quizService->saveAnswers($attempt, $request->input('answers', []));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', 'Answers saved.');
    }

    public function submit(Request $request, Quiz $quiz, QuizAttempt $attempt)
    {
        $student = $this->getStudent();
        $this->assertOwnAttempt($student, $quiz, $attempt);

        try {
            $this->quizService->submitAttempt($attempt, $request->input('answers', []));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('sip.quizzes.result', [$quiz, $attempt])
            ->with('success', 'Quiz submitted successfully.');
    }

    public function result(Quiz $quiz, QuizAttempt $attempt)
    {
        $student = $this->getStudent();
        $this->assertOwnAttempt($student, $quiz, $attempt);

        if ($attempt->isInProgress()) {
            return redirect()->route('sip.quizzes.attempt', [$quiz, $attempt]);
        }

        $attempt->load(['answers.question.options', 'quiz.questions']);

        return view('sip.quizzes.result', compact('student', 'quiz', 'attempt'));
    }

    protected function assertOwnAttempt(Student $student, Quiz $quiz, QuizAttempt $attempt): void
    {
        $this->quizService->assertStudentCanAccessQuiz($student, $quiz);
        if ((int) $attempt->quiz_id !== (int) $quiz->id
            || (int) $attempt->student_id !== (int) $student->id) {
            abort(403);
        }
    }
}
