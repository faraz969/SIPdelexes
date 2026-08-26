@php
    $selectedProgram = old('program_id', $offering->program_id ?? '');
    $selectedCourses = old('course_ids', $offering->course_ids ?? []);
    $selectedCourses = array_map('intval', (array) $selectedCourses);
@endphp

<div class="row">
    <div class="col-md-3 mb-3">
        <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
        <select name="program_id" id="program_id" class="form-select @error('program_id') is-invalid @enderror" required>
            <option value="">-- Select Program --</option>
            @foreach($programs as $program)
                <option value="{{ $program->id }}" {{ (string) $selectedProgram === (string) $program->id ? 'selected' : '' }}>
                    {{ $program->name }}
                </option>
            @endforeach
        </select>
        @error('program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label for="academic_year" class="form-label">Academic Year <span class="text-danger">*</span></label>
        <input type="text" name="academic_year" id="academic_year"
               class="form-control @error('academic_year') is-invalid @enderror"
               value="{{ old('academic_year', $offering->academic_year ?? ($defaultAcademicYear ?? '')) }}"
               placeholder="e.g. 2025/2026" required>
        @error('academic_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
        <select name="semester" id="semester" class="form-select @error('semester') is-invalid @enderror" required>
            <option value="">-- Select --</option>
            <option value="First Semester" {{ old('semester', $offering->semester ?? '') == 'First Semester' ? 'selected' : '' }}>First Semester</option>
            <option value="Second Semester" {{ old('semester', $offering->semester ?? '') == 'Second Semester' ? 'selected' : '' }}>Second Semester</option>
        </select>
        @error('semester')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3 mb-3">
        <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
        <select name="level" id="level" class="form-select @error('level') is-invalid @enderror" required>
            @foreach(($levels ?? \App\Models\Student::LEVELS) as $levelOption)
                <option value="{{ $levelOption }}" {{ old('level', $offering->level ?? '100') == $levelOption ? 'selected' : '' }}>
                    Level {{ $levelOption }}
                </option>
            @endforeach
        </select>
        @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Courses for this semester <span class="text-danger">*</span></label>
    <p class="text-muted small mb-2">Select the courses students will confirm for this program, semester, and level. Students only see packages matching their level.</p>
    @error('course_ids')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
    @error('course_ids.*')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

    <div id="coursesEmpty" class="alert alert-secondary" style="display:none;">Select a program to load courses.</div>
    <div id="coursesList" class="border rounded p-3" style="max-height: 360px; overflow-y: auto;"></div>
    <div class="mt-2"><strong>Selected credits:</strong> <span id="totalCredits">0</span></div>
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes (optional)</label>
    <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes', $offering->notes ?? '') }}</textarea>
</div>

<div class="form-check mb-2">
    <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1"
           {{ old('is_published', $offering->is_published ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_published">Publish now (visible to students for confirmation)</label>
</div>

<script>
(function () {
    var coursesByProgram = @json($coursesByProgram);
    var selected = @json($selectedCourses);
    var programSelect = document.getElementById('program_id');
    var listEl = document.getElementById('coursesList');
    var emptyEl = document.getElementById('coursesEmpty');
    var totalEl = document.getElementById('totalCredits');

    function render() {
        var programId = programSelect.value;
        listEl.innerHTML = '';
        if (!programId || !coursesByProgram[programId] || !coursesByProgram[programId].length) {
            emptyEl.style.display = 'block';
            emptyEl.textContent = programId
                ? 'No active courses found for this program. Create courses first.'
                : 'Select a program to load courses.';
            totalEl.textContent = '0';
            return;
        }
        emptyEl.style.display = 'none';
        var total = 0;
        coursesByProgram[programId].forEach(function (course) {
            var checked = selected.indexOf(course.id) !== -1 || selected.indexOf(String(course.id)) !== -1;
            if (checked) total += parseFloat(course.credits || 0);
            var wrap = document.createElement('div');
            wrap.className = 'form-check mb-2';
            wrap.innerHTML =
                '<input class="form-check-input course-cb" type="checkbox" name="course_ids[]" value="' + course.id + '" id="c_' + course.id + '" data-credits="' + course.credits + '"' + (checked ? ' checked' : '') + '>' +
                '<label class="form-check-label" for="c_' + course.id + '">' + course.label + '</label>';
            listEl.appendChild(wrap);
        });
        totalEl.textContent = total;
        listEl.querySelectorAll('.course-cb').forEach(function (cb) {
            cb.addEventListener('change', updateTotal);
        });
    }

    function updateTotal() {
        var total = 0;
        selected = [];
        listEl.querySelectorAll('.course-cb').forEach(function (cb) {
            if (cb.checked) {
                total += parseFloat(cb.getAttribute('data-credits') || 0);
                selected.push(parseInt(cb.value, 10));
            }
        });
        totalEl.textContent = total;
    }

    programSelect.addEventListener('change', function () {
        selected = [];
        render();
    });
    render();
})();
</script>
