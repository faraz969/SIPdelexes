@extends('layouts.app')

@section('title', 'Course Results - Lecturer')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0"><i class="fas fa-chart-bar"></i> Course Results</h3>
        <a href="{{ route('lecturer.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lecturer.results.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Academic Year</label>
                    <select name="academic_year" class="form-select">
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ $academicYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                        @if($academicYears->isEmpty())
                            <option value="{{ $academicYear }}">{{ $academicYear }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <option value="">-- Use course default --</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem }}" {{ $semester == $sem ? 'selected' : '' }}>{{ $sem }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary" type="submit">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    @if($cards->isEmpty())
        <div class="alert alert-info">You have no course assignments. Contact Admin to be assigned to courses.</div>
    @else
        <div class="row">
            @foreach($cards as $card)
                @php
                    $assignment = $card['assignment'];
                    $sheet = $card['sheet'];
                    $status = $sheet->status ?? 'not started';
                @endphp
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <strong>{{ $assignment->course->course_code ?? 'N/A' }}</strong>
                            — {{ $assignment->course->course_title ?? '' }}
                            <div class="small text-muted">
                                Session: {{ $assignment->session->name ?? 'N/A' }}
                                · {{ $card['academic_year'] }} / {{ $card['semester'] }}
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <span class="badge bg-secondary">{{ $card['student_count'] }} students</span>
                                <span class="badge bg-info text-dark">{{ $card['component_count'] }} components</span>
                                @if($sheet)
                                    <span class="badge bg-{{ $status === 'published' ? 'success' : ($status === 'submitted' || $status === 'hod_approved' || $status === 'approved' ? 'primary' : ($status === 'returned' ? 'warning' : 'dark')) }}">
                                        {{ strtoupper(str_replace('_', ' ', $status)) }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-dark">NOT STARTED</span>
                                @endif
                            </p>
                            @if($card['progress'])
                                <div class="mb-2">
                                    <small>Marks entered: {{ $card['progress']['entered'] }}/{{ $card['progress']['expected'] }}
                                        ({{ $card['progress']['percent'] }}%)</small>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: {{ min(100, $card['progress']['percent']) }}%"></div>
                                    </div>
                                </div>
                            @endif
                            @if($card['component_count'] < 1)
                                <div class="alert alert-warning mb-2 py-2 small">
                                    No assessment components on this course. Ask Admin/HOD to configure them.
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <form method="POST" action="{{ route('lecturer.results.open', $assignment) }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="academic_year" value="{{ $card['academic_year'] }}">
                                <input type="hidden" name="semester" value="{{ $card['semester'] }}">
                                <button type="submit" class="btn btn-sm btn-primary" {{ $card['component_count'] < 1 ? 'disabled' : '' }}>
                                    <i class="fas fa-edit"></i> {{ $sheet ? 'Open Result Sheet' : 'Start Results' }}
                                </button>
                            </form>
                            <a href="{{ route('lecturer.students', $assignment) }}" class="btn btn-sm btn-outline-secondary">Students</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
