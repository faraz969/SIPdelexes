@extends('layouts.app')

@section('title', 'Generate Invoice - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-file-invoice-dollar"></i> Generate Invoice</h2>
            <a href="{{ route('admin.erp.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to ERP Dashboard</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.erp.invoices.generate') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                        {{ $student->student_id }} - {{ $student->user->name }} ({{ $student->program->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="invoice_type" class="form-label">Invoice Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="invoice_type" name="invoice_type" required>
                                <option value="">Select Type</option>
                                <option value="tuition" {{ old('invoice_type') == 'tuition' ? 'selected' : '' }}>Tuition Fees</option>
                                <option value="late_registration" {{ old('invoice_type') == 'late_registration' ? 'selected' : '' }}>Late Registration Fee</option>
                                <option value="other" {{ old('invoice_type') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="academic_year" name="academic_year" value="{{ old('academic_year', $defaultAcademicYear ?? \App\Models\SiteSetting::currentAcademicYear()) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select" id="semester" name="semester">
                                    <option value="">Select Semester</option>
                                    <option value="First Semester" {{ old('semester') == 'First Semester' ? 'selected' : '' }}>First Semester</option>
                                    <option value="Second Semester" {{ old('semester') == 'Second Semester' ? 'selected' : '' }}>Second Semester</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="total_amount" class="form-label">Total Amount (GHS) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="total_amount" 
                                   name="total_amount" 
                                   step="0.01" 
                                   min="0" 
                                   value="{{ old('total_amount') }}" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="due_date" 
                                   name="due_date" 
                                   value="{{ old('due_date') }}" 
                                   required>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            This is a mock invoice generation. In production, invoices will be automatically generated by ERP.
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Generate Invoice
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

