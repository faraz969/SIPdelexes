@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Courses Management</h4>
                    <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Course
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Program</label>
                            <select name="program_id" class="form-select">
                                <option value="">All Programs</option>
                                @foreach($programs as $p)
                                    <option value="{{ $p->id }}" {{ request('program_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All</option>
                                <option value="core" {{ request('type') == 'core' ? 'selected' : '' }}>Core</option>
                                <option value="elective" {{ request('type') == 'elective' ? 'selected' : '' }}>Elective</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary">Filter</button>
                        </div>
                    </form>

                    @if($courses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Title</th>
                                        <th>Program</th>
                                        <th>Academic Year</th>
                                        <th>Semester</th>
                                        <th>Credits</th>
                                        <th>Assessment Split</th>
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
                                            <td>{{ $course->program->name ?? 'N/A' }}</td>
                                            <td>{{ $course->academic_year ?: '—' }}</td>
                                            <td>{{ $course->semester ?: '—' }}</td>
                                            <td>{{ $course->credit_units }}{{ $course->total_credit_units ? ' / ' . $course->total_credit_units : '' }}</td>
                                            <td>{{ $course->assessment_split ?: '—' }}</td>
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
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.courses.show', $course) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                    <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                    <form action="{{ route('admin.courses.destroy', $course) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this course?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-muted">No courses found.</p>
                            <a href="{{ route('admin.courses.create') }}" class="btn btn-primary">Create First Course</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
