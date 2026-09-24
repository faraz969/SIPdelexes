@extends('layouts.app')

@section('title', 'How to Use SIP')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1"><i class="fas fa-lightbulb"></i> How to Use SIP</h2>
            <p class="text-muted mb-0">Guides and notes from the administration.</p>
        </div>
        <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    @if($notes->isEmpty())
        <div class="alert alert-info mb-0">
            No usage notes have been published yet. Check back later.
        </div>
    @else
        <div class="accordion" id="sipNotesAccordion">
            @foreach($notes as $index => $note)
                <div class="accordion-item mb-2 border rounded overflow-hidden">
                    <h2 class="accordion-header" id="heading-{{ $note->id }}">
                        <button class="accordion-button {{ $index === 0 ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapse-{{ $note->id }}"
                                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                aria-controls="collapse-{{ $note->id }}">
                            <strong>{{ $note->title }}</strong>
                        </button>
                    </h2>
                    <div id="collapse-{{ $note->id }}"
                         class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                         aria-labelledby="heading-{{ $note->id }}"
                         data-bs-parent="#sipNotesAccordion">
                        <div class="accordion-body">
                            <div class="sip-note-body" style="white-space: pre-wrap; line-height: 1.6;">{{ $note->body }}</div>
                            <div class="small text-muted mt-3">
                                Updated {{ $note->updated_at->format('d M Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
