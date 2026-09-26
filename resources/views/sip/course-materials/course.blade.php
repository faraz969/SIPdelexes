@extends('layouts.app')

@section('title', 'Materials - ' . ($course->course_code ?? 'Course'))

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">{{ $course->course_code }} — {{ $course->course_title }}</h2>
            <p class="text-muted mb-0">{{ $academicYear }} · {{ $semester }}</p>
        </div>
        <a href="{{ route('sip.course-materials.index') }}" class="btn btn-outline-secondary btn-sm">All Courses</a>
    </div>

    @if($materials->isEmpty())
        <div class="alert alert-info mb-0">No materials have been published for this course yet.</div>
    @else
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Uploaded</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($materials as $material)
                            <tr>
                                <td>
                                    <strong>{{ $material->title }}</strong>
                                    @if($material->description)
                                        <div class="small text-muted">{{ $material->description }}</div>
                                    @endif
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $material->fileTypeLabel() }}</span></td>
                                <td>{{ $material->fileSizeLabel() }}</td>
                                <td>{{ $material->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('sip.course-materials.download', $material) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
