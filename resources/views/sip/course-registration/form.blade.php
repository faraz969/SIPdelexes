@extends('layouts.app')

@section('title', 'Course Registration - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-book"></i> Course Registration</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="{{ route('sip.course-registration.list') }}" class="btn btn-outline-primary mb-3"><i class="fas fa-list"></i> My Registrations</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!$student->program_id)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> You have no program assigned. Please contact the registrar before registering for courses.
        </div>
    @elseif($availableOfferings->isEmpty())
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Course registration has not been opened for your program yet.
            Your HOD or Registrar must publish a semester course package before you can register.
        </div>
    @elseif($existingRegistration)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You have already registered for {{ $semester }} {{ $academicYear }}.
            <a href="{{ route('sip.course-registration.list') }}" class="btn btn-sm btn-info">View Registration</a>
        </div>
    @else
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <label for="offering_id" class="form-label"><strong>Select semester Course registration</strong></label>
                        <select id="offering_id" class="form-select" onchange="window.location='{{ route('sip.course-registration.show') }}?offering_id=' + this.value;">
                            @foreach($availableOfferings as $item)
                                <option value="{{ $item->id }}" {{ optional($offering)->id == $item->id ? 'selected' : '' }}>
                                    {{ $item->semester }} — {{ $item->academic_year }}
                                    ({{ count($item->course_ids ?? []) }} courses)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Confirm Registration — {{ $semester }} {{ $academicYear }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            The courses below were set by your department for this semester.
                            Review them and tick the confirmation box to register. You do not select individual courses.
                        </p>

                        @if($courses->isEmpty())
                            <div class="alert alert-warning mb-0">
                                This package has no active courses. Please contact your HOD or Registrar.
                            </div>
                        @else
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>Course Code</th>
                                            <th>Course Title</th>
                                            <th>Type</th>
                                            <th>Credits</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $totalCredits = 0; @endphp
                                        @foreach($courses as $course)
                                            @php
                                                $credits = (float) ($course->total_credit_units ?? $course->credit_units);
                                                $totalCredits += $credits;
                                            @endphp
                                            <tr>
                                                <td>{{ $course->course_code }}</td>
                                                <td>{{ $course->course_title }}</td>
                                                <td>
                                                    @if($course->is_elective)
                                                        <span class="badge bg-info">Elective</span>
                                                    @else
                                                        <span class="badge bg-primary">Core</span>
                                                    @endif
                                                </td>
                                                <td>{{ $credits }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold">
                                            <td colspan="3">Total credit units</td>
                                            <td>{{ $totalCredits }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <form method="POST" action="{{ route('sip.course-registration.register') }}">
                                @csrf
                                <input type="hidden" name="offering_id" value="{{ $offering->id }}">

                                <div class="form-check mb-3">
                                    <input class="form-check-input @error('confirm_registration') is-invalid @enderror"
                                           type="checkbox" name="confirm_registration" value="1" id="confirm_registration" required>
                                    <label class="form-check-label" for="confirm_registration">
                                        I confirm registration for the courses listed above for
                                        <strong>{{ $semester }} {{ $academicYear }}</strong>.
                                    </label>
                                    @error('confirm_registration')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Late registration may incur additional fees.
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check"></i> Confirm Registration
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Registration Info</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Student ID:</strong> {{ $student->student_id }}</p>
                        <p><strong>Program:</strong> {{ $student->program->name ?? 'N/A' }}</p>
                        <p><strong>Semester:</strong> {{ $semester }}</p>
                        <p><strong>Academic Year:</strong> {{ $academicYear }}</p>
                        <hr>
                        <p><strong>Courses in package:</strong> {{ $courses->count() }}</p>
                        <p><strong>Payment Status:</strong></p>
                        <p>Current: {{ number_format($student->getPaymentPercentage(), 1) }}%</p>
                        <p class="text-success">✓ Registration Enabled</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
