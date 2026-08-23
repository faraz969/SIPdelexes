@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Create Course — {{ $department->name }}</h4>
                </div>
                <div class="card-body">
                    @if($programs->isEmpty())
                        <div class="alert alert-warning">No active programs in your department. Ask an admin to create a program first.</div>
                    @endif

                    <form action="{{ route('hod.courses.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('course_code') is-invalid @enderror"
                                   id="course_code" name="course_code" value="{{ old('course_code') }}" required placeholder="e.g. CS101">
                            @error('course_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="course_title" class="form-label">Course Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('course_title') is-invalid @enderror"
                                   id="course_title" name="course_title" value="{{ old('course_title') }}" required>
                            @error('course_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
                            <select class="form-select @error('program_id') is-invalid @enderror" id="program_id" name="program_id" required>
                                <option value="">-- Select Program --</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="academic_year" class="form-label">Academic Year</label>
                                <input type="text" class="form-control @error('academic_year') is-invalid @enderror"
                                       id="academic_year" name="academic_year" value="{{ old('academic_year', $defaultAcademicYear) }}" placeholder="e.g. 2025/2026">
                                @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select class="form-select @error('semester') is-invalid @enderror" id="semester" name="semester">
                                    <option value="">-- Select --</option>
                                    <option value="First Semester" {{ old('semester') == 'First Semester' ? 'selected' : '' }}>First Semester</option>
                                    <option value="Second Semester" {{ old('semester') == 'Second Semester' ? 'selected' : '' }}>Second Semester</option>
                                </select>
                                @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="credit_units" class="form-label">Credit Units <span class="text-danger">*</span></label>
                                <input type="number" step="0.5" min="0" class="form-control @error('credit_units') is-invalid @enderror"
                                       id="credit_units" name="credit_units" value="{{ old('credit_units', 3) }}" required>
                                @error('credit_units')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="total_credit_units" class="form-label">Total Credit Units</label>
                                <input type="number" step="0.5" min="0" class="form-control @error('total_credit_units') is-invalid @enderror"
                                       id="total_credit_units" name="total_credit_units" value="{{ old('total_credit_units') }}">
                                @error('total_credit_units')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="assessment_split" class="form-label">Assessment Split</label>
                            <input type="text" class="form-control @error('assessment_split') is-invalid @enderror"
                                   id="assessment_split" name="assessment_split" value="{{ old('assessment_split') }}"
                                   placeholder="e.g. Class 30%, Exam 70%">
                            @error('assessment_split')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_elective" name="is_elective" value="1" {{ old('is_elective') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_elective">Elective (uncheck for Core)</label>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('hod.courses.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" {{ $programs->isEmpty() ? 'disabled' : '' }}>Create Course</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
