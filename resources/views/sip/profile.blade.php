@extends('layouts.app')

@section('title', 'Student Profile - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-user-circle"></i> Student Profile</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Biodata (Read-Only)</h5>
                </div>
                <div class="card-body">
                    @php
                        $biodata = $student->biodata ?? [];
                    @endphp
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Full Name:</strong> {{ $biodata['full_name'] ?? $student->user->name }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Email:</strong> {{ $biodata['email'] ?? $student->user->email }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Phone:</strong> {{ $student->user->phone ?? 'N/A' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Date of Birth:</strong> {{ isset($biodata['dob']) ? \Carbon\Carbon::parse($biodata['dob'])->format('d M Y') : 'N/A' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Gender:</strong> {{ $biodata['gender'] ?? 'N/A' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Nationality:</strong> {{ $biodata['nationality'] ?? 'N/A' }}
                        </div>
                        <div class="col-md-12 mb-3">
                            <strong>Address:</strong> {{ $biodata['address'] ?? 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Programme & Faculty</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Programme:</strong> {{ $student->program->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Department:</strong> {{ $student->department->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Academic Year:</strong> {{ $student->academic_year }}
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Academic Status:</strong> 
                            <span class="badge bg-{{ $student->academic_status === 'active' ? 'success' : ($student->academic_status === 'deferred' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($student->academic_status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

