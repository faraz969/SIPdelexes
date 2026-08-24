<div class="mb-3">
    <label for="name" class="form-label">Document Name <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('name') is-invalid @enderror"
           id="name" name="name" value="{{ old('name', $document->name ?? '') }}"
           placeholder="e.g. Prospectus, Student Handbook" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="text-muted">This is the title students will see on the Downloads page.</small>
</div>

<div class="mb-3">
    <label for="document" class="form-label">
        Document File @if(empty($document))<span class="text-danger">*</span>@endif
    </label>
    <input type="file" class="form-control @error('document') is-invalid @enderror"
           id="document" name="document" {{ empty($document) ? 'required' : '' }}
           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png">
    @error('document')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="text-muted">PDF, Word, Excel, PowerPoint, or image. Max 10 MB.</small>
    @if(!empty($document))
        <div class="mt-2">
            Current file: <a href="{{ route('admin.sip-documents.file', $document) }}" target="_blank">{{ $document->original_filename }}</a>
        </div>
    @endif
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="sort_order" class="form-label">Sort Order</label>
        <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
               id="sort_order" name="sort_order" value="{{ old('sort_order', $document->sort_order ?? 0) }}" min="0">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Lower numbers appear first.</small>
    </div>
    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $document->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Visible to students</label>
        </div>
    </div>
</div>
