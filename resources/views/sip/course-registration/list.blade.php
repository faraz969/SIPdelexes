@extends('layouts.app')

@section('title', 'Registered Courses - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-list"></i> Registered Courses</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    @if($registrations->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No course registrations found.
            <a href="{{ route('sip.course-registration.show') }}" class="btn btn-sm btn-primary">Register Now</a>
        </div>
    @else
        @foreach($registrations as $registration)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        {{ $registration->semester }} - {{ $registration->academic_year }}
                        @if($registration->status === 'registered')
                            <span class="badge bg-success float-end">Registered</span>
                        @elseif($registration->status === 'late')
                            <span class="badge bg-warning float-end">Late Registration</span>
                        @else
                            <span class="badge bg-secondary float-end">{{ ucfirst($registration->status) }}</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if($registration->is_late_registration && $registration->late_fee > 0)
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Late Registration Fee: <strong>GHS {{ number_format($registration->late_fee, 2) }}</strong>
                        </div>
                    @endif

                    <h6>Registered Courses:</h6>
                    @if($registration->courses && count($registration->courses) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Course Code</th>
                                        <th>Course Name</th>
                                        <th>Credits</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($registration->courses as $course)
                                        <tr>
                                            <td>{{ $course['code'] ?? 'N/A' }}</td>
                                            <td>{{ $course['name'] ?? 'N/A' }}</td>
                                            <td>{{ $course['credits'] ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No courses registered</p>
                    @endif

                    <small class="text-muted">
                        Registered on: {{ $registration->registered_at->format('d M Y H:i') }}
                    </small>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

