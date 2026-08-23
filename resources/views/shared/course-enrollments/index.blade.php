@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ $pageTitle }}</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route($filterRoute) }}" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                @isset($departments)
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ (string) ($departmentId ?? '') === (string) $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endisset

                <div class="col-md-3">
                    <label class="form-label">Program</label>
                    <select name="program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" {{ (string) ($programId ?? '') === (string) $program->id ? 'selected' : '' }}>
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Academic Year</label>
                    <select name="academic_year" class="form-select" onchange="this.form.submit()">
                        <option value="">All years</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ (string) $academicYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                        @if($academicYear && !$academicYears->contains($academicYear))
                            <option value="{{ $academicYear }}" selected>{{ $academicYear }}</option>
                        @endif
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="">All semesters</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem }}" {{ (string) $semester === (string) $sem ? 'selected' : '' }}>{{ $sem }}</option>
                        @endforeach
                    </select>
                </div>

                @if($academicYear || $semester || ($programId ?? null) || ($departmentId ?? null))
                    <div class="col-md-2">
                        <a href="{{ route($filterRoute) }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Registered Students per Course ({{ $rows->count() }})</h5>
        </div>
        <div class="card-body">
            @if($rows->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                @isset($departments)
                                    <th>Department</th>
                                @endisset
                                <th>Program</th>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Type</th>
                                <th class="text-center">Students Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td>{{ $row->course_code }}</td>
                                    <td>{{ $row->course_title }}</td>
                                    @isset($departments)
                                        <td>{{ $row->department_name }}</td>
                                    @endisset
                                    <td>{{ $row->program_name }}</td>
                                    <td>{{ $row->academic_year }}</td>
                                    <td>{{ $row->semester }}</td>
                                    <td>
                                        @if($row->is_elective)
                                            <span class="badge bg-info">Elective</span>
                                        @else
                                            <span class="badge bg-primary">Core</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $row->registered_count > 0 ? 'bg-success' : 'bg-secondary' }} fs-6">
                                            {{ $row->registered_count }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($row->registered_count > 0 && $row->semester !== '—' && $row->academic_year !== '—')
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="{{ route($showStudentsRoute, $row->course_id) }}?semester={{ urlencode($row->semester) }}&academic_year={{ urlencode($row->academic_year) }}">
                                                View students
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No courses found for the selected filters.</p>
            @endif
        </div>
    </div>
</div>
@endsection
