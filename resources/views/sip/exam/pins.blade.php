@extends('layouts.app')

@section('title', 'Exam PIN - SIP')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-key"></i> Exam PIN</h2>
            <a href="{{ route('sip.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    @if(!$student->canGenerateExamPin())
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> 
            <strong>You cannot generate an Exam PIN yet.</strong>
            <br>
            You must have 100% of your fees paid to generate an Exam PIN.
            <br>
            Current Balance: <strong>GHS {{ number_format($student->getTotalBalance(), 2) }}</strong>
        </div>
    @else
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle"></i> Generate New Exam PIN</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Exam PINs are generated for your current level:
                    <strong>Level {{ \App\Models\Student::normalizeLevel($student->level ?? null) }}</strong>
                </p>
                <form method="POST" action="{{ route('sip.exam.generate-pin') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="First Semester">First Semester</option>
                            <option value="Second Semester">Second Semester</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <input type="text" class="form-control" id="academic_year" name="academic_year" value="{{ $student->academic_year }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Level</label>
                        <input type="text" class="form-control" value="Level {{ \App\Models\Student::normalizeLevel($student->level ?? null) }}" readonly>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-key"></i> Generate Exam PIN
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Your Exam PINs</h5>
        </div>
        <div class="card-body">
            @if($pins->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No Exam PINs generated yet.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>PIN</th>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Expires At</th>
                                <th>Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pins as $pin)
                                <tr>
                                    <td>
                                        <code class="bg-light p-2 rounded">{{ $pin->pin }}</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('{{ $pin->pin }}')">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                    </td>
                                    <td>{{ $pin->semester }}</td>
                                    <td>{{ $pin->academic_year }}</td>
                                    <td><span class="badge bg-primary">{{ $pin->level ?? '100' }}</span></td>
                                    <td>
                                        @if($pin->is_used)
                                            <span class="badge bg-secondary">Used</span>
                                        @elseif($pin->isExpired())
                                            <span class="badge bg-danger">Expired</span>
                                        @else
                                            <span class="badge bg-success">Valid</span>
                                        @endif
                                    </td>
                                    <td>{{ $pin->expires_at->format('d M Y H:i') }}</td>
                                    <td>{{ $pin->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('PIN copied to clipboard!');
    });
}
</script>
@endsection

