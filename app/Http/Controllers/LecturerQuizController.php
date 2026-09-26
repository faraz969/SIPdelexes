<?php

namespace App\Http\Controllers;

use App\Models\Lecturer;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\SiteSetting;
use App\Services\CourseEnrollmentService;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LecturerQuizController extends Controller
{
    protected QuizService $quizService;
    protected CourseEnrollmentService $enrollmentService;

    public function __construct(QuizService $quizService, CourseEnrollmentService $enrollmentService)
    {
        $this->quizService = $quizService;
        $this->enrollmentService = $enrollmentService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $assignments = Lecturer::with(['course', 'session'])
            ->where('user_id', $user->id)
            ->orderBy('course_id')
            ->get();

        $options = $this->enrollmentService->filterOptions();
        $academicYear = trim((string) $request->get('academic_year', SiteSetting::currentAcademicYear()));
        $semester = trim((string) $request->get('semester', ''));

        $cards = $assignments->map(function (Lecturer $assignment) use ($academicYear, $semester) {
            $year = $academicYear !== ''
                ? $academicYear
                : ($assignment->academic_year ?: SiteSetting::currentAcademicYear());
            $sem = $semester !== ''
                ? $semester
                : ($assignment->semester ?: 'First Semester');

            $quizzes = Quiz::where('course_id', $assignment->course_id)
                ->where('lecturer_id', $assignment->id)
                ->where('academic_year', $year)
                ->where('semester', $sem)
                ->withCount(['questions', 'attempts'])
                ->orderByDesc('id')
                ->get();

            return [
                'assignment' => $assignment,
                'academic_year' => $year,
                'semester' => $sem,
                'quizzes' => $quizzes,
                'count' => $quizzes->count(),
            ];
        });

        return view('lecturer.quizzes.index', [
            'cards' => $cards,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'semesters' => $options['semesters'],
            'academicYears' => $options['academicYears'],
        ]);
    }

    public function course(Request $request, Lecturer $lecturer)
    {
        $this->quizService->assertOwnsAssignment($lecturer);
        $lecturer->load(['course', 'session']);

        $options = $this->enrollmentService->filterOptions();
        $academicYear = trim((string) $request->get('academic_year', SiteSetting::currentAcademicYear()));
        $semester = trim((string) $request->get('semester', $lecturer->semester ?: 'First Semester'));

        $quizzes = Quiz::where('course_id', $lecturer->course_id)
            ->where('lecturer_id', $lecturer->id)
            ->where('academic_year', $academicYear)
            ->where('semester', $semester)
            ->withCount(['questions', 'attempts'])
            ->orderByDesc('id')
            ->get();

        return view('lecturer.quizzes.course', compact(
            'lecturer',
            'quizzes',
            'academicYear',
            'semester'
        ) + [
            'semesters' => $options['semesters'],
            'academicYears' => $options['academicYears'],
            'questionTypes' => Quiz::QUESTION_TYPES,
        ]);
    }

    public function store(Request $request, Lecturer $lecturer)
    {
        $this->quizService->assertOwnsAssignment($lecturer);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string|max:5000',
            'academic_year' => 'required|string|max:50',
            'semester' => 'required|string|max:100',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'max_attempts' => 'nullable|integer|min:1|max:20',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after_or_equal:opens_at',
            'is_published' => 'boolean',
            'show_score_to_student' => 'boolean',
        ]);

        $quiz = $this->quizService->createQuiz($lecturer, [
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'max_attempts' => $validated['max_attempts'] ?? 1,
            'opens_at' => $validated['opens_at'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'show_score_to_student' => $request->boolean('show_score_to_student', true),
        ]);

        return redirect()
            ->route('lecturer.quizzes.show', [$lecturer, $quiz])
            ->with('success', 'Quiz created. Add questions next.');
    }

    public function show(Lecturer $lecturer, Quiz $quiz)
    {
        $this->quizService->assertOwnsAssignment($lecturer);
        $this->assertQuizBelongs($lecturer, $quiz);

        $quiz->load(['questions.options', 'course']);
        $attempts = $quiz->attempts()
            ->with(['student.user'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        return view('lecturer.quizzes.show', [
            'lecturer' => $lecturer,
            'quiz' => $quiz,
            'attempts' => $attempts,
            'questionTypes' => Quiz::QUESTION_TYPES,
        ]);
    }

    public function update(Request $request, Lecturer $lecturer, Quiz $quiz)
    {
        $this->quizService->assertOwnsAssignment($lecturer);
        $this->assertQuizBelongs($lecturer, $quiz);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'instructions' => 'nullable|string|max:5000',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'max_attempts' => 'nullable|integer|min:1|max:20',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after_or_equal:opens_at',
            'is_published' => 'boolean',
            'show_score_to_student' => 'boolean',
        ]);

        $this->quizService->updateQuiz($quiz, [
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'max_attempts' => $validated['max_attempts'] ?? 1,
            'opens_at' => $validated['opens_at'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'show_score_to_student' => $request->boolean('show_score_to_student', true),
        ]);

        return back()->with('success', 'Quiz settings updated.');
    }

    public function destroy(Lecturer $lecturer, Quiz $quiz)
    {
        $this->quizService->assertOwnsAssignment($lecturer);
        $this->assertQuizBelongs($lecturer, $quiz);

        $year = $quiz->academic_year;
        $sem = $quiz->semester;
        $quiz->delete();

        return redirect()
            ->route('lecturer.quizzes.course', [
                'lecturer' => $lecturer,
                'academic_year' => $year,
                'semester' => $sem,
            ])
            ->with('success', 'Quiz deleted.');
    }

    public function storeQuestion(Request $request, Lecturer $lecturer, Quiz $quiz)
    {
        $this->quizService->assertOwnsAssignment($lecturer);
        $this->assertQuizBelongs($lecturer, $quiz);

        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', array_keys(Quiz::QUESTION_TYPES)),
            'question_text' => 'required|string|max:10000',
            'marks' => 'required|numeric|min:0.5|max:100',
            'sequence' => 'nullable|integer|min:0',
            'correct_answer' => 'nullable|string|max:5000',
            'code_language' => 'nullable|string|max:50',
            'options' => 'nullable|array',
            'options.*.text' => 'nullable|string|max:2000',
            'options.*.is_correct' => 'nullable|boolean',
        ]);

        try {
            $this->quizService->addQuestion($quiz, $validated);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return back()->with('success', 'Question added.');
    }

    public function destroyQuestion(Lecturer $lecturer, Quiz $quiz, QuizQuestion $question)
    {
        $this->quizService->assertOwnsAssignment($lecturer);
        $this->assertQuizBelongs($lecturer, $quiz);
        if ((int) $question->quiz_id !== (int) $quiz->id) {
            abort(404);
        }

        $this->quizService->deleteQuestion($question);

        return back()->with('success', 'Question deleted.');
    }

    public function attempt(Lecturer $lecturer, Quiz $quiz, QuizAttempt $attempt)
    {
        $this->quizService->assertOwnsAssignment($lecturer);
        $this->assertQuizBelongs($lecturer, $quiz);
        if ((int) $attempt->quiz_id !== (int) $quiz->id) {
            abort(404);
        }

        $attempt->load(['student.user', 'answers.question.options', 'quiz.questions.options']);

        return view('lecturer.quizzes.attempt', compact('lecturer', 'quiz', 'attempt'));
    }

    public function grade(Request $request, Lecturer $lecturer, Quiz $quiz, QuizAttempt $attempt)
    {
        $this->quizService->assertOwnsAssignment($lecturer);
        $this->assertQuizBelongs($lecturer, $quiz);
        if ((int) $attempt->quiz_id !== (int) $quiz->id) {
            abort(404);
        }

        $request->validate([
            'marks' => 'required|array',
            'marks.*' => 'nullable|numeric|min:0',
            'comments' => 'nullable|array',
            'lecturer_feedback' => 'nullable|string|max:5000',
        ]);

        $payload = $request->input('marks', []);
        $payload['comments'] = $request->input('comments', []);

        try {
            $this->quizService->gradeAttempt($attempt, $payload, $request->input('lecturer_feedback'));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('lecturer.quizzes.attempt', [$lecturer, $quiz, $attempt])
            ->with('success', 'Attempt graded successfully.');
    }

    protected function assertQuizBelongs(Lecturer $lecturer, Quiz $quiz): void
    {
        if ((int) $quiz->lecturer_id !== (int) $lecturer->id
            || (int) $quiz->course_id !== (int) $lecturer->course_id) {
            abort(403, 'This quiz does not belong to your course assignment.');
        }
    }
}
