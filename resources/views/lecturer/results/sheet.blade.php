@extends('layouts.app')

@section('title', 'Result Sheet - Lecturer')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">
                {{ $sheet->course->course_code }} — {{ $sheet->course->course_title }}
            </h3>
            <div class="text-muted">
                {{ $sheet->academic_year }} · {{ $sheet->semester }}
                · Session: {{ $lecturer->session->name ?? 'N/A' }}
                · Status:
                <span class="badge bg-{{ $sheet->status === 'published' ? 'success' : ($sheet->status === 'returned' ? 'warning' : 'primary') }}">
                    {{ strtoupper(str_replace('_', ' ', $sheet->status)) }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('lecturer.results.index') }}" class="btn btn-outline-secondary btn-sm">All Courses</a>
            @if($sheet->isEditableByLecturer())
                <form method="POST" action="{{ route('lecturer.results.submit', [$lecturer, $sheet]) }}"
                      onsubmit="return confirm('Submit this result sheet to HOD? You will not be able to edit until it is returned.');">
                    @csrf
                    <button class="btn btn-success btn-sm" type="submit">
                        <i class="fas fa-paper-plane"></i> Submit to HOD
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($sheet->status === 'returned' && $sheet->return_comments)
        <div class="alert alert-warning">
            <strong>Returned by reviewer:</strong> {{ $sheet->return_comments }}
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-center"><div class="card-body py-2">
                <div class="small text-muted">Students</div>
                <strong>{{ $progress['students'] }}</strong>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-center"><div class="card-body py-2">
                <div class="small text-muted">Marks entered</div>
                <strong>{{ $progress['entered'] }}/{{ $progress['expected'] }}</strong>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-center"><div class="card-body py-2">
                <div class="small text-muted">Completion</div>
                <strong>{{ $progress['percent'] }}%</strong>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card text-center"><div class="card-body py-2">
                <div class="small text-muted">Missing students</div>
                <strong>{{ $progress['incomplete_students'] }}</strong>
            </div></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Enter / Upload by Component</strong></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Max</th>
                            <th>Contribution</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($components as $component)
                            <tr>
                                <td>{{ $component->code }}</td>
                                <td>{{ $component->name }}</td>
                                <td><span class="badge bg-{{ $component->category === 'exam' ? 'dark' : 'secondary' }}">{{ ucfirst($component->category) }}</span></td>
                                <td>{{ $component->max_mark }}</td>
                                <td>{{ $component->contribution }}</td>
                                <td>
                                    @if($sheet->isEditableByLecturer())
                                        <a href="{{ route('lecturer.results.enter', [$lecturer, $sheet, $component]) }}" class="btn btn-sm btn-outline-primary">Enter Marks</a>
                                        <a href="{{ route('lecturer.results.csv', [$lecturer, $sheet, $component]) }}" class="btn btn-sm btn-outline-success">CSV Upload</a>
                                    @else
                                        <span class="text-muted small">Locked</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Course Result Sheet</strong></div>
        <div class="card-body table-responsive">
            @if(count($rows) === 0)
                <div class="alert alert-info mb-0">No registered students found for this course/session/semester.</div>
            @else
                <table class="table table-bordered table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            @foreach($components as $component)
                                <th class="text-center" title="{{ $component->name }}">{{ $component->code }}</th>
                            @endforeach
                            <th class="text-center">Class</th>
                            <th class="text-center">Exam</th>
                            <th class="text-center">Mark</th>
                            <th class="text-center">Grade</th>
                            <th class="text-center">GP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['student']->student_id }}</td>
                                <td>{{ $row['student']->user->name ?? 'N/A' }}</td>
                                @foreach($components as $component)
                                    <td class="text-center">{{ $row['scores'][$component->id] ?? '—' }}</td>
                                @endforeach
                                <td class="text-center">{{ $row['class_mark'] ?? '—' }}</td>
                                <td class="text-center">{{ $row['exam_mark'] ?? '—' }}</td>
                                <td class="text-center"><strong>{{ $row['final_mark'] ?? '—' }}</strong></td>
                                <td class="text-center">{{ $row['grade'] ?? '—' }}</td>
                                <td class="text-center">{{ $row['grade_point'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
