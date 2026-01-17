@extends('layouts.app')

@section('title', 'Invoices - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-file-invoice"></i> Invoices</h2>
            <a href="{{ route('admin.erp.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to ERP Dashboard</a>
            <a href="{{ route('admin.erp.invoices.generate-form') }}" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Generate Invoice</a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h6>Total Invoices</h6>
                    <h4>{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h6>Pending</h6>
                    <h4>{{ $stats['pending'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h6>Partial</h6>
                    <h4>{{ $stats['partial'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6>Paid</h6>
                    <h4>{{ $stats['paid'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Student</th>
                            <th>Type</th>
                            <th>Academic Year</th>
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
                                <td><a href="{{ route('admin.erp.invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ $invoice->student->user->name ?? 'N/A' }}<br><small>{{ $invoice->student->student_id ?? '' }}</small></td>
                                <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}</span></td>
                                <td>{{ $invoice->academic_year }}</td>
                                <td>GHS {{ number_format($invoice->total_amount, 2) }}</td>
                                <td>GHS {{ number_format($invoice->paid_amount, 2) }}</td>
                                <td><strong class="text-{{ $invoice->balance > 0 ? 'danger' : 'success' }}">GHS {{ number_format($invoice->balance, 2) }}</strong></td>
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
                                    <a href="{{ route('admin.erp.invoices.show', $invoice->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection

