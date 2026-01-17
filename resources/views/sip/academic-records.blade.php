@extends('layouts.app')

@section('title', 'Academic Records - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-book-reader"></i> Academic Records</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    @if($records->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No academic records available yet.
        </div>
    @else
        @foreach($records as $record)
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar"></i> {{ $record->semester }} - {{ $record->academic_year }}
                        @if($record->is_approved)
                            <span class="badge bg-success float-end">Approved</span>
                        @else
                            <span class="badge bg-warning float-end">Pending Approval</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6><i class="fas fa-book"></i> Registered Courses</h6>
                            @if($record->registered_courses && count($record->registered_courses) > 0)
                                <ul class="list-group">
                                    @foreach($record->registered_courses as $course)
                                        <li class="list-group-item">{{ $course['name'] ?? $course['code'] ?? 'N/A' }} - {{ $course['credits'] ?? 'N/A' }} Credits</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">No courses registered</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-chart-line"></i> Results</h6>
                            @if($record->gpa)
                                <div class="alert alert-success">
                                    <strong>GPA:</strong> {{ number_format($record->gpa, 2) }}
                                </div>
                            @endif
                            @if($record->results && count($record->results) > 0)
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Grade</th>
                                            <th>Credits</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($record->results as $result)
                                            <tr>
                                                <td>{{ $result['course'] ?? 'N/A' }}</td>
                                                <td><span class="badge bg-primary">{{ $result['grade'] ?? 'N/A' }}</span></td>
                                                <td>{{ $result['credits'] ?? 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted">No results available</p>
                            @endif
                        </div>
                    </div>
                    
                    @if($record->resits_history && count($record->resits_history) > 0)
                        <div class="row">
                            <div class="col-12">
                                <h6><i class="fas fa-redo"></i> Resits History</h6>
                                <ul class="list-group">
                                    @foreach($record->resits_history as $resit)
                                        <li class="list-group-item">
                                            {{ $resit['course'] ?? 'N/A' }} - 
                                            Previous: {{ $resit['previous_grade'] ?? 'N/A' }}, 
                                            New: {{ $resit['new_grade'] ?? 'N/A' }}
                                            <small class="text-muted">({{ $resit['date'] ?? 'N/A' }})</small>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

