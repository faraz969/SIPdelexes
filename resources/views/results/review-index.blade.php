@extends('layouts.app')

@section('title', $pageTitle ?? 'Course Results')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">{{ $pageTitle ?? 'Course Results' }}</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route($filterRoute) }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        @foreach($pendingStatuses as $key => $label)
                            <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if(!empty($showPublishSemester) && !empty($publishSemesterRoute))
        <div class="card mb-3 border-success">
            <div class="card-header bg-success text-white">Publish All Approved for Semester</div>
            <div class="card-body">
                <form method="POST" action="{{ route($publishSemesterRoute) }}" class="row g-2 align-items-end"
                      onsubmit="return confirm('Publish all finally approved sheets for this semester?');">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Academic Year</label>
                        <select name="academic_year" class="form-select" required>
                            @foreach(($academicYears ?? collect()) as $year)
                                <option value="{{ $year }}" {{ ($defaultAcademicYear ?? '') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Semester</label>
                        <select name="semester" class="form-select" required>
                            @foreach(($semesters ?? collect()) as $sem)
                                <option value="{{ $sem }}">{{ $sem }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success">Publish Semester</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body table-responsive">
            @if($sheets->isEmpty())
                <div class="alert alert-info mb-0">No result sheets for this filter.</div>
            @else
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Year / Semester</th>
                            <th>Lecturer</th>
                            <th>Status</th>
                            <th>Records</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sheets as $sheet)
                            <tr>
                                <td>
                                    <strong>{{ $sheet->course->course_code ?? 'N/A' }}</strong><br>
                                    <small>{{ $sheet->course->course_title ?? '' }}</small>
                                    @if($sheet->course->program)
                                        <br><small class="text-muted">{{ $sheet->course->program->name }}</small>
                                    @endif
                                </td>
                                <td>{{ $sheet->academic_year }}<br><small>{{ $sheet->semester }}</small></td>
                                <td>{{ $sheet->lecturer->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $sheet->status === 'published' ? 'success' : ($sheet->status === 'returned' ? 'warning' : 'primary') }}">
                                        {{ strtoupper(str_replace('_', ' ', $sheet->status)) }}
                                    </span>
                                </td>
                                <td>{{ $sheet->records->count() }}</td>
                                <td>
                                    <a href="{{ route($showRoute, $sheet) }}" class="btn btn-sm btn-outline-primary">Review</a>
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
