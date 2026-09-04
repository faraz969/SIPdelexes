@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ $pageTitle }}</h3>
        <a href="{{ route('registrar.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('registrar.applications.' . $status) }}" class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search name, email, phone, serial, application #, session, campus, program...">
                </div>
                <div class="col-md-3">
                    <label for="department_id" class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All departments</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ (string) $departmentId === (string) $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="program_id" class="form-label">Program</label>
                    <select name="program_id" id="program_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" {{ (string) ($programId ?? '') === (string) $program->id ? 'selected' : '' }}>
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="academic_year" class="form-label">Academic Year</label>
                    <select name="academic_year" id="academic_year" class="form-select" onchange="this.form.submit()">
                        <option value="">All academic years</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" {{ $academicYear === $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                @if($academicYear || $departmentId || !empty($programId) || !empty($search))
                    <div class="col-md-2">
                        <a href="{{ route('registrar.applications.' . $status) }}" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                @endif
            </div>
            @if(!empty($search))
                <small class="text-muted d-block mt-2">Searching for: "{{ $search }}" ({{ $applications->count() }} result(s))</small>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ $pageTitle }} ({{ $applications->count() }})</h5>
        </div>
        <div class="card-body">
            @if($applications->count() > 0)
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
                                <th>Department</th>
                                @if($status === 'pending')
                                    <th>HOD Status</th>
                                @else
                                    <th>Registrar Status</th>
                                    <th>Reviewed</th>
                                @endif
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $app)
                                <tr>
                                    <td>{{ $app->id }}</td>
                                    <td>{{ $app->user->name ?? '-' }}</td>
                                    <td>{{ $app->user->email ?? '-' }}</td>
                                    <td>{{ $app->application_number }}</td>
                                    <td>{{ $app->academic_year }}</td>
                                    <td>{{ $app->admissionForm?->preferred_session ?? ($app->data['preferred_session'] ?? '—') }}</td>
                                    <td>{{ ucfirst($app->form_type) }}</td>
                                    <td>
                                        @php $qualifiedPrograms = $app->getQualifiedPrograms(); @endphp
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
                                    <td>{{ $app->department->name ?? '-' }}</td>
                                    @if($status === 'pending')
                                        <td>
                                            @if($app->hod_status === 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @else
                                                <span class="badge bg-warning">{{ ucfirst($app->hod_status) }}</span>
                                            @endif
                                        </td>
                                    @else
                                        <td>
                                            @if($app->registrar_status === 'approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($app->registrar_status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($app->registrar_status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $app->registrar_reviewed_at ? $app->registrar_reviewed_at->format('M d, Y') : '-' }}</td>
                                    @endif
                                    <td>
                                        <a class="btn btn-sm {{ $status === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}"
                                           href="{{ route('registrar.applications.show', $app->id) }}">
                                            {{ $status === 'pending' ? 'Review' : 'View' }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">No applications found.</p>
            @endif
        </div>
    </div>
</div>
@endsection
