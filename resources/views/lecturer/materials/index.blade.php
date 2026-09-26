@extends('layouts.app')

@section('title', 'Course Materials - Lecturer')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0"><i class="fas fa-folder-open"></i> Course Materials</h3>
        <a href="{{ route('lecturer.dashboard') }}" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('lecturer.materials.index') }}" class="row g-3 align-items-end">
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
                @php $assignment = $card['assignment']; @endphp
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
                            <p class="mb-3">
                                <span class="badge bg-secondary">{{ $card['count'] }} material(s)</span>
                            </p>
                            <a href="{{ route('lecturer.materials.show', [
                                'lecturer' => $assignment,
                                'academic_year' => $card['academic_year'],
                                'semester' => $card['semester'],
                            ]) }}" class="btn btn-primary btn-sm">
                                Manage Materials
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
