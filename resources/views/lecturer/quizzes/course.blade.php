@extends('layouts.app')

@section('title', 'Quizzes - ' . ($lecturer->course->course_code ?? 'Course'))

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">{{ $lecturer->course->course_code }} — {{ $lecturer->course->course_title }}</h3>
            <div class="text-muted">{{ $academicYear }} · {{ $semester }}</div>
        </div>
        <a href="{{ route('lecturer.quizzes.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><strong>Create Quiz</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('lecturer.quizzes.store', $lecturer) }}">
                @csrf
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" required maxlength="255"
                               placeholder="e.g. Week 3 Quiz">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Duration (min)</label>
                        <input type="number" name="duration_minutes" class="form-control" min="1" max="600"
                               value="{{ old('duration_minutes') }}" placeholder="Optional">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Max attempts</label>
                        <input type="number" name="max_attempts" class="form-control" min="1" max="20"
                               value="{{ old('max_attempts', 1) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Instructions</label>
                        <textarea name="instructions" class="form-control" rows="2">{{ old('instructions') }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Opens at</label>
                        <input type="datetime-local" name="opens_at" class="form-control" value="{{ old('opens_at') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Closes at</label>
                        <input type="datetime-local" name="closes_at" class="form-control" value="{{ old('closes_at') }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_published" value="1" id="is_published"
                                   {{ old('is_published') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">Publish now</label>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="show_score_to_student" value="1" id="show_score"
                                   {{ old('show_score_to_student', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_score">Show score</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Create Quiz</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Quizzes ({{ $quizzes->count() }})</strong></div>
        <div class="card-body table-responsive">
            @if($quizzes->isEmpty())
                <div class="alert alert-light mb-0">No quizzes for this term yet.</div>
            @else
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Questions</th>
                            <th>Marks</th>
                            <th>Attempts</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quizzes as $quiz)
                            <tr>
                                <td>
                                    <strong>{{ $quiz->title }}</strong>
                                    @if($quiz->duration_minutes)
                                        <div class="small text-muted">{{ $quiz->duration_minutes }} min</div>
                                    @endif
                                </td>
                                <td>{{ $quiz->questions_count }}</td>
                                <td>{{ number_format($quiz->total_marks, 1) }}</td>
                                <td>{{ $quiz->attempts_count }}</td>
                                <td>
                                    @if($quiz->is_published)
                                        <span class="badge bg-success">Published</span>
                                    @else
                                        <span class="badge bg-secondary">Draft</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('lecturer.quizzes.show', [$lecturer, $quiz]) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
