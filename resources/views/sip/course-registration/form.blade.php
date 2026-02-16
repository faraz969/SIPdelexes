@extends('layouts.app')

@section('title', 'Course Registration - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-book"></i> Course Registration</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    @if(!$student->program_id)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> You have no program assigned. Please contact the registrar before registering for courses.
        </div>
    @elseif($existingRegistration)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You have already registered for {{ $semester }} {{ $academicYear }}.
            <a href="{{ route('sip.course-registration.list') }}" class="btn btn-sm btn-info">View Registration</a>
        </div>
    @else
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Register for Courses – {{ $semester }} {{ $academicYear }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Select courses below. <strong>Maximum total credit units: 21.</strong></p>

                        <form method="POST" action="{{ route('sip.course-registration.register') }}" id="courseRegistrationForm">
                            @csrf
                            <input type="hidden" name="semester" value="{{ $semester }}">
                            <input type="hidden" name="academic_year" value="{{ $academicYear }}">

                            @if($coreCourses->isNotEmpty())
                                <h6 class="text-primary mt-3 mb-2"><i class="fas fa-star"></i> Core Courses</h6>
                                <div class="mb-3">
                                    @foreach($coreCourses as $course)
                                        @php $credits = (float)($course->total_credit_units ?? $course->credit_units); @endphp
                                        <div class="form-check mb-2">
                                            <input class="form-check-input course-checkbox" type="checkbox" name="courses[]" value="{{ $course->id }}" id="course_{{ $course->id }}" data-credits="{{ $credits }}">
                                            <label class="form-check-label" for="course_{{ $course->id }}">
                                                <strong>{{ $course->course_code }}</strong> – {{ $course->course_title }}
                                                <span class="text-muted">({{ $credits }} credit{{ $credits != 1 ? 's' : '' }})</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($electiveCourses->isNotEmpty())
                                <h6 class="text-info mt-3 mb-2"><i class="fas fa-list"></i> Elective Courses</h6>
                                <div class="mb-3">
                                    @foreach($electiveCourses as $course)
                                        @php $credits = (float)($course->total_credit_units ?? $course->credit_units); @endphp
                                        <div class="form-check mb-2">
                                            <input class="form-check-input course-checkbox" type="checkbox" name="courses[]" value="{{ $course->id }}" id="course_{{ $course->id }}" data-credits="{{ $credits }}">
                                            <label class="form-check-label" for="course_{{ $course->id }}">
                                                <strong>{{ $course->course_code }}</strong> – {{ $course->course_title }}
                                                <span class="text-muted">({{ $credits }} credit{{ $credits != 1 ? 's' : '' }})</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($coreCourses->isEmpty() && $electiveCourses->isEmpty())
                                <div class="alert alert-warning">
                                    No courses are available for your program ({{ $student->program->name ?? 'N/A' }}). Please contact the admin.
                                </div>
                            @else
                                <div class="alert alert-secondary mb-3">
                                    <strong>Total selected:</strong> <span id="totalCredits">0</span> / 21 credit units
                                </div>
                                <p class="text-danger small" id="creditWarning" style="display:none;">Total credits exceed 21. Please reduce your selection.</p>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Late registration may incur additional fees.
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-check"></i> Submit Registration
                                </button>
                            @endif
                        </form>
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
                        <p><strong>Credit limit:</strong> 21 units max</p>
                        <p><strong>Payment Status:</strong></p>
                        <p>Current: {{ number_format($student->getPaymentPercentage(), 1) }}%</p>
                        <p class="text-success">✓ Registration Enabled</p>
                    </div>
                </div>
            </div>
        </div>

        @if($coreCourses->isNotEmpty() || $electiveCourses->isNotEmpty())
        <script>
        (function() {
            var maxCredits = 21;
            var checkboxes = document.querySelectorAll('.course-checkbox');
            var totalEl = document.getElementById('totalCredits');
            var warningEl = document.getElementById('creditWarning');
            var submitBtn = document.getElementById('submitBtn');

            function updateTotal() {
                var total = 0;
                checkboxes.forEach(function(cb) {
                    if (cb.checked) total += parseFloat(cb.getAttribute('data-credits') || 0);
                });
                if (totalEl) totalEl.textContent = total;
                if (warningEl) {
                    warningEl.style.display = total > maxCredits ? 'block' : 'none';
                }
                if (submitBtn) {
                    submitBtn.disabled = total > maxCredits || total === 0;
                }
            }

            checkboxes.forEach(function(cb) {
                cb.addEventListener('change', updateTotal);
            });
            updateTotal();
        })();
        </script>
        @endif
    @endif
</div>
@endsection
