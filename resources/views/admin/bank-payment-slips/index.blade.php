@extends('layouts.app')

@section('title', 'Bank Payment Slips')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Bank Payment Slips</h3>
        <small class="text-muted">Submitted from SIP invoice payments</small>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Submissions</div>
                    <div class="h4 mb-0">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-warning">
                <div class="card-body">
                    <div class="text-muted small">Pending Verification</div>
                    <div class="h4 mb-0 text-warning">{{ number_format($stats['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100 border-success">
                <div class="card-body">
                    <div class="text-muted small">Completed</div>
                    <div class="h4 mb-0 text-success">{{ number_format($stats['completed']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Pending Amount</div>
                    <div class="h4 mb-0">GHS {{ number_format((float) $stats['total_pending_amount'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.bank-payment-slips.index') }}" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                           placeholder="Search reference, student ID, name, email, or invoice #">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                </div>
                <div class="col-md-2 d-grid">
                    <a href="{{ route('admin.bank-payment-slips.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Submitted</th>
                            <th>Reference</th>
                            <th>Student</th>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>ERP</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slips as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d M Y H:i') }}</td>
                                <td><code>{{ $payment->payment_reference }}</code></td>
                                <td>
                                    <div class="fw-semibold">{{ optional($payment->student->user)->name ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ $payment->student->student_id ?? '-' }}</small>
                                </td>
                                <td>{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                <td><strong>GHS {{ number_format($payment->amount, 2) }}</strong></td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @elseif($payment->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($payment->erp_status === 'synced')
                                        <span class="badge bg-success">Synced</span>
                                    @elseif($payment->erp_status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.bank-payment-slips.show', $payment) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="{{ route('admin.bank-payment-slips.slip', $payment) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                        <i class="fas fa-file"></i> Slip
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No bank payment slips submitted yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-transparent">
            {{ $slips->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
