@extends('layouts.app')

@section('title', 'Manage Materials - ' . ($lecturer->course->course_code ?? 'Course'))

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">
                {{ $lecturer->course->course_code ?? 'N/A' }} — {{ $lecturer->course->course_title ?? '' }}
            </h3>
            <div class="text-muted">
                Session: {{ $lecturer->session->name ?? 'N/A' }}
                · {{ $academicYear }} / {{ $semester }}
            </div>
        </div>
        <a href="{{ route('lecturer.materials.index', ['academic_year' => $academicYear, 'semester' => $semester]) }}"
           class="btn btn-outline-secondary btn-sm">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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

    <div class="card mb-4">
        <div class="card-header"><strong>Upload Material</strong></div>
        <div class="card-body">
            <form method="POST" action="{{ route('lecturer.materials.store', $lecturer) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="academic_year" value="{{ $academicYear }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}"
                               placeholder="e.g. Week 1 Lecture Slides" required maxlength="255">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1"
                                   {{ old('is_published', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_published">Visible to students</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description (optional)</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="Short note for students...">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" required
                               accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.mp4,.mov,.avi,.webm,.mkv,.zip">
                        <small class="text-muted">
                            PDF, Word, PowerPoint, Excel, images, videos, or ZIP. Max 100 MB.
                        </small>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Uploaded Materials ({{ $materials->count() }})</strong>
            <form method="GET" action="{{ route('lecturer.materials.show', $lecturer) }}" class="d-flex gap-2">
                <select name="academic_year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" {{ $academicYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                    @if($academicYears->isEmpty())
                        <option value="{{ $academicYear }}">{{ $academicYear }}</option>
                    @endif
                </select>
                <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($semesters as $sem)
                        <option value="{{ $sem }}" {{ $semester == $sem ? 'selected' : '' }}>{{ $sem }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="card-body table-responsive">
            @if($materials->isEmpty())
                <div class="alert alert-light mb-0">No materials uploaded for this term yet.</div>
            @else
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Status</th>
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
                                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($material->description, 80) }}</div>
                                    @endif
                                    <div class="small text-muted">{{ $material->original_filename }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $material->fileTypeLabel() }}</span></td>
                                <td>{{ $material->fileSizeLabel() }}</td>
                                <td>
                                    @if($material->is_published)
                                        <span class="badge bg-success">Published</span>
                                    @else
                                        <span class="badge bg-secondary">Hidden</span>
                                    @endif
                                </td>
                                <td>{{ $material->created_at->format('d M Y') }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('lecturer.materials.download', [$lecturer, $material]) }}"
                                       class="btn btn-sm btn-outline-primary">Download</a>
                                    <form method="POST"
                                          action="{{ route('lecturer.materials.update', [$lecturer, $material]) }}"
                                          class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="title" value="{{ $material->title }}">
                                        <input type="hidden" name="description" value="{{ $material->description }}">
                                        <input type="hidden" name="sort_order" value="{{ $material->sort_order }}">
                                        @if($material->is_published)
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                    title="Hide from students">Hide</button>
                                        @else
                                            <input type="hidden" name="is_published" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-success"
                                                    title="Show to students">Publish</button>
                                        @endif
                                    </form>
                                    <form method="POST"
                                          action="{{ route('lecturer.materials.destroy', [$lecturer, $material]) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this material?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
