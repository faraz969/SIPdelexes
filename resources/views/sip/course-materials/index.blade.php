@extends('layouts.app')

@section('title', 'Course Materials - SIP')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1"><i class="fas fa-folder-open"></i> Course Materials</h2>
            <p class="text-muted mb-0">Lecture notes, slides, and files from your lecturers.</p>
        </div>
        <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    @if(empty($groups))
        <div class="alert alert-info mb-0">
            No registered courses found. Register for courses first to see materials.
        </div>
    @else
        <div class="row">
            @foreach($groups as $group)
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong>{{ $group['course_code'] }}</strong> — {{ $group['course_title'] }}
                            <div class="small text-muted">{{ $group['academic_year'] }} · {{ $group['semester'] }}</div>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                <span class="badge bg-secondary">{{ $group['materials']->count() }} file(s)</span>
                            </p>
                            <a href="{{ route('sip.course-materials.course', [
                                'course' => $group['course_id'],
                                'academic_year' => $group['academic_year'],
                                'semester' => $group['semester'],
                            ]) }}" class="btn btn-outline-primary btn-sm">
                                View Materials
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
