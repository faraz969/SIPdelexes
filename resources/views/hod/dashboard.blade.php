@extends('layouts.app')

@section('content')
<div class="container">
    <h3>HOD Dashboard - {{ $department->name }}</h3>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Pending</h6>
                    <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Approved</h6>
                    <h3 class="mb-0 text-success">{{ $stats['approved'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Rejected</h6>
                    <h3 class="mb-0 text-danger">{{ $stats['rejected'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('hod.dashboard') }}" class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="academic_year" class="form-label">Academic Year</label>
                    <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                        <option value="">All academic years</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ $academicYear === $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                @if($academicYear)
                    <div class="col-md-2">
                        <a href="{{ route('hod.dashboard') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                @endif
            </div>
        </div>
    </form>

    <!-- Pending Applications Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Pending Applications ({{ $pendingApplications->count() }})</h5>
        </div>
        <div class="card-body">
            @if($pendingApplications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Applicant</th>
                                <th>Email</th>
                                <th>Application #</th>
                                <th>Academic Year</th>
                                <th>Preferred Session</th>
                                <th>Form Type</th>
                                <th>Qualification</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingApplications as $app)
                                <tr>
                                    <td>{{ $app->id }}</td>
                                    <td>{{ $app->user->name ?? '-' }}</td>
                                    <td>{{ $app->user->email ?? '-' }}</td>
                                    <td>{{ $app->application_number }}</td>
                                    <td>{{ $app->academic_year }}</td>
                                    <td>{{ $app->admissionForm?->preferred_session ?? ($app->data['preferred_session'] ?? '—') }}</td>
                                    <td>{{ ucfirst($app->form_type) }}</td>
                                    <td>
                                        @php
                                            $qualifiedPrograms = $app->getQualifiedPrograms();
                                        @endphp
                                        @if($qualifiedPrograms->isNotEmpty())
                                            <span class="badge bg-success">Qualified</span>
                                            <div class="mt-1">
                                                @foreach($qualifiedPrograms as $program)
                                                    <small class="d-block text-muted">{{ $program->name }}</small>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="badge bg-danger">Unqualified</span>
                                        @endif
                                    </td>
                                    <td>{{ $app->created_at->format('M d, Y') }}</td>
                                    <td>
                                        @if($app->department_ids && count($app->department_ids) > 1)
                                            <small class="text-muted">Multiple departments</small><br>
                                        @endif
                                        <a class="btn btn-sm btn-primary" href="{{ route('hod.applications.show', $app->id) }}">Review</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No pending applications to review.</p>
            @endif
        </div>
    </div>

    <!-- Reviewed Applications Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Reviewed Applications ({{ $reviewedApplications->count() }})</h5>
        </div>
        <div class="card-body">
            @if($reviewedApplications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Applicant</th>
                                <th>Email</th>
                                <th>Application #</th>
                                <th>Academic Year</th>
                                <th>Preferred Session</th>
                                <th>Form Type</th>
                                <th>Qualification</th>
                                <th>HOD Status</th>
                                <th>Reviewed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reviewedApplications as $app)
                                <tr>
                                    <td>{{ $app->id }}</td>
                                    <td>{{ $app->user->name ?? '-' }}</td>
                                    <td>{{ $app->user->email ?? '-' }}</td>
                                    <td>{{ $app->application_number }}</td>
                                    <td>{{ $app->academic_year }}</td>
                                    <td>{{ $app->admissionForm?->preferred_session ?? ($app->data['preferred_session'] ?? '—') }}</td>
                                    <td>{{ ucfirst($app->form_type) }}</td>
                                    <td>
                                        @php
                                            $qualifiedPrograms = $app->getQualifiedPrograms();
                                        @endphp
                                        @if($qualifiedPrograms->isNotEmpty())
                                            <span class="badge bg-success">Qualified</span>
                                            <div class="mt-1">
                                                @foreach($qualifiedPrograms as $program)
                                                    <small class="d-block text-muted">{{ $program->name }}</small>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="badge bg-danger">Unqualified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($app->hod_status === 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($app->hod_status === 'rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($app->hod_status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $app->hod_reviewed_at ? $app->hod_reviewed_at->format('M d, Y') : '-' }}</td>
                                    <td>
                                        @if($app->department_ids && count($app->department_ids) > 1)
                                            <small class="text-muted">Multiple departments</small><br>
                                        @endif
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('hod.applications.show', $app->id) }}">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No reviewed applications yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection