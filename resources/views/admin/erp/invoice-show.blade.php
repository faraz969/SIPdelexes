@extends('layouts.app')

@section('title', 'Invoice Details - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-file-invoice"></i> Invoice Details</h2>
            <a href="{{ route('admin.erp.invoices') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Invoices</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Invoice #{{ $invoice->invoice_number }}</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Invoice Number:</th>
                            <td>{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <th>ERP Invoice ID:</th>
                            <td>{{ $invoice->erp_invoice_id ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}</span></td>
                        </tr>
                        <tr>
                            <th>Student:</th>
                            <td>{{ $invoice->student->user->name ?? 'N/A' }} ({{ $invoice->student->student_id ?? 'N/A' }})</td>
                        </tr>
                        <tr>
                            <th>Academic Year:</th>
                            <td>{{ $invoice->academic_year }}</td>
                        </tr>
                        <tr>
                            <th>Semester:</th>
                            <td>{{ $invoice->semester ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Total Amount:</th>
                            <td><strong>GHS {{ number_format($invoice->total_amount, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Paid Amount:</th>
                            <td>GHS {{ number_format($invoice->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Balance:</th>
                            <td><strong class="text-{{ $invoice->balance > 0 ? 'danger' : 'success' }}">GHS {{ number_format($invoice->balance, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
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
                        <tr>
                            <th>Due Date:</th>
                            <td>{{ $invoice->due_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th>Issued Date:</th>
                            <td>{{ $invoice->issued_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th>Synced from ERP:</th>
                            <td>{{ $invoice->synced_from_erp ? 'Yes' : 'No' }}</td>
                        </tr>
                    </table>

                    @if($invoice->line_items && count($invoice->line_items) > 0)
                        <h6>Line Items:</h6>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->line_items as $item)
                                    <tr>
                                        <td>{{ $item['description'] ?? 'N/A' }}</td>
                                        <td>GHS {{ number_format($item['amount'] ?? 0, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            @if($invoice->payments->count() > 0)
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Payment History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_reference }}</td>
                                            <td>GHS {{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ ucfirst($payment->payment_method) }}</td>
                                            <td>
                                                @if($payment->status === 'completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $payment->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

