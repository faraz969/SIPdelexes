@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('lecturer.dashboard') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h4>
                {{ $lecturer->course->course_code ?? 'N/A' }} - {{ $lecturer->course->course_title ?? '' }}
                <span class="badge bg-secondary">{{ $lecturer->session->name ?? 'N/A' }} Session</span>
            </h4>
            <p class="text-muted mb-0">Students registered for this course with the same preferred session. You can upload grades for these students.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($students->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Program</th>
                                <th>Academic Year</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td>{{ $student->student_id }}</td>
                                    <td>{{ $student->user->name ?? 'N/A' }}</td>
                                    <td>{{ $student->user->email ?? 'N/A' }}</td>
                                    <td>{{ $student->program->name ?? 'N/A' }}</td>
                                    <td>{{ $student->academic_year }}</td>
                                    <td>
                                        <span class="badge bg-info">Grade upload coming soon</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No students assigned to this course and session. Students who register for this course with {{ $lecturer->session->name ?? '' }} as their preferred session will appear here.</p>
            @endif
        </div>
    </div>
</div>
@endsection
