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

    @if($existingRegistration)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> You have already registered for {{ $semester }} {{ $academicYear }}.
            <a href="{{ route('sip.course-registration.list') }}" class="btn btn-sm btn-info">View Registration</a>
        </div>
    @else
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Register for Courses - {{ $semester }} {{ $academicYear }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sip.course-registration.register') }}">
                            @csrf
                            
                            <input type="hidden" name="semester" value="{{ $semester }}">
                            <input type="hidden" name="academic_year" value="{{ $academicYear }}">

                            <div class="mb-3">
                                <label class="form-label">Select Courses</label>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Course list will be loaded from ERP. For now, this is a placeholder.
                                    <br><small>In production, courses will be fetched from the ERP system based on your program.</small>
                                </div>
                                
                                <!-- Placeholder course selection -->
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="courses[]" value="course1" id="course1">
                                    <label class="form-check-label" for="course1">
                                        Introduction to Computer Science (3 Credits)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="courses[]" value="course2" id="course2">
                                    <label class="form-check-label" for="course2">
                                        Mathematics for Computing (3 Credits)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="courses[]" value="course3" id="course3">
                                    <label class="form-check-label" for="course3">
                                        Programming Fundamentals (3 Credits)
                                    </label>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Note:</strong> Late registration may incur additional fees.
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check"></i> Register Courses
                            </button>
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

