@extends('layouts.app')

@section('title', 'Mark Attempt')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">Mark: {{ $quiz->title }}</h3>
            <div class="text-muted">
                {{ $attempt->student->user->name ?? 'Student' }}
                ({{ $attempt->student->student_id ?? '' }})
                · Attempt #{{ $attempt->attempt_number }}
                · {{ $attempt->statusLabel() }}
            </div>
        </div>
        <a href="{{ route('lecturer.quizzes.show', [$lecturer, $quiz]) }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('lecturer.quizzes.grade', [$lecturer, $quiz, $attempt]) }}">
        @csrf
        @foreach($quiz->questions as $question)
            @php
                $answer = $attempt->answers->firstWhere('quiz_question_id', $question->id);
            @endphp
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <span>
                        <strong>Q{{ $loop->iteration }}.</strong>
                        <span class="badge bg-light text-dark">{{ $question->typeLabel() }}</span>
                    </span>
                    <span>Max: {{ number_format($question->marks, 1) }}</span>
                </div>
                <div class="card-body">
                    <p>{{ $question->question_text }}</p>

                    @if(in_array($question->type, ['single_choice', 'multiple_choice', 'true_false'], true))
                        <ul>
                            @foreach($question->options as $opt)
                                @php $selected = in_array($opt->id, $answer->selected_option_ids ?? [], false); @endphp
                                <li class="{{ $selected ? 'fw-bold' : '' }}">
                                    {{ $opt->option_text }}
                                    @if($selected) <span class="badge bg-info text-dark">Selected</span> @endif
                                    @if($opt->is_correct) <span class="badge bg-success">Correct</span> @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="border rounded p-2 bg-light mb-2" style="white-space: pre-wrap; font-family: {{ $question->type === 'code' ? 'monospace' : 'inherit' }};">
                            {{ $answer->answer_text ?? '—' }}
                        </div>
                        @if($question->type === 'fill_blank' && $question->correct_answer)
                            <div class="small text-muted">Expected: {{ $question->correct_answer }}</div>
                        @endif
                    @endif

                    <div class="row g-2 mt-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Marks awarded</label>
                            <input type="number" step="0.5" min="0" max="{{ $question->marks }}"
                                   name="marks[{{ $question->id }}]" class="form-control"
                                   value="{{ old('marks.'.$question->id, $answer->marks_awarded) }}" required>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Comment (optional)</label>
                            <input type="text" name="comments[{{ $question->id }}]" class="form-control"
                                   value="{{ old('comments.'.$question->id, $answer->grader_comment) }}">
                        </div>
                    </div>
                    @if($answer && $answer->is_auto_graded)
                        <div class="small text-muted mt-1">Auto-graded: {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}</div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="card mb-3">
            <div class="card-body">
                <label class="form-label">Overall feedback to student</label>
                <textarea name="lecturer_feedback" class="form-control" rows="3">{{ old('lecturer_feedback', $attempt->lecturer_feedback) }}</textarea>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Save Grades</button>
    </form>
</div>
@endsection
