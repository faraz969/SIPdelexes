@extends('layouts.app')

@section('title', 'Payment History - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-history"></i> Payment History</h2>
            <a href="{{ route('sip.payments.invoices') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Invoices</a>
        </div>
    </div>

    @if($payments->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No payment history available yet.
        </div>
    @else
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Payment Reference</th>
                                <th>Invoice #</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>ERP Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_reference }}</td>
                                    <td>{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                    <td><strong>GHS {{ number_format($payment->amount, 2) }}</strong></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $payment->payment_method === 'bank' ? 'Bank Slip' : ucfirst($payment->payment_method) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($payment->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($payment->status === 'processing')
                                            <span class="badge bg-warning">Processing</span>
                                        @elseif($payment->status === 'pending')
                                            <span class="badge bg-info">Pending Verification</span>
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
                                            <span class="badge bg-warning">Pending</span>
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
@endsection

