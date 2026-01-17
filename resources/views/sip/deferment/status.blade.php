@extends('layouts.app')

@section('title', 'Deferment Status - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-pause-circle"></i> Deferment Status</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    @if($deferments->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No deferment requests found.
            <a href="{{ route('sip.deferment.form') }}" class="btn btn-sm btn-primary">Request Deferment</a>
        </div>
    @else
        @foreach($deferments as $deferment)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        Request #{{ $deferment->id }}
                        @if($deferment->status === 'pending')
                            <span class="badge bg-warning float-end">Pending</span>
                        @elseif($deferment->status === 'approved')
                            <span class="badge bg-success float-end">Approved</span>
                        @elseif($deferment->status === 'rejected')
                            <span class="badge bg-danger float-end">Rejected</span>
                        @elseif($deferment->status === 'reactivated')
                            <span class="badge bg-info float-end">Reactivated</span>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Reason:</strong></p>
                            <p>{{ $deferment->reason }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Defer From:</strong> {{ $deferment->defer_from->format('d M Y') }}</p>
                            @if($deferment->defer_to)
                                <p><strong>Defer To:</strong> {{ $deferment->defer_to->format('d M Y') }}</p>
                            @endif
                            <p><strong>Submitted:</strong> {{ $deferment->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>

                    @if($deferment->registrar_comments)
                        <hr>
                        <div class="alert alert-{{ $deferment->status === 'approved' ? 'success' : ($deferment->status === 'rejected' ? 'danger' : 'info') }}">
                            <strong>Registrar Comments:</strong>
                            <p class="mb-0">{{ $deferment->registrar_comments }}</p>
                            @if($deferment->approver)
                                <small>Reviewed by: {{ $deferment->approver->name }} on {{ $deferment->approved_at->format('d M Y H:i') }}</small>
                            @endif
                        </div>
                    @endif

                    @if($deferment->reactivated_at)
                        <div class="alert alert-info">
                            <i class="fas fa-check-circle"></i> 
                            Reactivated on: {{ $deferment->reactivated_at->format('d M Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <div class="mt-3">
        <a href="{{ route('sip.deferment.form') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Deferment Request
        </a>
    </div>
</div>
@endsection

