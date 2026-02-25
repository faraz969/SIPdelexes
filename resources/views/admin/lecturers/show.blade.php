@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Lecturer Details</h4>
                    <div>
                        <a href="{{ route('admin.lecturers.edit', $lecturer) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.lecturers.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 180px;">Lecturer</th>
                            <td>{{ $lecturer->user->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $lecturer->user->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Course</th>
                            <td>{{ $lecturer->course->course_code ?? 'N/A' }} - {{ $lecturer->course->course_title ?? '' }}</td>
                        </tr>
                        <tr>
                            <th>Program</th>
                            <td>{{ $lecturer->course->program->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Session</th>
                            <td>{{ $lecturer->session->name ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
