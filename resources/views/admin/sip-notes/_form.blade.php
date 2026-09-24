<div class="mb-3">
    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
    <input type="text" class="form-control @error('title') is-invalid @enderror"
           id="title" name="title" value="{{ old('title', $note->title ?? '') }}"
           placeholder="e.g. How to pay fees on SIP" required maxlength="255">
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
    <label for="body" class="form-label">Note content <span class="text-danger">*</span></label>
    <textarea class="form-control @error('body') is-invalid @enderror"
              id="body" name="body" rows="12" required
              placeholder="Write clear steps for students...">{{ old('body', $note->body ?? '') }}</textarea>
    @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <small class="text-muted">Plain text is fine. Line breaks are kept when shown to students.</small>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="sort_order" class="form-label">Sort Order</label>
        <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
               id="sort_order" name="sort_order"
               value="{{ old('sort_order', $note->sort_order ?? 0) }}" min="0">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <small class="text-muted">Lower numbers appear first.</small>
    </div>
    <div class="col-md-6 mb-3 d-flex align-items-end">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $note->is_active ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Visible to students</label>
        </div>
    </div>
</div>
