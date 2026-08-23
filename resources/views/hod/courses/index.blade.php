@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Courses — {{ $department->name }}</h3>
        <a href="{{ route('hod.courses.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add Course
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Program</label>
                    <select name="program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Programs</option>
                        @foreach($programs as $p)
                            <option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ request('type', 'all') == 'all' ? 'selected' : '' }}>All</option>
                        <option value="core" {{ request('type') == 'core' ? 'selected' : '' }}>Core</option>
                        <option value="elective" {{ request('type') == 'elective' ? 'selected' : '' }}>Elective</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            @if($courses->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Program</th>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Credits</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courses as $course)
                                <tr>
                                    <td>{{ $course->course_code }}</td>
                                    <td>{{ $course->course_title }}</td>
                                    <td>{{ $course->program->name ?? '—' }}</td>
                                    <td>{{ $course->academic_year ?: '—' }}</td>
                                    <td>{{ $course->semester ?: '—' }}</td>
                                    <td>{{ $course->credit_units }}</td>
                                    <td>
                                        @if($course->is_elective)
                                            <span class="badge bg-info">Elective</span>
                                        @else
                                            <span class="badge bg-primary">Core</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($course->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('hod.courses.show', $course) }}" class="btn btn-outline-info"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('hod.courses.edit', $course) }}" class="btn btn-outline-warning"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('hod.courses.destroy', $course) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this course?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No courses found for your department. <a href="{{ route('hod.courses.create') }}">Create one</a>.</p>
            @endif
        </div>
    </div>
</div>
@endsection
