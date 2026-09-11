@extends('layouts.app')

@section('title', 'CSV Preview - Lecturer')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <a href="{{ route('lecturer.results.csv', [$lecturer, $sheet, $component]) }}" class="btn btn-outline-secondary btn-sm">&larr; Back</a>
    </div>

    <h3>CSV Preview — {{ $component->name }}</h3>
    <p class="text-muted">{{ $sheet->course->course_code }} · Review valid/invalid rows before importing.</p>

    <div class="row mb-3">
        <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><small>Total</small><div><strong>{{ $preview['summary']['total'] }}</strong></div></div></div></div>
        <div class="col-md-3"><div class="card text-center border-success"><div class="card-body py-2"><small>Valid</small><div><strong class="text-success">{{ $preview['summary']['valid'] }}</strong></div></div></div></div>
        <div class="col-md-3"><div class="card text-center border-danger"><div class="card-body py-2"><small>Invalid</small><div><strong class="text-danger">{{ $preview['summary']['invalid'] }}</strong></div></div></div></div>
        <div class="col-md-3"><div class="card text-center border-warning"><div class="card-body py-2"><small>Duplicates</small><div><strong class="text-warning">{{ $preview['summary']['duplicates'] }}</strong></div></div></div></div>
    </div>

    @if(!empty($preview['valid']))
        <div class="card mb-3">
            <div class="card-header text-success">Valid rows ({{ count($preview['valid']) }})</div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Row</th><th>Student ID</th><th>Name</th><th>Mark</th></tr></thead>
                    <tbody>
                        @foreach($preview['valid'] as $row)
                            <tr>
                                <td>{{ $row['row'] }}</td>
                                <td>{{ $row['student_number'] }}</td>
                                <td>{{ $row['student_name'] }}</td>
                                <td>{{ $row['mark'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if(!empty($preview['invalid']))
        <div class="card mb-3 border-danger">
            <div class="card-header text-danger">Invalid rows ({{ count($preview['invalid']) }})</div>
            <div class="card-body table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Row</th><th>Student ID</th><th>Mark</th><th>Errors</th></tr></thead>
                    <tbody>
                        @foreach($preview['invalid'] as $row)
                            <tr>
                                <td>{{ $row['row'] }}</td>
                                <td>{{ $row['student_id'] }}</td>
                                <td>{{ $row['mark'] }}</td>
                                <td>{{ implode('; ', $row['errors']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('lecturer.results.csv.import', [$lecturer, $sheet, $component]) }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="form-check mb-3">
            <input type="hidden" name="allow_overwrite" value="0">
            <input class="form-check-input" type="checkbox" value="1" id="allow_overwrite" name="allow_overwrite">
            <label class="form-check-label" for="allow_overwrite">
                Allow overwrite of existing marks for this component (explicit correction)
            </label>
        </div>
        <button type="submit" class="btn btn-success" {{ empty($preview['valid']) ? 'disabled' : '' }}
                onclick="return confirm('Import valid rows into the result sheet?');">
            Confirm Import ({{ $preview['summary']['valid'] }} valid)
        </button>
        <a href="{{ route('lecturer.results.csv', [$lecturer, $sheet, $component]) }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
