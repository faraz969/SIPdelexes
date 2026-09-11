@extends('layouts.app')

@section('title', 'Enter Marks - Lecturer')

@section('content')
<div class="container py-4">
    <div class="mb-3">
        <a href="{{ route('lecturer.results.sheet', [$lecturer, $sheet]) }}" class="btn btn-outline-secondary btn-sm">&larr; Back to Sheet</a>
    </div>

    <h3 class="mb-1">Enter Marks — {{ $component->name }}</h3>
    <p class="text-muted">
        {{ $sheet->course->course_code }} · {{ $sheet->academic_year }} / {{ $sheet->semester }}
        · Max mark: <strong>{{ $component->max_mark }}</strong>
        · Contribution: <strong>{{ $component->contribution }}</strong> ({{ ucfirst($component->category) }})
    </p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('lecturer.results.enter.save', [$lecturer, $sheet, $component]) }}">
        @csrf
        <div class="card">
            <div class="card-body table-responsive">
                @if($roster->isEmpty())
                    <div class="alert alert-info mb-0">No students in roster.</div>
                @else
                    <table class="table table-sm table-striped align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th style="width: 160px;">Mark (0–{{ $component->max_mark }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roster as $i => $student)
                                @php
                                    $existingScore = $existing->get($student->id);
                                    $oldVal = old('marks.'.$student->id, $existingScore->raw_mark ?? '');
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $student->student_id }}</td>
                                    <td>{{ $student->user->name ?? 'N/A' }}</td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="{{ $component->max_mark }}"
                                               class="form-control form-control-sm @error('marks.'.$student->id) is-invalid @enderror"
                                               name="marks[{{ $student->id }}]" value="{{ $oldVal }}"
                                               placeholder="Leave blank to clear">
                                        @error('marks.'.$student->id)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            @if($roster->isNotEmpty())
                <div class="card-footer d-flex justify-content-between">
                    <a href="{{ route('lecturer.results.sheet', [$lecturer, $sheet]) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Marks</button>
                </div>
            @endif
        </div>
    </form>
</div>
@endsection
