@extends('layouts.app')

@section('title', 'Result - ' . $quiz->title)

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">{{ $quiz->title }} — Result</h2>
            <div class="text-muted">Attempt #{{ $attempt->attempt_number }} · {{ $attempt->statusLabel() }}</div>
        </div>
        <a href="{{ route('sip.quizzes.show', $quiz) }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            @if($quiz->show_score_to_student && $attempt->score !== null)
                <h4 class="mb-1">
                    Score: {{ number_format($attempt->score, 1) }}
                    / {{ number_format($attempt->max_score ?? $quiz->total_marks, 1) }}
                </h4>
            @elseif($attempt->status === 'submitted')
                <div class="alert alert-info mb-0">Submitted. Waiting for lecturer marking on written questions.</div>
            @else
                <div class="alert alert-secondary mb-0">Score is hidden by the lecturer.</div>
            @endif
            @if($attempt->lecturer_feedback)
                <p class="mt-3 mb-0"><strong>Lecturer feedback:</strong> {{ $attempt->lecturer_feedback }}</p>
            @endif
        </div>
    </div>

    @foreach($quiz->questions as $question)
        @php $answer = $attempt->answers->firstWhere('quiz_question_id', $question->id); @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><strong>Q{{ $loop->iteration }}.</strong> {{ $question->typeLabel() }}</span>
                <span>
                    @if($answer && $answer->marks_awarded !== null && $quiz->show_score_to_student)
                        {{ number_format($answer->marks_awarded, 1) }} / {{ number_format($question->marks, 1) }}
                    @else
                        {{ number_format($question->marks, 1) }} marks
                    @endif
                </span>
            </div>
            <div class="card-body">
                <p>{{ $question->question_text }}</p>
                @if(in_array($question->type, ['single_choice', 'multiple_choice', 'true_false'], true))
                    <ul>
                        @foreach($question->options as $opt)
                            @php $selected = in_array($opt->id, $answer->selected_option_ids ?? [], false); @endphp
                            <li class="{{ $selected ? 'fw-bold' : '' }}">
                                {{ $opt->option_text }}
                                @if($selected) <span class="badge bg-info text-dark">Your answer</span> @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="border rounded p-2 bg-light" style="white-space: pre-wrap; font-family: {{ $question->type === 'code' ? 'monospace' : 'inherit' }};">
                        {{ $answer->answer_text ?? '—' }}
                    </div>
                @endif
                @if($answer && $answer->grader_comment)
                    <div class="small text-muted mt-2">Comment: {{ $answer->grader_comment }}</div>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
