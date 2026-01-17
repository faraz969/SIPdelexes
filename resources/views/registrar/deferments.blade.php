@extends('layouts.app')

@section('title', 'Deferment Management - Registrar')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-pause-circle"></i> Deferment Management</h2>
            <a href="{{ route('registrar.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    @if($pendingDeferments->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">Pending Deferment Requests ({{ $pendingDeferments->count() }})</h5>
            </div>
            <div class="card-body">
                @foreach($pendingDeferments as $deferment)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>{{ $deferment->student->user->name ?? 'N/A' }} 
                                        <small class="text-muted">({{ $deferment->student->student_id ?? 'N/A' }})</small>
                                    </h5>
                                    <p><strong>Program:</strong> {{ $deferment->student->program->name ?? 'N/A' }}</p>
                                    <p><strong>Reason:</strong> {{ $deferment->reason }}</p>
                                    <p><strong>Defer From:</strong> {{ $deferment->defer_from->format('d M Y') }}</p>
                                    @if($deferment->defer_to)
                                        <p><strong>Defer To:</strong> {{ $deferment->defer_to->format('d M Y') }}</p>
                                    @endif
                                    <p><small class="text-muted">Submitted: {{ $deferment->created_at->format('d M Y H:i') }}</small></p>
                                </div>
                                <div class="col-md-4">
                                    <form method="POST" action="{{ route('registrar.deferments.approve', $deferment->id) }}" class="mb-2">
                                        @csrf
                                        <div class="mb-2">
                                            <textarea name="comments" class="form-control" rows="3" placeholder="Comments (optional)"></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('registrar.deferments.reject', $deferment->id) }}">
                                        @csrf
                                        <div class="mb-2">
                                            <textarea name="comments" class="form-control" rows="3" placeholder="Rejection reason (required)" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-danger btn-sm w-100">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Deferments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Reason</th>
                            <th>Defer From</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allDeferments as $deferment)
                            <tr>
                                <td>{{ $deferment->student->user->name ?? 'N/A' }}</td>
                                <td>{{ $deferment->student->student_id ?? 'N/A' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($deferment->reason, 50) }}</td>
                                <td>{{ $deferment->defer_from->format('d M Y') }}</td>
                                <td>
                                    @if($deferment->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($deferment->status === 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($deferment->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @elseif($deferment->status === 'reactivated')
                                        <span class="badge bg-info">Reactivated</span>
                                    @endif
                                </td>
                                <td>
                                    @if($deferment->status === 'approved' && $deferment->student->academic_status === 'deferred')
                                        <form method="POST" action="{{ route('registrar.deferments.reactivate', $deferment->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-info">
                                                <i class="fas fa-play"></i> Reactivate
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $allDeferments->links() }}
        </div>
    </div>
</div>
@endsection

