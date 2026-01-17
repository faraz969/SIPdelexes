@extends('layouts.app')

@section('title', 'Student Details - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-user-graduate"></i> Student Details</h2>
            <a href="{{ route('admin.erp.students') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Students</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Student Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Student ID:</strong> {{ $student->student_id }}</p>
                    <p><strong>Name:</strong> {{ $student->user->name }}</p>
                    <p><strong>Email:</strong> {{ $student->user->email }}</p>
                    <p><strong>Phone:</strong> {{ $student->user->phone ?? 'N/A' }}</p>
                    <p><strong>Program:</strong> {{ $student->program->name ?? 'N/A' }}</p>
                    <p><strong>Department:</strong> {{ $student->department->name ?? 'N/A' }}</p>
                    <p><strong>Academic Year:</strong> {{ $student->academic_year }}</p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-{{ $student->academic_status === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($student->academic_status) }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Financial Summary</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total Balance:</strong> 
                        <span class="text-danger">GHS {{ number_format($student->getTotalBalance(), 2) }}</span>
                    </p>
                    <p><strong>Total Paid:</strong> 
                        <span class="text-success">GHS {{ number_format($student->getTotalPaid(), 2) }}</span>
                    </p>
                    <p><strong>Payment %:</strong> {{ number_format($student->getPaymentPercentage(), 1) }}%</p>
                    <p><strong>Can Register:</strong> 
                        <span class="badge bg-{{ $student->canRegisterForCourses() ? 'success' : 'danger' }}">
                            {{ $student->canRegisterForCourses() ? 'Yes' : 'No' }}
                        </span>
                    </p>
                    <p><strong>Can Generate Exam PIN:</strong> 
                        <span class="badge bg-{{ $student->canGenerateExamPin() ? 'success' : 'danger' }}">
                            {{ $student->canGenerateExamPin() ? 'Yes' : 'No' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Invoices -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Invoices ({{ $student->invoices->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($student->invoices->isEmpty())
                        <p class="text-muted">No invoices</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($student->invoices as $invoice)
                                        <tr>
                                            <td><a href="{{ route('admin.erp.invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type)) }}</td>
                                            <td>GHS {{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>GHS {{ number_format($invoice->balance, 2) }}</td>
                                            <td>
                                                @if($invoice->status === 'paid')
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif($invoice->status === 'partial')
                                                    <span class="badge bg-warning">Partial</span>
                                                @else
                                                    <span class="badge bg-danger">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payments -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Payments ({{ $student->payments->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($student->payments->isEmpty())
                        <p class="text-muted">No payments</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($student->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_reference }}</td>
                                            <td>GHS {{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ ucfirst($payment->payment_method) }}</td>
                                            <td>
                                                @if($payment->status === 'completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $payment->created_at->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Course Registrations -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Course Registrations ({{ $student->courseRegistrations->count() }})</h5>
                </div>
                <div class="card-body">
                    @if($student->courseRegistrations->isEmpty())
                        <p class="text-muted">No course registrations</p>
                    @else
                        @foreach($student->courseRegistrations as $registration)
                            <div class="mb-2">
                                <strong>{{ $registration->semester }} - {{ $registration->academic_year }}</strong>
                                <span class="badge bg-{{ $registration->status === 'registered' ? 'success' : 'warning' }}">
                                    {{ ucfirst($registration->status) }}
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

