@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Upload Courses from CSV</h4>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Courses
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('import_errors') && count(session('import_errors')) > 0)
                        <div class="alert alert-warning">
                            <strong>Errors:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach(session('import_errors') as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @error('file')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <p class="text-muted mb-4">
                        Upload a CSV file with a header row. Rows with an existing <strong>course_code</strong> will be updated; new codes will create new courses.
                        Maximum file size: 2 MB.
                    </p>

                    <form action="{{ route('admin.courses.upload.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label for="file" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".csv,.txt" required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.courses.upload.sample') }}" class="btn btn-outline-primary">
                                <i class="fas fa-download"></i> Download Sample CSV
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Upload & Import
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h6 class="mb-2">CSV format</h6>
                    <p class="small text-muted mb-1">First row must be the header. Supported columns (underscore or space):</p>
                    <table class="table table-sm table-bordered small">
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Required</th>
                                <th>Example</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>course_code</td><td>Yes</td><td>CS101</td></tr>
                            <tr><td>course_title</td><td>Yes</td><td>Introduction to Computer Science</td></tr>
                            <tr><td>program_id</td><td>Yes*</td><td>1 (or use program name in column "program")</td></tr>
                            <tr><td>academic_year</td><td>No</td><td>{{ $sampleAcademicYear ?? '2025-2026' }}</td></tr>
                            <tr><td>semester</td><td>No</td><td>First Semester</td></tr>
                            <tr><td>credit_units</td><td>Yes</td><td>3</td></tr>
                            <tr><td>total_credit_units</td><td>No</td><td>—</td></tr>
                            <tr><td>assessment_split</td><td>No</td><td>Class 30%, Exam 70%</td></tr>
                            <tr><td>is_elective</td><td>No</td><td>0 or 1 (0 = Core, 1 = Elective)</td></tr>
                            <tr><td>is_active</td><td>No</td><td>1 (default)</td></tr>
                            <tr><td>sort_order</td><td>No</td><td>0</td></tr>
                        </tbody>
                    </table>
                    <p class="small text-muted">* Use <strong>program_id</strong> (numeric, from Programs list) or <strong>program</strong> (exact program name).</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
