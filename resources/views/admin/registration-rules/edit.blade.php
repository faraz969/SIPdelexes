@extends('layouts.app')

@section('title', 'Edit Registration Rule - Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-edit"></i> Edit Registration Rule</h2>
            <a href="{{ route('admin.registration-rules.index') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Rule Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.registration-rules.update', $registrationRule->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="rule_name" class="form-label">Rule Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="rule_name" name="rule_name" value="{{ old('rule_name', $registrationRule->rule_name) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="minimum_payment_percentage" class="form-label">Minimum Payment Percentage <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="minimum_payment_percentage" 
                                   name="minimum_payment_percentage" 
                                   step="0.01" 
                                   min="0" 
                                   max="100" 
                                   value="{{ old('minimum_payment_percentage', $registrationRule->minimum_payment_percentage) }}" 
                                   required>
                            <small class="form-text text-muted">Students must pay at least this percentage to register for courses</small>
                        </div>

                        <div class="mb-3">
                            <label for="late_registration_fee" class="form-label">Late Registration Fee (GHS) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="late_registration_fee" 
                                   name="late_registration_fee" 
                                   step="0.01" 
                                   min="0" 
                                   value="{{ old('late_registration_fee', $registrationRule->late_registration_fee) }}" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="late_registration_days" class="form-label">Late Registration Days <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="late_registration_days" 
                                   name="late_registration_days" 
                                   min="1" 
                                   value="{{ old('late_registration_days', $registrationRule->late_registration_days) }}" 
                                   required>
                            <small class="form-text text-muted">Days after registration period to apply late fee</small>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $registrationRule->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Activate this rule
                            </label>
                            <small class="form-text text-muted d-block">Only one rule can be active at a time.</small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Update Rule
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

