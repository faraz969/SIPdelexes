@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Academic Settings</h4>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.academic-year-settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <h5 class="mb-3">Academic Year</h5>
                        <p class="text-muted small mb-3">
                            Used for new admission applications, bank voucher receipts, ERP defaults, and other intake-year displays.
                        </p>
                        <div class="mb-4">
                            <label for="academic_year" class="form-label">Current academic year</label>
                            <input type="text" class="form-control @error('academic_year') is-invalid @enderror"
                                   id="academic_year" name="academic_year"
                                   value="{{ old('academic_year', $academic_year) }}"
                                   placeholder="e.g. 2025/2026 or 2026-2027" required>
                            @error('academic_year')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <h5 class="mb-3">Official Transcript Fee</h5>
                        <p class="text-muted small mb-3">
                            Amount charged when a student requests an official transcript. After payment, Registrar must approve before the student can view it. Set to 0 to skip payment and send requests straight to Registrar.
                        </p>
                        <div class="mb-4">
                            <label for="transcript_fee" class="form-label">Transcript fee (GHS)</label>
                            <input type="number" step="0.01" min="0"
                                   class="form-control @error('transcript_fee') is-invalid @enderror"
                                   id="transcript_fee" name="transcript_fee"
                                   value="{{ old('transcript_fee', number_format((float) $transcript_fee, 2, '.', '')) }}"
                                   required>
                            @error('transcript_fee')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
