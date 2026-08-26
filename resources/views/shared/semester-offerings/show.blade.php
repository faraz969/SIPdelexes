@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">{{ $pageTitle }}</h3>
            <p class="text-muted mb-0">
                {{ $offering->program->name ?? '—' }} · Level {{ $offering->level ?? '100' }} · {{ $offering->semester }} · {{ $offering->academic_year }}
            </p>
        </div>
        <div>
            <a href="{{ route($routePrefix . '.semester-offerings.edit', $offering) }}" class="btn btn-warning btn-sm">Edit</a>
            <a href="{{ route($routePrefix . '.semester-offerings.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <small class="text-muted">Status</small>
                <div>
                    @if($offering->is_published)
                        <span class="badge bg-success">Published</span>
                    @else
                        <span class="badge bg-secondary">Draft</span>
                    @endif
                </div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <small class="text-muted">Courses</small>
                <div class="fs-4">{{ $courses->count() }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <small class="text-muted">Total Credits</small>
                <div class="fs-4">{{ $offering->totalCreditUnits() }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <small class="text-muted">Student Registrations</small>
                <div class="fs-4">{{ $registrationCount }}</div>
            </div></div>
        </div>
    </div>

    @if($offering->notes)
        <div class="alert alert-light border">{{ $offering->notes }}</div>
    @endif

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Courses in this package</h5></div>
        <div class="card-body">
            @if($courses->isEmpty())
                <p class="text-muted mb-0">No courses in this package.</p>
            @else
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Credits</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $course)
                            <tr>
                                <td>{{ $course->course_code }}</td>
                                <td>{{ $course->course_title }}</td>
                                <td>{{ $course->type_label }}</td>
                                <td>{{ $course->total_credit_units ?? $course->credit_units }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
