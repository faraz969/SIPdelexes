@extends('layouts.app')

@section('title', 'Deferment Request - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-pause-circle"></i> Deferment Request</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    @if($activeDeferment)
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            You have an active deferment request.
            <a href="{{ route('sip.deferment.status') }}" class="btn btn-sm btn-info">View Status</a>
        </div>
    @else
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Submit Deferment Request</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sip.deferment.submit') }}">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Deferment <span class="text-danger">*</span></label>
                                <textarea class="form-control" 
                                          id="reason" 
                                          name="reason" 
                                          rows="5" 
                                          minlength="20" 
                                          maxlength="1000" 
                                          required 
                                          placeholder="Please provide a detailed reason for your deferment request (minimum 20 characters)">{{ old('reason') }}</textarea>
                                <small class="form-text text-muted">Minimum 20 characters required</small>
                                @error('reason')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="defer_from" class="form-label">Defer From Date <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control" 
                                       id="defer_from" 
                                       name="defer_from" 
                                       value="{{ old('defer_from') }}" 
                                       min="{{ date('Y-m-d') }}"
                                       required>
                                @error('defer_from')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="defer_to" class="form-label">Defer To Date (Optional)</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="defer_to" 
                                       name="defer_to" 
                                       value="{{ old('defer_to') }}">
                                <small class="form-text text-muted">Leave blank if return date is uncertain</small>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Important:</strong> Once your deferment is approved:
                                <ul class="mb-0 mt-2">
                                    <li>Course registration will be frozen</li>
                                    <li>Exam PIN generation will be disabled</li>
                                    <li>ERP invoicing will be suspended</li>
                                </ul>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Deferment Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Student Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Student ID:</strong> {{ $student->student_id }}</p>
                        <p><strong>Name:</strong> {{ $student->user->name }}</p>
                        <p><strong>Program:</strong> {{ $student->program->name ?? 'N/A' }}</p>
                        <p><strong>Academic Status:</strong> 
                            <span class="badge bg-{{ $student->academic_status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($student->academic_status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

