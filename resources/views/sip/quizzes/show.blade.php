@extends('layouts.app')

@section('title', $quiz->title)

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">{{ $quiz->title }}</h2>
            <div class="text-muted">{{ $quiz->course->course_code ?? '' }} · {{ $quiz->academic_year }} / {{ $quiz->semester }}</div>
        </div>
        <a href="{{ route('sip.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">All Quizzes</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            @if($quiz->instructions)
                <p>{{ $quiz->instructions }}</p>
            @endif
            <ul class="mb-3">
                <li>Questions: {{ $quiz->questions->count() }}</li>
                <li>Total marks: {{ number_format($quiz->total_marks, 1) }}</li>
                <li>Max attempts: {{ $quiz->max_attempts }}</li>
                @if($quiz->duration_minutes)
                    <li>Duration: {{ $quiz->duration_minutes }} minutes</li>
                @endif
                @if($quiz->opens_at)
                    <li>Opens: {{ $quiz->opens_at->timezone(config('app.timezone'))->format('d M Y H:i') }} ({{ config('app.timezone') }})</li>
                @endif
                @if($quiz->closes_at)
                    <li>Closes: {{ $quiz->closes_at->timezone(config('app.timezone'))->format('d M Y H:i') }} ({{ config('app.timezone') }})</li>
                @endif
            </ul>

            @if($inProgress)
                <a href="{{ route('sip.quizzes.attempt', [$quiz, $inProgress]) }}" class="btn btn-warning">
                    Continue Attempt #{{ $inProgress->attempt_number }}
                </a>
            @elseif($quiz->isOpen() && $attempts->count() < $quiz->max_attempts)
                <form method="POST" action="{{ route('sip.quizzes.start', $quiz) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Start a new attempt now?');">
                        Start Quiz
                    </button>
                </form>
            @elseif(!$quiz->isOpen())
                <div class="alert alert-secondary mb-0">{{ $quiz->availabilityMessage() }}</div>
            @else
                <div class="alert alert-secondary mb-0">You have used all allowed attempts.</div>
            @endif
        </div>
    </div>

    @if($attempts->isNotEmpty())
        <div class="card">
            <div class="card-header">Your Attempts</div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            <tr>
                                <td>{{ $attempt->attempt_number }}</td>
                                <td>{{ $attempt->statusLabel() }}</td>
                                <td>
                                    @if($attempt->score !== null && $quiz->show_score_to_student)
                                        {{ number_format($attempt->score, 1) }} / {{ number_format($attempt->max_score ?? $quiz->total_marks, 1) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($attempt->isInProgress())
                                        <a href="{{ route('sip.quizzes.attempt', [$quiz, $attempt]) }}" class="btn btn-sm btn-outline-warning">Continue</a>
                                    @else
                                        <a href="{{ route('sip.quizzes.result', [$quiz, $attempt]) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
