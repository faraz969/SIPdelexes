@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ $pageTitle }}</h4>
                    <a href="{{ route($routePrefix . '.semester-offerings.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if($programs->isEmpty())
                        <div class="alert alert-warning">No active programs available. Create programs first.</div>
                    @endif

                    <form method="POST" action="{{ route($routePrefix . '.semester-offerings.store') }}" id="offeringForm">
                        @csrf
                        @include('shared.semester-offerings._form', ['offering' => null])
                        <div class="d-flex justify-content-between mt-3">
                            <a href="{{ route($routePrefix . '.semester-offerings.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" {{ $programs->isEmpty() ? 'disabled' : '' }}>Save Package</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
