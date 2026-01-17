@extends('layouts.app')

@section('title', 'Sync Invoice - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-sync"></i> Sync Invoice from ERP</h2>
            <a href="{{ route('admin.erp.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to ERP Dashboard</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sync Invoice</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.erp.invoices.sync') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                        {{ $student->student_id }} - {{ $student->user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="erp_invoice_id" class="form-label">ERP Invoice ID <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="erp_invoice_id" 
                                   name="erp_invoice_id" 
                                   value="{{ old('erp_invoice_id') }}" 
                                   placeholder="Enter ERP Invoice ID"
                                   required>
                            <small class="form-text text-muted">This would normally come from ERP system. For testing, enter any ID.</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            This is a mock sync. In production, invoices will be automatically synced from ERP via API.
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sync"></i> Sync Invoice
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

