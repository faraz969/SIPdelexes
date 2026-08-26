@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ $pageTitle }}</h3>
        <a href="{{ route($routePrefix . '.semester-offerings.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> New Package
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Program</label>
                    <select name="program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" value="{{ request('academic_year') }}" placeholder="e.g. 2025/2026" onchange="this.form.submit()">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        <option value="First Semester" {{ request('semester') == 'First Semester' ? 'selected' : '' }}>First Semester</option>
                        <option value="Second Semester" {{ request('semester') == 'Second Semester' ? 'selected' : '' }}>Second Semester</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-select" onchange="this.form.submit()">
                        <option value="">All</option>
                        @foreach(($levels ?? []) as $levelOption)
                            <option value="{{ $levelOption }}" {{ request('level') == $levelOption ? 'selected' : '' }}>{{ $levelOption }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body">
            @if($offerings->isEmpty())
                <p class="text-muted mb-0">No semester course packages yet. <a href="{{ route($routePrefix . '.semester-offerings.create') }}">Create one</a> so students can confirm registration.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Program</th>
                                @if(!$department)
                                    <th>Department</th>
                                @endif
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Level</th>
                                <th>Courses</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($offerings as $offering)
                                <tr>
                                    <td>{{ $offering->program->name ?? '—' }}</td>
                                    @if(!$department)
                                        <td>{{ $offering->program->department->name ?? '—' }}</td>
                                    @endif
                                    <td>{{ $offering->academic_year }}</td>
                                    <td>{{ $offering->semester }}</td>
                                    <td><span class="badge bg-primary">{{ $offering->level ?? '100' }}</span></td>
                                    <td>{{ count($offering->course_ids ?? []) }}</td>
                                    <td>
                                        @if($offering->is_published)
                                            <span class="badge bg-success">Published</span>
                                        @else
                                            <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route($routePrefix . '.semester-offerings.show', $offering) }}" class="btn btn-outline-info" title="View"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route($routePrefix . '.semester-offerings.edit', $offering) }}" class="btn btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route($routePrefix . '.semester-offerings.toggle-publish', $offering) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-{{ $offering->is_published ? 'secondary' : 'success' }}" title="{{ $offering->is_published ? 'Unpublish' : 'Publish' }}">
                                                    <i class="fas fa-{{ $offering->is_published ? 'eye-slash' : 'bullhorn' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route($routePrefix . '.semester-offerings.destroy', $offering) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this package?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
