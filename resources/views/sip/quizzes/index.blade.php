@extends('layouts.app')

@section('title', 'Quizzes - SIP')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1"><i class="fas fa-question-circle"></i> Quizzes</h2>
            <p class="text-muted mb-0">Quizzes for your registered courses.</p>
        </div>
        <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($quizzes->isEmpty())
        <div class="alert alert-info mb-0">No published quizzes for your registered courses yet.</div>
    @else
        <div class="row">
            @foreach($quizzes as $quiz)
                @php
                    $mine = $attemptCounts->get($quiz->id, collect());
                    $latest = $mine->sortByDesc('attempt_number')->first();
                @endphp
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong>{{ $quiz->title }}</strong>
                            <div class="small text-muted">
                                {{ $quiz->course->course_code ?? '' }} · {{ $quiz->academic_year }} / {{ $quiz->semester }}
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <span class="badge bg-secondary">{{ $quiz->questions_count }} questions</span>
                                <span class="badge bg-light text-dark">{{ number_format($quiz->total_marks, 1) }} marks</span>
                                @if($quiz->isOpen())
                                    <span class="badge bg-success">Open</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ ucfirst($quiz->availabilityStatus() === 'scheduled' ? 'Scheduled' : ($quiz->availabilityStatus() === 'closed' ? 'Closed' : 'Unavailable')) }}</span>
                                @endif
                            </p>
                            @if($latest)
                                <p class="small text-muted mb-3">
                                    Your latest: {{ $latest->statusLabel() }}
                                    @if($latest->score !== null && $quiz->show_score_to_student)
                                        · Score {{ number_format($latest->score, 1) }}/{{ number_format($latest->max_score ?? $quiz->total_marks, 1) }}
                                    @endif
                                </p>
                            @endif
                            <a href="{{ route('sip.quizzes.show', $quiz) }}" class="btn btn-outline-primary btn-sm">Open</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
