@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Lecturer Assignment</h4>
                </div>

                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('admin.lecturers.update', $lecturer) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="user_id" class="form-label">User (Lecturer) <span class="text-danger">*</span></label>
                            <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ old('user_id', $lecturer->user_id) == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->email }}) {{ $u->role === 'lecturer' ? '- Lecturer' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                            <select class="form-select @error('course_id') is-invalid @enderror" id="course_id" name="course_id" required>
                                @foreach($courses as $c)
                                    <option value="{{ $c->id }}" {{ old('course_id', $lecturer->course_id) == $c->id ? 'selected' : '' }}>
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
                                @foreach($sessions as $s)
                                    <option value="{{ $s->id }}" {{ old('session_id', $lecturer->session_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('session_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.lecturers.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Lecturer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
