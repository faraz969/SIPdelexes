@extends('layouts.app')

@section('title', 'ERP Dashboard - Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-cogs"></i> ERP Integration Dashboard</h2>
            <p class="text-muted">Mock ERP functionalities for testing before ERP integration</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5>Total Students</h5>
                    <h3>{{ $stats['total_students'] }}</h3>
                    <small>Active: {{ $stats['active_students'] }} | Deferred: {{ $stats['deferred_students'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5>Total Invoices</h5>
                    <h3>{{ $stats['total_invoices'] }}</h3>
                    <small>Pending: {{ $stats['pending_invoices'] }} | Unpaid: {{ $stats['unpaid_invoices'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5>Total Payments</h5>
                    <h3>{{ $stats['total_payments'] }}</h3>
                    <small>Pending Sync: {{ $stats['pending_payments'] }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5>Total Revenue</h5>
                    <h3>GHS {{ number_format($stats['total_revenue'], 2) }}</h3>
                    <small>Completed payments</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.erp.invoices.generate-form') }}" class="btn btn-primary me-2">
                        <i class="fas fa-plus"></i> Generate Invoice
                    </a>
                    <a href="{{ route('admin.erp.invoices.sync-form') }}" class="btn btn-info me-2">
                        <i class="fas fa-sync"></i> Sync Invoice from ERP
                    </a>
                    <a href="{{ route('admin.erp.students') }}" class="btn btn-secondary me-2">
                        <i class="fas fa-users"></i> View Students
                    </a>
                    <a href="{{ route('admin.erp.invoices') }}" class="btn btn-secondary me-2">
                        <i class="fas fa-file-invoice"></i> View Invoices
                    </a>
                    <a href="{{ route('admin.erp.payments') }}" class="btn btn-secondary me-2">
                        <i class="fas fa-credit-card"></i> View Payments
                    </a>
                    <a href="{{ route('admin.erp.activity-logs') }}" class="btn btn-secondary">
                        <i class="fas fa-history"></i> Activity Logs
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Invoices</h5>
                </div>
                <div class="card-body">
                    @if($recentInvoices->isEmpty())
                        <p class="text-muted">No invoices yet</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentInvoices as $invoice)
                                        <tr>
                                            <td><a href="{{ route('admin.erp.invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                            <td>{{ $invoice->student->user->name ?? 'N/A' }}</td>
                                            <td>GHS {{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>
                                                @if($invoice->status === 'paid')
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif($invoice->status === 'partial')
                                                    <span class="badge bg-warning">Partial</span>
                                                @else
                                                    <span class="badge bg-danger">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Payments</h5>
                </div>
                <div class="card-body">
                    @if($recentPayments->isEmpty())
                        <p class="text-muted">No payments yet</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentPayments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_reference }}</td>
                                            <td>{{ $payment->student->user->name ?? 'N/A' }}</td>
                                            <td>GHS {{ number_format($payment->amount, 2) }}</td>
                                            <td>
                                                @if($payment->status === 'completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @elseif($payment->status === 'processing')
                                                    <span class="badge bg-warning">Processing</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

