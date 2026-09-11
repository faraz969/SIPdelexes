@extends('layouts.app')

@section('title', 'CSV Upload - Lecturer')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <a href="{{ route('lecturer.results.sheet', [$lecturer, $sheet]) }}" class="btn btn-outline-secondary btn-sm">&larr; Back to Sheet</a>
    </div>

    <h3>CSV Upload — {{ $component->name }}</h3>
    <p class="text-muted">
        {{ $sheet->course->course_code }} · {{ $sheet->academic_year }} / {{ $sheet->semester }}
        · Max: {{ $component->max_mark }} · Code: {{ $component->code }}
    </p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header">Step 1 — Download Template</div>
        <div class="card-body">
            <p class="mb-2">Template includes registered students for this course/session with the correct assessment code.</p>
            <a href="{{ route('lecturer.results.csv.template', [$lecturer, $sheet, $component]) }}" class="btn btn-outline-primary">
                <i class="fas fa-download"></i> Download CSV Template
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Step 2 — Upload &amp; Validate</div>
        <div class="card-body">
            <form method="POST" action="{{ route('lecturer.results.csv.validate', [$lecturer, $sheet, $component]) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label">CSV File</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv" required>
                    <small class="text-muted">
                        Required columns: student_id, mark.
                        Optional: index_no, student_name, academic_year, semester, course_code, assessment_type, assessment_code, max_mark, remarks.
                    </small>
                </div>
                <button type="submit" class="btn btn-primary">Validate CSV</button>
            </form>
        </div>
    </div>
</div>
@endsection
