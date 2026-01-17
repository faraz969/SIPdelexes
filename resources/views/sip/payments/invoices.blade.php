@extends('layouts.app')

@section('title', 'Invoices - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-file-invoice-dollar"></i> Invoices</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5>Total Balance</h5>
                    <h3>GHS {{ number_format($student->getTotalBalance(), 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5>Total Paid</h5>
                    <h3>GHS {{ number_format($student->getTotalPaid(), 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5>Payment %</h5>
                    <h3>{{ number_format($student->getPaymentPercentage(), 1) }}%</h3>
                </div>
            </div>
        </div>
    </div>

    @if($invoices->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No invoices available yet.
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Invoices</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Type</th>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Total Amount</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->invoice_number }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}</span></td>
                                    <td>{{ $invoice->academic_year }}</td>
                                    <td>{{ $invoice->semester ?? 'N/A' }}</td>
                                    <td>GHS {{ number_format($invoice->total_amount, 2) }}</td>
                                    <td>GHS {{ number_format($invoice->paid_amount, 2) }}</td>
                                    <td>
                                        <strong class="text-{{ $invoice->balance > 0 ? 'danger' : 'success' }}">
                                            GHS {{ number_format($invoice->balance, 2) }}
                                        </strong>
                                    </td>
                                    <td>
                                        @if($invoice->status === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($invoice->status === 'partial')
                                            <span class="badge bg-warning">Partial</span>
                                        @else
                                            <span class="badge bg-danger">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ $invoice->due_date->format('d M Y') }}</td>
                                    <td>
                                        @if($invoice->balance > 0)
                                            <a href="{{ route('sip.payments.pay', $invoice->id) }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-credit-card"></i> Pay
                                            </a>
                                        @else
                                            <span class="text-muted">Paid</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('sip.payments.history') }}" class="btn btn-info">
            <i class="fas fa-history"></i> View Payment History
        </a>
    </div>
</div>
@endsection

