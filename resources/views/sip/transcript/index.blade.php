@extends('layouts.app')

@section('title', 'Official Transcript - SIP')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-scroll"></i> Official Transcript</h2>
        <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-info">
        Request and pay for an official transcript. After Registrar approval you can <strong>view</strong> it in SIP.
        Official PDF copies are issued by the Registrar (students cannot download the PDF).
        Current fee: <strong>GHS {{ number_format($fee, 2) }}</strong>
    </div>

    @if(!$openRequest)
        <div class="card mb-4">
            <div class="card-header">New Transcript Request</div>
            <div class="card-body">
                <form method="POST" action="{{ route('sip.transcript.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Purpose (optional)</label>
                        <input type="text" name="purpose" class="form-control" maxlength="500"
                               value="{{ old('purpose') }}" placeholder="e.g. Job application, further studies">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Number of copies</label>
                        <select name="copies" class="form-select" style="max-width: 120px;">
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ (int) old('copies', 1) === $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                        <small class="text-muted">Fee is charged per request (not multiplied by copies unless Admin policy changes).</small>
                    </div>
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Submit transcript request{{ $fee > 0 ? ' and proceed to payment of GHS ' . number_format($fee, 2) : '' }}?');">
                        <i class="fas fa-paper-plane"></i> Request Transcript
                    </button>
                </form>
            </div>
        </div>
    @else
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning">Open Request</div>
            <div class="card-body">
                <p class="mb-1"><strong>Status:</strong> {{ $openRequest->statusLabel() }}</p>
                <p class="mb-1"><strong>Amount:</strong> GHS {{ number_format($openRequest->amount, 2) }}</p>
                <p class="mb-3"><strong>Submitted:</strong> {{ $openRequest->created_at->format('d M Y H:i') }}</p>
                @if($openRequest->isPendingPayment() && $openRequest->invoice_id)
                    <a href="{{ route('sip.payments.pay', $openRequest->invoice_id) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-credit-card"></i> Pay Fee Now
                    </a>
                @elseif($openRequest->isPendingApproval())
                    <div class="alert alert-secondary mb-0">Payment received. Waiting for Registrar approval.</div>
                @endif
            </div>
        </div>
    @endif

    @if($latestApproved)
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">Approved Transcript</div>
            <div class="card-body">
                <p>Your official transcript was approved on {{ optional($latestApproved->approved_at)->format('d M Y H:i') }}.</p>
                <a href="{{ route('sip.transcript.view', $latestApproved) }}" class="btn btn-outline-success">
                    <i class="fas fa-eye"></i> View Transcript
                </a>
                <p class="small text-muted mt-2 mb-0">View only — PDF download is available through the Registrar’s office.</p>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Request History</div>
        <div class="card-body table-responsive">
            @if($requests->isEmpty())
                <div class="alert alert-light mb-0">No transcript requests yet.</div>
            @else
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $req)
                            <tr>
                                <td>{{ $req->id }}</td>
                                <td>{{ $req->created_at->format('d M Y') }}</td>
                                <td>GHS {{ number_format($req->amount, 2) }}</td>
                                <td>{{ $req->statusLabel() }}</td>
                                <td class="text-end">
                                    @if($req->isApproved())
                                        <a href="{{ route('sip.transcript.view', $req) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    @elseif($req->isPendingPayment() && $req->invoice_id)
                                        <a href="{{ route('sip.payments.pay', $req->invoice_id) }}" class="btn btn-sm btn-success">Pay</a>
                                    @elseif($req->isRejected() && $req->registrar_comments)
                                        <small class="text-danger">{{ $req->registrar_comments }}</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
