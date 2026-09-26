@extends('layouts.app')

@section('title', $quiz->title)

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">{{ $quiz->title }}</h3>
            <div class="text-muted">
                {{ $quiz->course->course_code ?? '' }} · {{ $quiz->academic_year }} / {{ $quiz->semester }}
                · Total marks: {{ number_format($quiz->total_marks, 1) }}
            </div>
        </div>
        <a href="{{ route('lecturer.quizzes.course', [
            'lecturer' => $lecturer,
            'academic_year' => $quiz->academic_year,
            'semester' => $quiz->semester,
        ]) }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><strong>Quiz Settings</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('lecturer.quizzes.update', [$lecturer, $quiz]) }}" data-convert-quiz-datetimes>
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $quiz->title) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Duration (min)</label>
                        <input type="number" name="duration_minutes" class="form-control" min="1"
                               value="{{ old('duration_minutes', $quiz->duration_minutes) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max attempts</label>
                        <input type="number" name="max_attempts" class="form-control" min="1"
                               value="{{ old('max_attempts', $quiz->max_attempts) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Instructions</label>
                        <textarea name="instructions" class="form-control" rows="2">{{ old('instructions', $quiz->instructions) }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Opens at <span class="text-muted small">(your local time)</span></label>
                        <input type="datetime-local" name="opens_at" class="form-control" data-app-datetime
                               value="{{ old('opens_at', optional($quiz->opens_at)->timezone(config('app.timezone'))->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Closes at <span class="text-muted small">(your local time)</span></label>
                        <input type="datetime-local" name="closes_at" class="form-control" data-app-datetime
                               value="{{ old('closes_at', optional($quiz->closes_at)->timezone(config('app.timezone'))->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_published" value="1" id="pub"
                                   {{ old('is_published', $quiz->is_published) ? 'checked' : '' }}>
                            <label class="form-check-label" for="pub">Published</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="show_score_to_student" value="1" id="score"
                                   {{ old('show_score_to_student', $quiz->show_score_to_student) ? 'checked' : '' }}>
                            <label class="form-check-label" for="score">Show score</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <p class="small text-muted mb-2">
                            Open/close times are saved in college time ({{ config('app.timezone') }}).
                            Your browser converts automatically from local time.
                        </p>
                        <button type="submit" class="btn btn-primary btn-sm">Save Settings</button>
                    </div>
                </div>
            </form>
            <form method="POST" action="{{ route('lecturer.quizzes.destroy', [$lecturer, $quiz]) }}" class="mt-2"
                  onsubmit="return confirm('Delete this quiz and all attempts?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete Quiz</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Add Question</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('lecturer.quizzes.questions.store', [$lecturer, $quiz]) }}" id="addQuestionForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" id="q_type" class="form-select" required>
                            @foreach($questionTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Marks</label>
                        <input type="number" step="0.5" min="0.5" name="marks" class="form-control" value="{{ old('marks', 1) }}" required>
                    </div>
                    <div class="col-md-6" id="codeLangWrap" style="display:none;">
                        <label class="form-label">Language (optional)</label>
                        <input type="text" name="code_language" class="form-control" value="{{ old('code_language') }}"
                               placeholder="e.g. Python, Java, PHP">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Question</label>
                        <textarea name="question_text" class="form-control" rows="3" required>{{ old('question_text') }}</textarea>
                    </div>
                    <div class="col-12" id="optionsWrap">
                        <label class="form-label">Options (mark correct)</label>
                        <div id="optionsList">
                            @for($i = 0; $i < 4; $i++)
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="options[{{ $i }}][is_correct]" value="1" title="Correct">
                                    </div>
                                    <input type="text" name="options[{{ $i }}][text]" class="form-control" placeholder="Option {{ $i+1 }}">
                                </div>
                            @endfor
                        </div>
                        <small class="text-muted">For single answer, tick exactly one. For multiple answers, tick all correct.</small>
                    </div>
                    <div class="col-md-6" id="tfWrap" style="display:none;">
                        <label class="form-label">Correct Answer</label>
                        <select name="correct_answer" class="form-select" id="tf_answer">
                            <option value="true">True</option>
                            <option value="false">False</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="blankWrap" style="display:none;">
                        <label class="form-label">Expected answer (auto-graded)</label>
                        <input type="text" name="correct_answer_blank" id="blank_answer" class="form-control"
                               placeholder="Exact answer (case-insensitive)">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Add Question</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Questions ({{ $quiz->questions->count() }})</strong></div>
        <div class="card-body">
            @forelse($quiz->questions as $q)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="badge bg-light text-dark">{{ $q->typeLabel() }}</span>
                            <span class="badge bg-primary">{{ number_format($q->marks, 1) }} marks</span>
                            <div class="mt-2"><strong>Q{{ $loop->iteration }}.</strong> {{ $q->question_text }}</div>
                            @if($q->options->isNotEmpty())
                                <ul class="mb-0 mt-2">
                                    @foreach($q->options as $opt)
                                        <li>{{ $opt->option_text }} @if($opt->is_correct)<strong>(correct)</strong>@endif</li>
                                    @endforeach
                                </ul>
                            @elseif($q->correct_answer)
                                <div class="small text-muted mt-1">Answer key: {{ $q->correct_answer }}</div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('lecturer.quizzes.questions.destroy', [$lecturer, $quiz, $q]) }}"
                              onsubmit="return confirm('Delete this question?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="alert alert-light mb-0">No questions yet.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Student Attempts ({{ $attempts->count() }})</strong></div>
        <div class="card-body table-responsive">
            @if($attempts->isEmpty())
                <div class="alert alert-light mb-0">No attempts yet.</div>
            @else
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Attempt</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Submitted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            <tr>
                                <td>
                                    {{ $attempt->student->user->name ?? 'N/A' }}
                                    <div class="small text-muted">{{ $attempt->student->student_id ?? '' }}</div>
                                </td>
                                <td>#{{ $attempt->attempt_number }}</td>
                                <td><span class="badge bg-{{ $attempt->status === 'graded' ? 'success' : ($attempt->status === 'submitted' ? 'warning text-dark' : 'secondary') }}">{{ $attempt->statusLabel() }}</span></td>
                                <td>
                                    @if($attempt->score !== null)
                                        {{ number_format($attempt->score, 1) }} / {{ number_format($attempt->max_score ?? $quiz->total_marks, 1) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ optional($attempt->submitted_at)->format('d M Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    @if($attempt->isSubmitted())
                                        <a href="{{ route('lecturer.quizzes.attempt', [$lecturer, $quiz, $attempt]) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            {{ $attempt->status === 'graded' ? 'View / Regrade' : 'Mark' }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

<script>
(function () {
    const type = document.getElementById('q_type');
    const optionsWrap = document.getElementById('optionsWrap');
    const tfWrap = document.getElementById('tfWrap');
    const blankWrap = document.getElementById('blankWrap');
    const codeLangWrap = document.getElementById('codeLangWrap');
    const blankAnswer = document.getElementById('blank_answer');
    const tfAnswer = document.getElementById('tf_answer');
    const form = document.getElementById('addQuestionForm');

    function sync() {
        const v = type.value;
        optionsWrap.style.display = (v === 'single_choice' || v === 'multiple_choice') ? '' : 'none';
        tfWrap.style.display = v === 'true_false' ? '' : 'none';
        blankWrap.style.display = v === 'fill_blank' ? '' : 'none';
        codeLangWrap.style.display = v === 'code' ? '' : 'none';
    }
    type.addEventListener('change', sync);
    sync();

    form.addEventListener('submit', function () {
        if (type.value === 'fill_blank' && blankAnswer.value) {
            // map blank field into correct_answer
            let hidden = form.querySelector('input[name="correct_answer"][data-blank]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'correct_answer';
                hidden.dataset.blank = '1';
                form.appendChild(hidden);
            }
            hidden.value = blankAnswer.value;
            tfAnswer.disabled = true;
        } else if (type.value === 'true_false') {
            blankAnswer.disabled = true;
        } else {
            // clear competing correct_answer from TF select when not used
            if (type.value !== 'true_false') {
                tfAnswer.disabled = true;
            }
            if (type.value !== 'fill_blank') {
                blankAnswer.disabled = true;
            }
        }
    });
})();
</script>
@include('partials.quiz-datetime-tz')
@endsection
