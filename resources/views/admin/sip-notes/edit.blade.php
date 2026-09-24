@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card">
                <div class="card-header"><h4 class="mb-0">Edit SIP Usage Note</h4></div>
                <div class="card-body">
                    <form action="{{ route('admin.sip-notes.update', $note) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @include('admin.sip-notes._form', ['note' => $note])
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="{{ route('admin.sip-notes.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
