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
                    <form method="POST" action="{{ route('sip.payments.process', $invoice->id) }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="amount" class="form-label">Payment Amount</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="amount" 
                                   name="amount" 
                                   step="0.01" 
                                   min="0.01" 
                                   max="{{ $invoice->balance }}" 
                                   value="{{ $invoice->balance }}"
                                   required>
                            <small class="form-text text-muted">Maximum: GHS {{ number_format($invoice->balance, 2) }}</small>
                        </div>

                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="card">Credit/Debit Card</option>
                                <option value="momo">Mobile Money (MoMo)</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Note:</strong> Payment will be processed through the payment gateway. 
                            You will be redirected to complete the payment.
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-credit-card"></i> Proceed to Payment
                        </button>
                    </form>
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

