@extends('layouts.app')

@section('title', 'My Results - SIP')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-chart-line"></i> My Results</h2>
        <div>
            <a href="{{ route('sip.transcript.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-scroll"></i> Official Transcript
            </a>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

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
                        <div class="small text-muted">CCR (Total Credit)</div>
                        <strong>{{ $results['cumulative']['ccr'] ?? $results['cumulative']['total_credit'] }}</strong>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="small text-muted">CGP</div>
                        <strong>{{ isset($results['cumulative']['cgp']) ? number_format($results['cumulative']['cgp'], 2) : '—' }}</strong>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="small text-muted">CGPA / FGPA</div>
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
                    <strong>{{ $semester['heading'] ?? ($semester['semester'] . ' — ' . $semester['academic_year']) }}</strong>
                    <span class="float-end">GPA: {{ $semester['gpa'] !== null ? number_format($semester['gpa'], 2) : '—' }}</span>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>CODE</th>
                                <th>COURSE NAME</th>
                                <th class="text-center">Credits</th>
                                <th class="text-center">Class</th>
                                <th class="text-center">Exam</th>
                                <th class="text-center">Mark</th>
                                <th class="text-center">Grade</th>
                                <th class="text-center">Grade Point</th>
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
                                    <td class="text-center">{{ isset($course['quality_points']) && $course['quality_points'] !== null ? number_format($course['quality_points'], 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="small text-muted">
                        TCR: {{ number_format($semester['tcr'] ?? $semester['total_credit'], 1) }}
                        · TGP: {{ isset($semester['tgp']) ? number_format($semester['tgp'], 2) : '—' }}
                        · GPA: {{ $semester['gpa'] !== null ? number_format($semester['gpa'], 2) : '—' }}
                        · CGP: {{ isset($semester['cgp']) ? number_format($semester['cgp'], 2) : '—' }}
                        · CCR: {{ isset($semester['ccr']) ? number_format($semester['ccr'], 1) : '—' }}
                        · FGPA: {{ isset($semester['fgpa']) && $semester['fgpa'] !== null ? number_format($semester['fgpa'], 2) : '—' }}
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
