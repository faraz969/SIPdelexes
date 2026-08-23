@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">{{ $course->course_code }} — {{ $course->course_title }}</h3>
            <p class="text-muted mb-0">{{ $semester }} · {{ $academicYear }}</p>
        </div>
        <a href="{{ route($backRoute) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>Program:</strong> {{ $course->program->name ?? '—' }}</div>
                @if($course->program && $course->program->department)
                    <div class="col-md-4"><strong>Department:</strong> {{ $course->program->department->name }}</div>
                @endif
                <div class="col-md-4"><strong>Students:</strong> {{ $students->count() }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if($students->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Program</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->student_id }}</td>
                                    <td>{{ $student->user->name ?? '—' }}</td>
                                    <td>{{ $student->user->email ?? '—' }}</td>
                                    <td>{{ $student->program->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No students registered for this course in the selected semester.</p>
            @endif
        </div>
    </div>
</div>
@endsection
