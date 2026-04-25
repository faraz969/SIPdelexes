@extends('layouts.app')

@section('title', 'Admission Payments')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-receipt me-2"></i>Admission Form Payments</h3>
        <small class="text-muted">Signup payment records only</small>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Records</div>
                    <div class="h4 mb-0">{{ number_format($stats['total_records']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">With Invoice ID</div>
                    <div class="h4 mb-0">{{ number_format($stats['with_invoice']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Captured Amount</div>
                    <div class="h4 mb-0">GHS {{ number_format((float) $stats['total_amount'], 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Today Records</div>
                    <div class="h4 mb-0">{{ number_format($stats['today_records']) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.admission-payments.index') }}" class="row g-2">
                <div class="col-md-10">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="{{ request('search') }}"
                        placeholder="Search by name, email, phone, serial number, or invoice ID">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Search</button>
                </div>
            </form>
            @if(request('search'))
                <div class="mt-2">
                    <a href="{{ route('admin.admission-payments.index') }}" class="btn btn-sm btn-outline-secondary">Clear Search</a>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Applicant</th>
                            <th>Contact</th>
                            <th>Form Type</th>
                            <th>Invoice ID</th>
                            <th>Amount</th>
                            <th>Serial Number</th>
                            <th>PIN Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $record)
                            <tr>
                                <td>{{ optional($record->created_at)->format('d M Y H:i') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $record->name }}</div>
                                    <small class="text-muted">ID: {{ $record->id }}</small>
                                </td>
                                <td>
                                    <div>{{ $record->email }}</div>
                                    <small class="text-muted">{{ $record->phone ?: '-' }}</small>
                                </td>
                                <td>{{ optional($record->formType)->name ?? '-' }}</td>
                                <td><code>{{ $record->invoice_id ?? '-' }}</code></td>
                                <td>
                                    @if(!is_null($record->payment))
                                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                                            GHS {{ number_format((float) $record->payment, 2) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><code>{{ $record->serial_number ?? '-' }}</code></td>
                                <td>{{ optional($record->pin_expires_at)->format('d M Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No admission payment records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-transparent">
            {{ $payments->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
