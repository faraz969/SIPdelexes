{{-- Shared assessment component editor for course create/edit --}}
@php
    $classTotal = config('results.class_mark_total', 30);
    $examTotal = config('results.exam_mark_total', 70);
    $existing = old('components');
    if ($existing === null) {
        if (isset($course) && $course->relationLoaded('assessmentComponents') && $course->assessmentComponents->isNotEmpty()) {
            $existing = $course->assessmentComponents->map(function ($c) {
                return [
                    'code' => $c->code,
                    'name' => $c->name,
                    'category' => $c->category,
                    'max_mark' => $c->max_mark,
                    'contribution' => $c->contribution,
                ];
            })->values()->all();
        } else {
            $existing = config('results.default_components', []);
        }
    }
@endphp

<div class="card mt-4 mb-3 border-primary">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Assessment Components</h5>
        <small>Class total must be {{ $classTotal }} · Exam total must be {{ $examTotal }}</small>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Configure how this course is assessed. You can add multiple Class components
            (assignments, quizzes, mid-semester, etc.) whose contributions add up to
            <strong>{{ $classTotal }}</strong>, plus Exam component(s) totaling
            <strong>{{ $examTotal }}</strong>.
        </p>

        @error('components')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="assessmentComponentsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 12%;">Code</th>
                        <th style="width: 28%;">Name</th>
                        <th style="width: 14%;">Category</th>
                        <th style="width: 14%;">Max Mark</th>
                        <th style="width: 14%;">Contribution</th>
                        <th style="width: 10%;"></th>
                    </tr>
                </thead>
                <tbody id="assessmentComponentsBody">
                    @foreach($existing as $i => $row)
                        <tr class="component-row">
                            <td>
                                <input type="text" class="form-control form-control-sm" name="components[{{ $i }}][code]"
                                       value="{{ $row['code'] ?? '' }}" placeholder="ASSIGN1" required>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm" name="components[{{ $i }}][name]"
                                       value="{{ $row['name'] ?? '' }}" placeholder="Assignment 1" required>
                            </td>
                            <td>
                                <select class="form-select form-select-sm component-category" name="components[{{ $i }}][category]" required>
                                    <option value="class" {{ ($row['category'] ?? '') === 'class' ? 'selected' : '' }}>Class</option>
                                    <option value="exam" {{ ($row['category'] ?? '') === 'exam' ? 'selected' : '' }}>Exam</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" name="components[{{ $i }}][max_mark]"
                                       value="{{ $row['max_mark'] ?? '' }}" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm component-contribution"
                                       name="components[{{ $i }}][contribution]" value="{{ $row['contribution'] ?? '' }}" required>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-component-row" title="Remove">&times;</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="addComponentRow">
                <i class="fas fa-plus"></i> Add Component
            </button>
            <div class="small">
                <span class="me-3">Class: <strong id="classContributionTotal">0</strong> / {{ $classTotal }}</span>
                <span>Exam: <strong id="examContributionTotal">0</strong> / {{ $examTotal }}</span>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const body = document.getElementById('assessmentComponentsBody');
    const addBtn = document.getElementById('addComponentRow');
    const classTarget = {{ (float) $classTotal }};
    const examTarget = {{ (float) $examTotal }};
    let rowIndex = body ? body.querySelectorAll('.component-row').length : 0;

    function rowHtml(i) {
        return `
            <tr class="component-row">
                <td><input type="text" class="form-control form-control-sm" name="components[${i}][code]" placeholder="ASSIGN1" required></td>
                <td><input type="text" class="form-control form-control-sm" name="components[${i}][name]" placeholder="Assignment 1" required></td>
                <td>
                    <select class="form-select form-select-sm component-category" name="components[${i}][category]" required>
                        <option value="class" selected>Class</option>
                        <option value="exam">Exam</option>
                    </select>
                </td>
                <td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm" name="components[${i}][max_mark]" required></td>
                <td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm component-contribution" name="components[${i}][contribution]" required></td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-component-row" title="Remove">&times;</button></td>
            </tr>`;
    }

    function recalcTotals() {
        let classSum = 0;
        let examSum = 0;
        body.querySelectorAll('.component-row').forEach(function (row) {
            const category = (row.querySelector('.component-category') || {}).value;
            const contrib = parseFloat((row.querySelector('.component-contribution') || {}).value) || 0;
            if (category === 'exam') examSum += contrib;
            else classSum += contrib;
        });
        const classEl = document.getElementById('classContributionTotal');
        const examEl = document.getElementById('examContributionTotal');
        if (classEl) {
            classEl.textContent = classSum.toFixed(2);
            classEl.classList.toggle('text-danger', Math.abs(classSum - classTarget) > 0.01);
            classEl.classList.toggle('text-success', Math.abs(classSum - classTarget) <= 0.01);
        }
        if (examEl) {
            examEl.textContent = examSum.toFixed(2);
            examEl.classList.toggle('text-danger', Math.abs(examSum - examTarget) > 0.01);
            examEl.classList.toggle('text-success', Math.abs(examSum - examTarget) <= 0.01);
        }
    }

    if (addBtn && body) {
        addBtn.addEventListener('click', function () {
            body.insertAdjacentHTML('beforeend', rowHtml(rowIndex++));
            recalcTotals();
        });

        body.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-component-row');
            if (!btn) return;
            const rows = body.querySelectorAll('.component-row');
            if (rows.length <= 1) return;
            btn.closest('.component-row').remove();
            recalcTotals();
        });

        body.addEventListener('input', recalcTotals);
        body.addEventListener('change', recalcTotals);
        recalcTotals();
    }
})();
</script>
