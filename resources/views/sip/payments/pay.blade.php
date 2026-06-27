@extends('layouts.app')

@section('title', 'Make Payment - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-credit-card"></i> Make Payment</h2>
            <a href="{{ route('sip.payments.invoices') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Invoices</a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Details</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Invoice Number:</th>
                            <td>{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <th>Type:</th>
                            <td>{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}</td>
                        </tr>
                        <tr>
                            <th>Academic Year:</th>
                            <td>{{ $invoice->academic_year }}</td>
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
                            <td><strong class="text-danger">GHS {{ number_format($invoice->balance, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Due Date:</th>
                            <td>{{ $invoice->due_date->format('d M Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Payment Form</h5>
                </div>
                <div class="card-body">
                    @if(!$paystackConfigured)
                        <div class="alert alert-warning mb-0">
                            Paystack is not configured. Please contact the finance office to make a payment.
                        </div>
                    @else
                        <form method="POST" action="{{ route('sip.payments.process', $invoice->id) }}">
                            @csrf

                            <div class="mb-3">
                                <label for="amount" class="form-label">Payment Amount</label>
                                <input type="number"
                                       class="form-control @error('amount') is-invalid @enderror"
                                       id="amount"
                                       name="amount"
                                       step="0.01"
                                       min="0.01"
                                       max="{{ $invoice->balance }}"
                                       value="{{ old('amount', $invoice->balance) }}"
                                       required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">Maximum: GHS {{ number_format($invoice->balance, 2) }}</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <div class="border rounded p-3 bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_paystack" value="paystack" checked required>
                                        <label class="form-check-label fw-semibold" for="payment_paystack">
                                            <i class="fas fa-credit-card me-1"></i> Paystack
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Pay securely with card, mobile money, or bank via Paystack.
                                    </small>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> You will be redirected to Paystack to complete your payment.
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-lock"></i> Pay with Paystack
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Payment Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Student ID:</strong> {{ $student->student_id }}</p>
                    <p><strong>Name:</strong> {{ $student->user->name }}</p>
                    <hr>
                    <p class="text-muted">
                        <small>
                            You can make partial or full payments.
                            Course registration will be enabled once you reach the minimum payment percentage.
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
