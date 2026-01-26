@extends('layouts.app')

@section('title', 'Student Emails & Passwords - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="fas fa-envelope"></i> Student Emails & Passwords
                <small class="text-muted">- For Webmail Creation</small>
            </h2>
            <a href="{{ route('admin.erp.dashboard') }}" class="btn btn-secondary mb-3">
                <i class="fas fa-arrow-left"></i> Back to ERP Dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-info-circle"></i> Instructions
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-0">
                <strong>Purpose:</strong> This page displays all student emails and passwords for manual webmail account creation. 
                Use this information to create email accounts in your webmail system (e.g., cPanel, Plesk, etc.).
            </p>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Student Email Accounts</h5>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">Total: {{ $students->total() }} students</small>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Student Email</th>
                            <th>Password/PIN</th>
                            <th>Program</th>
                            <th>Department</th>
                            <th>Password Changed</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $index => $student)
                            <tr>
                                <td>{{ $students->firstItem() + $index }}</td>
                                <td><strong>{{ $student->student_id }}</strong></td>
                                <td>{{ $student->user->name }}</td>
                                <td>
                                    <code class="text-primary">{{ $student->user->email }}</code>
                                    <button class="btn btn-sm btn-outline-secondary ms-2" 
                                            onclick="copyToClipboard('{{ $student->user->email }}')"
                                            title="Copy email">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                                <td>
                                    @if($student->user->pin)
                                        <code class="text-success">{{ $student->user->pin }}</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" 
                                                onclick="copyToClipboard('{{ $student->user->pin }}')"
                                                title="Copy password">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>{{ $student->program->name ?? 'N/A' }}</td>
                                <td>{{ $student->department->name ?? 'N/A' }}</td>
                                <td>
                                    @if($student->user->password_changed_at)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Yes
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $student->user->password_changed_at->format('Y-m-d H:i') }}</small>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-exclamation-triangle"></i> No
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $student->academic_status === 'active' ? 'success' : 'warning' }}">
                                        {{ ucfirst($student->academic_status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No students found with SIP accounts.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($students->hasPages())
                <div class="mt-3">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-download"></i> Export Options
            </h5>
        </div>
        <div class="card-body">
            <p>You can export this data for bulk webmail creation:</p>
            <button class="btn btn-primary" onclick="exportToCSV()">
                <i class="fas fa-file-csv"></i> Export to CSV
            </button>
            <button class="btn btn-success" onclick="exportToJSON()">
                <i class="fas fa-file-code"></i> Export to JSON
            </button>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Copied to clipboard: ' + text);
    }, function(err) {
        console.error('Failed to copy: ', err);
    });
}

function exportToCSV() {
    const rows = [];
    rows.push(['Student ID', 'Name', 'Email', 'Password/PIN', 'Program', 'Department', 'Password Changed', 'Status']);
    
    @foreach($students as $student)
        rows.push([
            '{{ $student->student_id }}',
            '{{ $student->user->name }}',
            '{{ $student->user->email }}',
            '{{ $student->user->pin ?? "N/A" }}',
            '{{ $student->program->name ?? "N/A" }}',
            '{{ $student->department->name ?? "N/A" }}',
            '{{ $student->user->password_changed_at ? "Yes" : "No" }}',
            '{{ $student->academic_status }}'
        ]);
    @endforeach
    
    const csvContent = rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'student_emails_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportToJSON() {
    const data = [];
    
    @foreach($students as $student)
        data.push({
            student_id: '{{ $student->student_id }}',
            name: '{{ $student->user->name }}',
            email: '{{ $student->user->email }}',
            password: '{{ $student->user->pin ?? "" }}',
            program: '{{ $student->program->name ?? "N/A" }}',
            department: '{{ $student->department->name ?? "N/A" }}',
            password_changed: {{ $student->user->password_changed_at ? 'true' : 'false' }},
            status: '{{ $student->academic_status }}'
        });
    @endforeach
    
    const jsonContent = JSON.stringify(data, null, 2);
    const blob = new Blob([jsonContent], { type: 'application/json;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'student_emails_' + new Date().toISOString().split('T')[0] + '.json');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endsection

