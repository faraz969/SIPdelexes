@extends('layouts.app')

@section('title', 'SIP Documents')

@section('content')
<div class="container py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><i class="fas fa-file-download me-2"></i>SIP Download Documents</h3>
            <small class="text-muted">Prospectus and other files shown on the student Downloads page</small>
        </div>
        <a href="{{ route('admin.sip-documents.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-upload"></i> Upload Document
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($documents->isEmpty())
                <p class="text-muted mb-0">No documents uploaded yet. <a href="{{ route('admin.sip-documents.create') }}">Upload the first document</a>.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>File</th>
                                <th>Status</th>
                                <th>Sort</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $document)
                                <tr>
                                    <td><strong>{{ $document->name }}</strong></td>
                                    <td>
                                        <small class="text-muted">{{ $document->original_filename }}</small>
                                    </td>
                                    <td>
                                        @if($document->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td>{{ $document->sort_order }}</td>
                                    <td>
                                        <small>{{ $document->created_at->format('d M Y') }}</small>
                                        @if($document->creator)
                                            <br><small class="text-muted">by {{ $document->creator->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.sip-documents.file', $document) }}" class="btn btn-outline-info" target="_blank" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.sip-documents.edit', $document) }}" class="btn btn-outline-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('admin.sip-documents.destroy', $document) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this document?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
