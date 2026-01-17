@extends('layouts.app')

@section('title', 'Payments - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-credit-card"></i> Payments</h2>
            <a href="{{ route('admin.erp.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to ERP Dashboard</a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h6>Total Payments</h6>
                    <h4>{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6>Completed</h6>
                    <h4>{{ $stats['completed'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h6>Pending</h6>
                    <h4>{{ $stats['pending'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h6>Failed</h6>
                    <h4>{{ $stats['failed'] }}</h4>
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
                            <th>Reference</th>
                            <th>Student</th>
                            <th>Invoice #</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>ERP Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_reference }}</td>
                                <td>{{ $payment->student->user->name ?? 'N/A' }}<br><small>{{ $payment->student->student_id ?? '' }}</small></td>
                                <td>{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                <td><strong>GHS {{ number_format($payment->amount, 2) }}</strong></td>
                                <td><span class="badge bg-secondary">{{ ucfirst($payment->payment_method) }}</span></td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($payment->status === 'processing')
                                        <span class="badge bg-warning">Processing</span>
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
                                <td>
                                    @if($payment->status === 'processing' || $payment->status === 'pending')
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#processModal{{ $payment->id }}">
                                            <i class="fas fa-check"></i> Process
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            <!-- Process Payment Modal -->
                            @if($payment->status === 'processing' || $payment->status === 'pending')
                            <div class="modal fade" id="processModal{{ $payment->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.erp.payments.process', $payment->id) }}">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Process Payment</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Reference:</strong> {{ $payment->payment_reference }}</p>
                                                <p><strong>Amount:</strong> GHS {{ number_format($payment->amount, 2) }}</p>
                                                <p><strong>Student:</strong> {{ $payment->student->user->name ?? 'N/A' }}</p>
                                                
                                                <div class="mb-3">
                                                    <label for="status{{ $payment->id }}" class="form-label">Status</label>
                                                    <select class="form-select" id="status{{ $payment->id }}" name="status" required>
                                                        <option value="completed">Completed</option>
                                                        <option value="failed">Failed</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="erp_payment_id{{ $payment->id }}" class="form-label">ERP Payment ID (Optional)</label>
                                                    <input type="text" class="form-control" id="erp_payment_id{{ $payment->id }}" name="erp_payment_id" placeholder="Auto-generated if left blank">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Process Payment</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection

