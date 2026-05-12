@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Academic Year</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">
                        This value is used for new admission applications, bank voucher receipts, ERP defaults when no term is configured, and other places that need the current intake year.
                    </p>
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.academic-year-settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Current academic year</label>
                            <input type="text" class="form-control @error('academic_year') is-invalid @enderror"
                                   id="academic_year" name="academic_year"
                                   value="{{ old('academic_year', $academic_year) }}"
                                   placeholder="e.g. 2025/2026 or 2026-2027" required>
                            @error('academic_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Use the same format you want shown on applications and receipts.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
