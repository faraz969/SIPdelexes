@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Registrar Approved Applications</h3>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>

    <p class="text-muted">Applications that have been approved by the Registrar, including the issued admission offer type.</p>

    <form method="GET" action="{{ route('admin.applications.registrar-approved') }}" class="card mb-4">
        <div class="card-body">
            <div class="row align-items-end g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search name, email, phone, serial, application #...">
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
                <div class="col-md-3">
                    <label for="offer_type" class="form-label">Offer Type</label>
                    <select name="offer_type" id="offer_type" class="form-select" onchange="this.form.submit()">
                        <option value="">All offer types</option>
                        @foreach($offerTypes as $type)
                            <option value="{{ $type }}" {{ $offerType === $type ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('-', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
                @if($academicYear || $departmentId || !empty($programId) || !empty($search) || !empty($offerType))
                    <div class="col-md-2">
                        <a href="{{ route('admin.applications.registrar-approved') }}" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                @endif
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Approved by Registrar ({{ $applications->total() }})</h5>
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
                                <th>Student ID</th>
                                <th>Academic Year</th>
                                <th>Department</th>
                                <th>Programme</th>
                                <th>Offer Type</th>
                                <th>Approved</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $app)
                                @php
                                    $offerData = $app->admissionFormData
                                        ?? optional($app->student)->admissionFormData;
                                @endphp
                                <tr>
                                    <td>{{ $app->id }}</td>
                                    <td>{{ $app->user->name ?? '-' }}</td>
                                    <td>{{ $app->user->email ?? '-' }}</td>
                                    <td>{{ $app->application_number }}</td>
                                    <td>{{ optional($app->student)->student_id ?? '—' }}</td>
                                    <td>{{ $app->academic_year }}</td>
                                    <td>{{ $app->department->name ?? '-' }}</td>
                                    <td>{{ optional(optional($app->student)->program)->name ?? '—' }}</td>
                                    <td>
                                        @if($offerData)
                                            <span class="badge bg-info text-dark">{{ $offerData->offer_type_label }}</span>
                                            @if(($offerData->offer_type ?? '') === 'conditional' && $offerData->conditional_subject)
                                                <div class="small text-muted mt-1">{{ $offerData->conditional_subject }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $app->registrar_reviewed_at ? $app->registrar_reviewed_at->format('M d, Y') : '-' }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('admin.applications.show', $app->id) }}">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $applications->links('pagination::bootstrap-4') }}
                </div>
            @else
                <p class="text-muted mb-0">No registrar-approved applications found.</p>
            @endif
        </div>
    </div>
</div>
@endsection
