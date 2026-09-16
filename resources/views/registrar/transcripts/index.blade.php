@extends('layouts.app')

@section('title', 'Transcript Requests - Registrar')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1"><i class="fas fa-scroll"></i> Transcript Requests</h2>
            <p class="text-muted mb-0">Approve paid transcript requests. Download official PDFs for students.</p>
        </div>
        <a href="{{ route('registrar.dashboard') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($pending->count() > 0)
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                Pending Approval ({{ $pending->count() }})
            </div>
            <div class="card-body">
                @foreach($pending as $req)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-7">
                                    <h5 class="mb-1">
                                        {{ $req->student->user->name ?? 'N/A' }}
                                        <small class="text-muted">({{ $req->student->student_id ?? 'N/A' }})</small>
                                    </h5>
                                    <p class="mb-1"><strong>Programme:</strong> {{ $req->student->program->name ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Amount paid:</strong> GHS {{ number_format($req->amount, 2) }}</p>
                                    <p class="mb-1"><strong>Copies:</strong> {{ $req->copies }}</p>
                                    @if($req->purpose)
                                        <p class="mb-1"><strong>Purpose:</strong> {{ $req->purpose }}</p>
                                    @endif
                                    <p class="mb-1"><strong>Invoice:</strong> {{ optional($req->invoice)->invoice_number ?? '—' }}</p>
                                    <p class="mb-0 small text-muted">
                                        Requested {{ $req->created_at->format('d M Y H:i') }}
                                        @if($req->paid_at)
                                            · Paid {{ $req->paid_at->format('d M Y H:i') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-5">
                                    <a href="{{ route('registrar.transcripts.download', $req) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                        <i class="fas fa-file-pdf"></i> Download PDF
                                    </a>
                                    <form method="POST" action="{{ route('registrar.transcripts.approve', $req) }}" class="mb-2">
                                        @csrf
                                        <textarea name="comments" class="form-control mb-2" rows="2" placeholder="Approval comments (optional)"></textarea>
                                        <button type="submit" class="btn btn-success btn-sm w-100"
                                                onclick="return confirm('Approve this transcript request?');">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('registrar.transcripts.reject', $req) }}">
                                        @csrf
                                        <textarea name="comments" class="form-control mb-2" rows="2" placeholder="Rejection reason (required)" required></textarea>
                                        <button type="submit" class="btn btn-danger btn-sm w-100"
                                                onclick="return confirm('Reject this transcript request?');">
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
    @else
        <div class="alert alert-info">No transcript requests awaiting approval.</div>
    @endif

    <div class="card">
        <div class="card-header">All Requests</div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($all as $req)
                        <tr>
                            <td>{{ $req->student->user->name ?? 'N/A' }}</td>
                            <td>{{ $req->student->student_id ?? 'N/A' }}</td>
                            <td>GHS {{ number_format($req->amount, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $req->status === 'approved' ? 'success' : ($req->status === 'rejected' ? 'danger' : ($req->status === 'pending_approval' ? 'warning text-dark' : 'secondary')) }}">
                                    {{ $req->statusLabel() }}
                                </span>
                            </td>
                            <td>{{ $req->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                @if(in_array($req->status, ['approved', 'pending_approval'], true))
                                    <a href="{{ route('registrar.transcripts.download', $req) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i> PDF
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $all->links() }}
        </div>
    </div>
</div>
@endsection
