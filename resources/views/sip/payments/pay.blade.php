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
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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
                            <td>{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Payment Form</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sip.payments.process', $invoice->id) }}" enctype="multipart/form-data" id="paymentForm">
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
                                @if($paystackConfigured)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_paystack" value="paystack"
                                           {{ old('payment_method', 'paystack') === 'paystack' ? 'checked' : '' }} required>
                                    <label class="form-check-label fw-semibold" for="payment_paystack">
                                        <i class="fas fa-credit-card me-1"></i> Paystack
                                    </label>
                                    <small class="text-muted d-block ms-4">
                                        Pay securely with card, mobile money, or bank via Paystack.
                                    </small>
                                </div>
                                @endif

                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_bank" value="bank"
                                           {{ old('payment_method', $paystackConfigured ? '' : 'bank') === 'bank' ? 'checked' : '' }}
                                           {{ !$paystackConfigured ? 'checked' : '' }}
                                           required>
                                    <label class="form-check-label fw-semibold" for="payment_bank">
                                        <i class="fas fa-university me-1"></i> Bank Transfer / Payment Slip
                                    </label>
                                    <small class="text-muted d-block ms-4">
                                        Pay at the bank, then scan/upload your payment slip. Accounts will be notified to update ERP.
                                    </small>
                                </div>
                            </div>
                            @error('payment_method')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="bankSlipSection" class="mb-3" style="display: none;">
                            <label for="bank_slip" class="form-label">Upload Bank Payment Slip <span class="text-danger">*</span></label>
                            <input type="file"
                                   class="form-control @error('bank_slip') is-invalid @enderror"
                                   id="bank_slip"
                                   name="bank_slip"
                                   accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                            @error('bank_slip')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Accepted formats: JPG, PNG, PDF. Max size: 5MB.</small>

                            <div class="alert alert-info mt-3 mb-0">
                                <i class="fas fa-info-circle"></i>
                                <strong>Note:</strong> After you submit, an email with your slip will be sent to the accounts department so they can update ERP. Your payment will show as <strong>Pending</strong> until accounts verifies it.
                            </div>
                        </div>

                        <div id="paystackNote" class="alert alert-info" style="{{ $paystackConfigured ? '' : 'display:none;' }}">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> You will be redirected to Paystack to complete your payment.
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg" id="submitPaymentBtn">
                            <i class="fas fa-lock"></i> <span id="submitPaymentLabel">{{ $paystackConfigured ? 'Pay with Paystack' : 'Submit Payment Slip' }}</span>
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

@push('scripts')
<script>
    (function () {
        const paystackRadio = document.getElementById('payment_paystack');
        const bankRadio = document.getElementById('payment_bank');
        const bankSlipSection = document.getElementById('bankSlipSection');
        const bankSlipInput = document.getElementById('bank_slip');
        const paystackNote = document.getElementById('paystackNote');
        const submitLabel = document.getElementById('submitPaymentLabel');
        const submitBtn = document.getElementById('submitPaymentBtn');

        function syncPaymentMethodUI() {
            const isBank = bankRadio && bankRadio.checked;

            if (bankSlipSection) {
                bankSlipSection.style.display = isBank ? 'block' : 'none';
            }
            if (bankSlipInput) {
                bankSlipInput.required = !!isBank;
                if (!isBank) {
                    bankSlipInput.value = '';
                }
            }
            if (paystackNote) {
                paystackNote.style.display = (!isBank && paystackRadio) ? 'block' : 'none';
            }
            if (submitLabel) {
                submitLabel.textContent = isBank ? 'Submit Payment Slip' : 'Pay with Paystack';
            }
            if (submitBtn) {
                const icon = submitBtn.querySelector('i');
                if (icon) {
                    icon.className = isBank ? 'fas fa-upload' : 'fas fa-lock';
                }
            }
        }

        if (paystackRadio) paystackRadio.addEventListener('change', syncPaymentMethodUI);
        if (bankRadio) bankRadio.addEventListener('change', syncPaymentMethodUI);
        syncPaymentMethodUI();
    })();
</script>
@endpush
