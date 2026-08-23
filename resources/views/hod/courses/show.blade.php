@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ $course->course_code }} — {{ $course->course_title }}</h3>
        <div>
            <a href="{{ route('hod.courses.edit', $course) }}" class="btn btn-warning btn-sm">Edit</a>
            <a href="{{ route('hod.courses.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered mb-0">
                <tr><th width="30%">Course Code</th><td>{{ $course->course_code }}</td></tr>
                <tr><th>Course Title</th><td>{{ $course->course_title }}</td></tr>
                <tr><th>Program</th><td>{{ $course->program->name ?? '—' }}</td></tr>
                <tr><th>Department</th><td>{{ $department->name }}</td></tr>
                <tr><th>Academic Year</th><td>{{ $course->academic_year ?: '—' }}</td></tr>
                <tr><th>Semester</th><td>{{ $course->semester ?: '—' }}</td></tr>
                <tr><th>Credit Units</th><td>{{ $course->credit_units }}{{ $course->total_credit_units ? ' / ' . $course->total_credit_units : '' }}</td></tr>
                <tr><th>Assessment Split</th><td>{{ $course->assessment_split ?: '—' }}</td></tr>
                <tr><th>Type</th><td>{{ $course->type_label }}</td></tr>
                <tr><th>Status</th><td>{{ $course->is_active ? 'Active' : 'Inactive' }}</td></tr>
            </table>
        </div>
    </div>
</div>
@endsection
