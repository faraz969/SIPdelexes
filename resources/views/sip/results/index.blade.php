@extends('layouts.app')

@section('title', 'My Results - SIP')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-chart-line"></i> My Results</h2>
        <div>
            @if(!empty($results['semesters']))
                <a href="{{ route('sip.results.pdf') }}" class="btn btn-outline-primary btn-sm" target="_blank">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
            @endif
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <strong>{{ $student->user->name ?? 'Student' }}</strong>
            · ID: {{ $student->student_id }}
            · Programme: {{ $student->program->name ?? '—' }}
            · Level: {{ $student->level_label ?? $student->level }}
        </div>
    </div>

    @if(empty($results['semesters']))
        <div class="alert alert-info">
            No published results yet. Results appear here after Registrar publishes them.
        </div>
    @else
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">Cumulative Summary</div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 mb-2">
                        <div class="small text-muted">Total Credit</div>
                        <strong>{{ $results['cumulative']['total_credit'] }}</strong>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="small text-muted">Weighted Average</div>
                        <strong>{{ $results['cumulative']['weighted_average'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="small text-muted">CGPA</div>
                        <strong>{{ $results['cumulative']['cgpa'] !== null ? number_format($results['cumulative']['cgpa'], 2) : '—' }}</strong>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="small text-muted">Classification</div>
                        <strong>{{ $results['cumulative']['classification'] ?? '—' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @foreach($results['semesters'] as $semester)
            <div class="card mb-4">
                <div class="card-header">
                    <strong>{{ $semester['semester'] }} — {{ $semester['academic_year'] }}</strong>
                    <span class="float-end">GPA: {{ $semester['gpa'] !== null ? number_format($semester['gpa'], 2) : '—' }}</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Course Code</th>
                                <th>Course Title</th>
                                <th class="text-center">Credits</th>
                                <th class="text-center">Class Mark</th>
                                <th class="text-center">Exam Mark</th>
                                <th class="text-center">Mark</th>
                                <th class="text-center">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($semester['courses'] as $course)
                                <tr>
                                    <td>{{ $course['course_code'] }}</td>
                                    <td>{{ $course['course_title'] }}</td>
                                    <td class="text-center">{{ $course['credits'] }}</td>
                                    <td class="text-center">{{ $course['class_mark'] ?? '—' }}</td>
                                    <td class="text-center">{{ $course['exam_mark'] ?? '—' }}</td>
                                    <td class="text-center"><strong>{{ $course['final_mark'] ?? '—' }}</strong></td>
                                    <td class="text-center">{{ $course['grade'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="small text-muted">
                        Total Credit: {{ $semester['total_credit'] }}
                        · Credit Obtained: {{ $semester['credit_obtained'] }}
                        · Weighted Marks: {{ $semester['weighted_marks'] }}
                        · Weighted Average: {{ $semester['weighted_average'] ?? '—' }}
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
