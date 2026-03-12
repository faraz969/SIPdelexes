@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Admission Form Defaults</h4>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('admin.admission-form-settings.edit') }}" class="mb-3">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Existing Academic Years</label>
                                <select name="academic_year" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Select Academic Year --</option>
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year }}" {{ ($academicYear ?? '') === $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Select an existing academic year to edit its defaults, or enter a new one below.</small>
                            </div>
                        </div>
                    </form>

                    <form action="{{ route('admin.admission-form-settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control @error('academic_year') is-invalid @enderror"
                                   id="academic_year" name="academic_year"
                                   value="{{ old('academic_year', $academicYear ?? $settings->academic_year) }}"
                                   placeholder="e.g., 2024/2025">
                            @error('academic_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="minimum_fee_percentage" class="form-label">Minimum Fee %</label>
                                <input type="number" step="0.01" min="0" max="100"
                                       class="form-control @error('minimum_fee_percentage') is-invalid @enderror"
                                       id="minimum_fee_percentage" name="minimum_fee_percentage"
                                       value="{{ old('minimum_fee_percentage', $settings->minimum_fee_percentage) }}">
                                @error('minimum_fee_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="balance_percentage" class="form-label">Balance %</label>
                                <input type="number" step="0.01" min="0" max="100"
                                       class="form-control @error('balance_percentage') is-invalid @enderror"
                                       id="balance_percentage" name="balance_percentage"
                                       value="{{ old('balance_percentage', $settings->balance_percentage) }}">
                                @error('balance_percentage')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="paid_fees_by_date" class="form-label">Paid Fees By Date</label>
                                <input type="date" class="form-control @error('paid_fees_by_date') is-invalid @enderror"
                                       id="paid_fees_by_date" name="paid_fees_by_date"
                                       value="{{ old('paid_fees_by_date', optional($settings->paid_fees_by_date)->format('Y-m-d')) }}">
                                @error('paid_fees_by_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="registration_begins" class="form-label">Registration Begins</label>
                                <input type="date" class="form-control @error('registration_begins') is-invalid @enderror"
                                       id="registration_begins" name="registration_begins"
                                       value="{{ old('registration_begins', optional($settings->registration_begins)->format('Y-m-d')) }}">
                                @error('registration_begins')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="orientation_new_students" class="form-label">Orientation for New Students</label>
                                <input type="date" class="form-control @error('orientation_new_students') is-invalid @enderror"
                                       id="orientation_new_students" name="orientation_new_students"
                                       value="{{ old('orientation_new_students', optional($settings->orientation_new_students)->format('Y-m-d')) }}">
                                @error('orientation_new_students')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="faculty_orientation" class="form-label">Faculty Orientation</label>
                                <input type="date" class="form-control @error('faculty_orientation') is-invalid @enderror"
                                       id="faculty_orientation" name="faculty_orientation"
                                       value="{{ old('faculty_orientation', optional($settings->faculty_orientation)->format('Y-m-d')) }}">
                                @error('faculty_orientation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="lectures_begin" class="form-label">Lectures Begin</label>
                                <input type="date" class="form-control @error('lectures_begin') is-invalid @enderror"
                                       id="lectures_begin" name="lectures_begin"
                                       value="{{ old('lectures_begin', optional($settings->lectures_begin)->format('Y-m-d')) }}">
                                @error('lectures_begin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back</a>
                            <button type="submit" class="btn btn-primary">Save Defaults</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

