@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Lecturer Dashboard</h3>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($assignmentsWithStudents->isEmpty())
        <div class="alert alert-info">
            You have no course assignments yet. Please contact the administrator to be assigned to courses and sessions.
        </div>
    @else
        @foreach($assignmentsWithStudents as $item)
            @php
                $lecturer = $item['lecturer'];
                $students = $item['students'];
            @endphp
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        {{ $lecturer->course->course_code ?? 'N/A' }} - {{ $lecturer->course->course_title ?? '' }}
                        <span class="badge bg-secondary ms-2">{{ $lecturer->session->name ?? 'N/A' }} Session</span>
                    </h5>
                    <a href="{{ route('lecturer.students', $lecturer) }}" class="btn btn-sm btn-outline-primary">
                        View Students ({{ $students->count() }})
                    </a>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        Students registered for this course with {{ $lecturer->session->name ?? '' }} as their preferred session.
                    </p>
                    @if($students->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Program</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students->take(10) as $student)
                                        <tr>
                                            <td>{{ $student->student_id }}</td>
                                            <td>{{ $student->user->name ?? 'N/A' }}</td>
                                            <td>{{ $student->program->name ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('lecturer.students', $lecturer) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($students->count() > 10)
                            <a href="{{ route('lecturer.students', $lecturer) }}" class="btn btn-link btn-sm">
                                View all {{ $students->count() }} students →
                            </a>
                        @endif
                    @else
                        <p class="text-muted mb-0">No students assigned yet. Students who register for this course with this session will appear here.</p>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
