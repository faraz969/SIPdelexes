@extends('layouts.app')

@section('title', 'Downloads - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-download"></i> Downloads</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    @if($downloads->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No documents available for download yet.
        </div>
    @else
        @foreach($downloads as $documentType => $documents)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        @if($documentType === 'admission_letter')
                            <i class="fas fa-file-alt"></i> Admission Letters
                        @elseif($documentType === 'registration_slip')
                            <i class="fas fa-file-invoice"></i> Registration Slips
                        @elseif($documentType === 'fee_receipt')
                            <i class="fas fa-receipt"></i> Fee Receipts
                        @elseif($documentType === 'exam_slip')
                            <i class="fas fa-clipboard-list"></i> Exam Slips
                        @elseif($documentType === 'admission_form')
                            <i class="fas fa-file-alt"></i> Admission Forms
                        @else
                            <i class="fas fa-file"></i> {{ ucfirst(str_replace('_', ' ', $documentType)) }}
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @foreach($documents as $download)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $download->file_name }}</h6>
                                    <small class="text-muted">
                                        @if($download->semester && $download->academic_year)
                                            {{ $download->semester }} - {{ $download->academic_year }}
                                        @endif
                                        | {{ $download->created_at->format('d M Y') }}
                                    </small>
                                </div>
                                <div class="btn-group" role="group">
                                    @if($download->document_type === 'admission_form' && $download->file_path === 'html')
                                        <a href="{{ route('sip.downloads.file', $download->id) }}" class="btn btn-info btn-sm" target="_blank">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                       
                                    @else
                                        <a href="{{ route('sip.downloads.file', $download->id) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

