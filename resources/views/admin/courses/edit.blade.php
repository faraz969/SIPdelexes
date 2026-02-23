@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Edit Course</h4>
                </div>

                <div class="card-body">
                    <form action="{{ route('admin.courses.update', $course) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('course_code') is-invalid @enderror"
                                   id="course_code" name="course_code" value="{{ old('course_code', $course->course_code) }}" required>
                            @error('course_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="course_title" class="form-label">Course Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('course_title') is-invalid @enderror"
                                   id="course_title" name="course_title" value="{{ old('course_title', $course->course_title) }}" required>
                            @error('course_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
                            <select class="form-control @error('program_id') is-invalid @enderror" id="program_id" name="program_id" required>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ old('program_id', $course->program_id) == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                                @endforeach
                            </select>
                            @error('program_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="academic_year" class="form-label">Academic Year</label>
                                    <input type="text" class="form-control @error('academic_year') is-invalid @enderror"
                                           id="academic_year" name="academic_year" value="{{ old('academic_year', $course->academic_year) }}" placeholder="e.g. 2025-2026">
                                    @error('academic_year')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <input type="text" class="form-control @error('semester') is-invalid @enderror"
                                           id="semester" name="semester" value="{{ old('semester', $course->semester) }}" placeholder="e.g. First Semester">
                                    @error('semester')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="credit_units" class="form-label">Credit Units <span class="text-danger">*</span></label>
                                    <input type="number" step="0.5" min="0" class="form-control @error('credit_units') is-invalid @enderror"
                                           id="credit_units" name="credit_units" value="{{ old('credit_units', $course->credit_units) }}" required>
                                    @error('credit_units')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_credit_units" class="form-label">Total Credit Units</label>
                                    <input type="number" step="0.5" min="0" class="form-control @error('total_credit_units') is-invalid @enderror"
                                           id="total_credit_units" name="total_credit_units" value="{{ old('total_credit_units', $course->total_credit_units) }}">
                                    @error('total_credit_units')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="assessment_split" class="form-label">Assessment Split</label>
                            <input type="text" class="form-control @error('assessment_split') is-invalid @enderror"
                                   id="assessment_split" name="assessment_split" value="{{ old('assessment_split', $course->assessment_split) }}"
                                   placeholder="e.g. Class 30%, Exam 70%">
                            @error('assessment_split')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_elective" name="is_elective" value="1" {{ old('is_elective', $course->is_elective) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_elective">Elective (uncheck for Core)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                           id="sort_order" name="sort_order" value="{{ old('sort_order', $course->sort_order) }}" min="0">
                                    @error('sort_order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $course->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update Course</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
