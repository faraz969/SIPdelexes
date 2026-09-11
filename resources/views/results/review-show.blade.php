@extends('layouts.app')

@section('title', 'Review Result Sheet')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">{{ $sheet->course->course_code }} — {{ $sheet->course->course_title }}</h3>
            <div class="text-muted">
                {{ $sheet->academic_year }} · {{ $sheet->semester }}
                @if($sheet->course->program)
                    · {{ $sheet->course->program->name }}
                @endif
                · Lecturer: {{ $sheet->lecturer->name ?? '—' }}
                · Status:
                <span class="badge bg-{{ $sheet->status === 'published' ? 'success' : ($sheet->status === 'returned' ? 'warning' : 'primary') }}">
                    {{ strtoupper(str_replace('_', ' ', $sheet->status)) }}
                </span>
            </div>
        </div>
        <a href="{{ route($indexRoute) }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($sheet->return_comments)
        <div class="alert alert-warning"><strong>Return comments:</strong> {{ $sheet->return_comments }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-header"><strong>Result Sheet</strong></div>
        <div class="card-body table-responsive">
            @if(count($rows) === 0)
                <div class="alert alert-info mb-0">No student rows on this sheet.</div>
            @else
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            @foreach($components as $component)
                                <th class="text-center">{{ $component->code }}</th>
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

    @if($sheet->approvals->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">Approval History</div>
            <div class="card-body">
                <ul class="mb-0">
                    @foreach($sheet->approvals->sortByDesc('id') as $approval)
                        <li>
                            <strong>{{ strtoupper($approval->stage) }} / {{ strtoupper($approval->action) }}</strong>
                            by {{ $approval->user->name ?? 'System' }}
                            at {{ $approval->created_at->format('d M Y H:i') }}
                            @if($approval->comment)
                                — {{ $approval->comment }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Actions</div>
        <div class="card-body">
            <div class="row">
                @if(!empty($canApprove))
                    <div class="col-md-4 mb-3">
                        <form method="POST" action="{{ route($approveRoute, $sheet) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Approval comment (optional)</label>
                                <textarea name="comment" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success"
                                    onclick="return confirm('Approve this result sheet?');">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                    </div>
                @endif

                @if(!empty($canReturn))
                    <div class="col-md-4 mb-3">
                        <form method="POST" action="{{ route($returnRoute, $sheet) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Return comments <span class="text-danger">*</span></label>
                                <textarea name="comment" class="form-control" rows="2" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning"
                                    onclick="return confirm('Return this sheet to the lecturer?');">
                                <i class="fas fa-undo"></i> Return to Lecturer
                            </button>
                        </form>
                    </div>
                @endif

                @if(!empty($canPublish) && !empty($publishRoute))
                    <div class="col-md-4 mb-3">
                        <form method="POST" action="{{ route($publishRoute, $sheet) }}">
                            @csrf
                            <p class="text-muted small">Publishing makes these results visible to students on SIP.</p>
                            <button type="submit" class="btn btn-primary"
                                    onclick="return confirm('Publish this course result to SIP?');">
                                <i class="fas fa-globe"></i> Publish to SIP
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
