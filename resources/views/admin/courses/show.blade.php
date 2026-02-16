@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ $course->course_code }} – {{ $course->course_title }}</h4>
                    <div>
                        <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
                        <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Course Code:</strong></td>
                            <td>{{ $course->course_code }}</td>
                        </tr>
                        <tr>
                            <td><strong>Course Title:</strong></td>
                            <td>{{ $course->course_title }}</td>
                        </tr>
                        <tr>
                            <td><strong>Program:</strong></td>
                            <td>{{ $course->program->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Credit Units:</strong></td>
                            <td>{{ $course->credit_units }}{{ $course->total_credit_units ? ' (Total: ' . $course->total_credit_units . ')' : '' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Assessment Split:</strong></td>
                            <td>{{ $course->assessment_split ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Type:</strong></td>
                            <td>{{ $course->type_label }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>
                                @if($course->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Sort Order:</strong></td>
                            <td>{{ $course->sort_order }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
