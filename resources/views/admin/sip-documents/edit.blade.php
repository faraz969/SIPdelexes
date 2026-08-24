@extends('layouts.app')

@section('title', 'Edit SIP Document')

@section('content')
<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Document</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.sip-documents.update', $document) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('admin.sip-documents._form', ['document' => $document])
                        <div class="d-flex justify-content-between mt-3">
                            <a href="{{ route('admin.sip-documents.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
