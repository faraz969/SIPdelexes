@extends('layouts.app')

@section('title', 'Bank Payment Slip - ' . $payment->payment_reference)

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Bank Payment Slip</h3>
        <a href="{{ route('admin.bank-payment-slips.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><strong>Payment Details</strong></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>Reference</th>
                            <td><code>{{ $payment->payment_reference }}</code></td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td><strong>GHS {{ number_format($payment->amount, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Submitted</th>
                            <td>{{ $payment->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($payment->status === 'completed')
                                    <span class="badge bg-success">Completed</span>
                                @elseif($payment->status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending Verification</span>
                                @elseif($payment->status === 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>ERP Status</th>
                            <td>
                                @if($payment->erp_status === 'synced')
                                    <span class="badge bg-success">Synced</span>
                                @elseif($payment->erp_status === 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @if($payment->erp_payment_id)
                        <tr>
                            <th>ERP Payment ID</th>
                            <td><code>{{ $payment->erp_payment_id }}</code></td>
                        </tr>
                        @endif
                        @if(!empty($payment->payment_details['original_filename']))
                        <tr>
                            <th>Original File</th>
                            <td>{{ $payment->payment_details['original_filename'] }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>Student</strong></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Name:</strong> {{ optional($payment->student->user)->name ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Student ID:</strong> {{ $payment->student->student_id ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Email:</strong> {{ optional($payment->student->user)->email ?? 'N/A' }}</p>
                    <p class="mb-0"><strong>Phone:</strong> {{ optional($payment->student->user)->phone ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><strong>Invoice</strong></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Invoice #:</strong> {{ $payment->invoice->invoice_number ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Type:</strong> {{ $payment->invoice ? ucfirst(str_replace('_', ' ', $payment->invoice->invoice_type)) : 'N/A' }}</p>
                    <p class="mb-1"><strong>Academic Year:</strong> {{ $payment->invoice->academic_year ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Total:</strong> GHS {{ $payment->invoice ? number_format($payment->invoice->total_amount, 2) : '0.00' }}</p>
                    <p class="mb-0"><strong>Balance:</strong> GHS {{ $payment->invoice ? number_format($payment->invoice->balance, 2) : '0.00' }}</p>
                    @if(!empty($payment->invoice->erp_invoice_id))
                        <p class="mb-0 mt-2"><strong>ERP Invoice:</strong> <code>{{ $payment->invoice->erp_invoice_id }}</code></p>
                    @endif
                </div>
            </div>

            @if($payment->status === 'pending' || $payment->status === 'processing')
            <div class="card">
                <div class="card-header"><strong>Process Payment</strong></div>
                <div class="card-body">
                    <p class="text-muted small">Mark as completed after verifying the slip and updating ERP.</p>
                    <form method="POST" action="{{ route('admin.erp.payments.process', $payment->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="erp_payment_id" class="form-label">ERP Payment ID (optional)</label>
                            <input type="text" class="form-control" id="erp_payment_id" name="erp_payment_id" placeholder="Auto-generated if left blank">
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Update Payment
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Uploaded Slip</strong>
                    <a href="{{ route('admin.bank-payment-slips.slip', $payment) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Open Full Size
                    </a>
                </div>
                <div class="card-body text-center">
                    @php
                        $mime = $payment->payment_details['mime_type'] ?? '';
                        $isImage = strpos($mime, 'image/') === 0 || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $payment->bank_slip_path);
                        $isPdf = strpos($mime, 'pdf') !== false || preg_match('/\.pdf$/i', $payment->bank_slip_path);
                    @endphp

                    @if($isImage)
                        <img src="{{ route('admin.bank-payment-slips.slip', $payment) }}" alt="Bank payment slip" class="img-fluid border rounded" style="max-height: 700px;">
                    @elseif($isPdf)
                        <iframe src="{{ route('admin.bank-payment-slips.slip', $payment) }}" title="Bank payment slip" style="width: 100%; height: 700px; border: 1px solid #ddd; border-radius: 4px;"></iframe>
                    @else
                        <div class="py-5">
                            <i class="fas fa-file fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Preview not available for this file type.</p>
                            <a href="{{ route('admin.bank-payment-slips.slip', $payment) }}" class="btn btn-primary" target="_blank">
                                <i class="fas fa-download"></i> Download Slip
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
