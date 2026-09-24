@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">SIP Usage Notes</h3>
            <p class="text-muted mb-0">Guides and tips shown to students in the SIP portal.</p>
        </div>
        <a href="{{ route('admin.sip-notes.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Add Note
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($notes->isEmpty())
                <p class="text-muted mb-0">No notes yet. <a href="{{ route('admin.sip-notes.create') }}">Add the first note</a>.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th>By</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notes as $note)
                                <tr>
                                    <td>{{ $note->sort_order }}</td>
                                    <td>
                                        <strong>{{ $note->title }}</strong>
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($note->body), 80) }}</div>
                                    </td>
                                    <td>
                                        @if($note->is_active)
                                            <span class="badge bg-success">Visible</span>
                                        @else
                                            <span class="badge bg-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td>{{ $note->updated_at->format('d M Y') }}</td>
                                    <td>{{ $note->creator->name ?? '—' }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('admin.sip-notes.edit', $note) }}" class="btn btn-sm btn-outline-warning">Edit</a>
                                        <form action="{{ route('admin.sip-notes.destroy', $note) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this note?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
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
