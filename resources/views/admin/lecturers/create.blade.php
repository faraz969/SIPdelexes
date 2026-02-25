@extends('layouts.app')

@section('head')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container .select2-selection--single { min-height: 38px; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 36px; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Add Lecturer Assignment</h4>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('admin.lecturers.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="user_id" class="form-label">User (Lecturer) <span class="text-danger">*</span></label>
                            <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                                <option value="">-- Search and select lecturer --</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            </select>
                            @if($users->isEmpty())
                                <small class="text-warning">No lecturer users found. Create a user with Lecturer role from <a href="{{ route('admin.users.index') }}">Users management</a> first.</small>
                            @endif
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                            <select class="form-select @error('course_id') is-invalid @enderror" id="course_id" name="course_id" required>
                                <option value="">-- Select Course --</option>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}" {{ old('course_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->course_code }} - {{ $c->course_title }} ({{ $c->program->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="session_id" class="form-label">Session <span class="text-danger">*</span></label>
                            <select class="form-select @error('session_id') is-invalid @enderror" id="session_id" name="session_id" required>
                                <option value="">-- Select Session --</option>
                                @foreach($sessions as $s)
                                    <option value="{{ $s->id }}" {{ old('session_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('session_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.lecturers.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Lecturer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#user_id').select2({
        placeholder: '-- Search and select lecturer --',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endpush
