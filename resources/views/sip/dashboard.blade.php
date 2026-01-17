@extends('layouts.app')

@section('title', 'SIP Dashboard - Student Information Portal')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="fas fa-graduation-cap"></i> Student Information Portal
                <small class="text-muted">- Welcome, {{ $student->user->name }}</small>
            </h2>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Total Balance</h5>
                    <h3 class="mb-0">GHS {{ number_format($stats['total_balance'], 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-percentage"></i> Payment %</h5>
                    <h3 class="mb-0">{{ number_format($stats['payment_percentage'], 1) }}%</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white {{ $stats['can_register'] ? 'bg-success' : 'bg-warning' }}">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-book"></i> Course Registration</h5>
                    <h3 class="mb-0">{{ $stats['can_register'] ? 'Enabled' : 'Blocked' }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white {{ $stats['can_generate_exam_pin'] ? 'bg-success' : 'bg-danger' }}">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-key"></i> Exam PIN</h5>
                    <h3 class="mb-0">{{ $stats['can_generate_exam_pin'] ? 'Available' : 'Not Available' }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Menu -->
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user-circle fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Student Profile</h5>
                    <p class="card-text">View your biodata, programme, and academic status</p>
                    <a href="{{ route('sip.profile') }}" class="btn btn-primary">View Profile</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-book-reader fa-3x text-info mb-3"></i>
                    <h5 class="card-title">Academic Records</h5>
                    <p class="card-text">View registered courses, results, and GPA</p>
                    <a href="{{ route('sip.academic-records') }}" class="btn btn-info">View Records</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-download fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Downloads</h5>
                    <p class="card-text">Download admission letter, receipts, and exam slips</p>
                    <a href="{{ route('sip.downloads') }}" class="btn btn-success">View Downloads</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-credit-card fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Payment</h5>
                    <p class="card-text">View invoices and make payments</p>
                    <a href="{{ route('sip.payments.invoices') }}" class="btn btn-warning">Make Payment</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-book fa-3x text-secondary mb-3"></i>
                    <h5 class="card-title">Course Registration</h5>
                    <p class="card-text">Register for courses</p>
                    <a href="{{ route('sip.course-registration.show') }}" class="btn btn-secondary">Register Courses</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-key fa-3x text-danger mb-3"></i>
                    <h5 class="card-title">Exam PIN</h5>
                    <p class="card-text">Generate exam PIN</p>
                    <a href="{{ route('sip.exam.pins') }}" class="btn btn-danger">Generate PIN</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-pause-circle fa-3x text-dark mb-3"></i>
                    <h5 class="card-title">Deferment</h5>
                    <p class="card-text">Request deferment or view status</p>
                    <a href="{{ route('sip.deferment.form') }}" class="btn btn-dark">Manage Deferment</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Info -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Student Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Student ID:</strong> {{ $student->student_id }}</p>
                            <p><strong>Program:</strong> {{ $student->program->name ?? 'N/A' }}</p>
                            <p><strong>Academic Year:</strong> {{ $student->academic_year }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Academic Status:</strong> 
                                <span class="badge bg-{{ $student->academic_status === 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($student->academic_status) }}
                                </span>
                            </p>
                            <p><strong>Admission Date:</strong> {{ $student->admission_date->format('d M Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

