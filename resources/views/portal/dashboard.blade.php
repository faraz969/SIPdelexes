@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Dashboard</h4>
        <small class="text-muted">Student Dashboard</small>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="welcome-banner mb-3">
        <h2>Good Afternoon, {{ explode(' ', Auth::user()->name)[0] }}!</h2>
        <p>Welcome to your Student Information Portal</p>
    </div>

    <div class="mb-3 d-flex flex-wrap gap-2">
        <button class="btn btn-sm btn-primary js-panel-btn active" data-panel="login-info-panel"><i class="fas fa-key me-1"></i> Login Information</button>
        <button class="btn btn-sm btn-outline-primary js-panel-btn" data-panel="personal-info-panel"><i class="fas fa-user me-1"></i> Personal Information</button>
        <button class="btn btn-sm btn-outline-primary js-panel-btn" data-panel="application-info-panel"><i class="fas fa-info-circle me-1"></i> Application Information</button>
    </div>

    <div id="login-info-panel" class="panel-view panel-active">
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-key me-2"></i> Your Login Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>PIN (Password):</strong> <code>{{ Auth::user()->pin ?? 'N/A' }}</code></div>
                    <div class="col-md-4"><strong>Serial Number:</strong> <code>{{ Auth::user()->serial_number ?? 'N/A' }}</code></div>
                    <div class="col-md-4"><strong>PIN Expires:</strong>
                        <span class="badge {{ optional(Auth::user()->pin_expires_at)->isFuture() ? 'bg-success' : 'bg-danger' }}">
                            {{ optional(Auth::user()->pin_expires_at)->toDayDateTimeString() ?? 'N/A' }}
                        </span>
                    </div>
                </div>
                @if(Auth::user()->serial_number)
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Login Options:</strong> You can login with your email or serial number: <code>{{ Auth::user()->serial_number }}</code>
                    </div>
                @endif
                @if(Auth::user()->pin_expires_at && Auth::user()->pin_expires_at->isPast())
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Your PIN has expired!</strong> Please contact administration for a new PIN.
                    </div>
                @elseif(Auth::user()->pin_expires_at && Auth::user()->pin_expires_at->diffInDays() <= 7)
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>PIN expires soon!</strong> Your PIN will expire in {{ Auth::user()->pin_expires_at->diffInDays() }} days.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="personal-info-panel" class="panel-view">
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-user me-2"></i> Personal Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>Name:</strong> {{ Auth::user()->name }}</div>
                    <div class="col-md-6"><strong>Email:</strong> {{ Auth::user()->email }}</div>
                    <div class="col-md-6"><strong>Phone:</strong> {{ Auth::user()->phone ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Nationality:</strong> {{ Auth::user()->nationality ?? 'N/A' }}</div>
                    <div class="col-md-12">
                        <strong>Form Type:</strong>
                        @if(Auth::user()->formType)
                            <span class="badge bg-primary">{{ Auth::user()->formType->name }}</span>
                        @else
                            <span class="text-muted">No form type selected</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="application-info-panel" class="panel-view">
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i> Application Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>Application Number:</strong> {{ $application->application_number ?? 'N/A' }}</div>
                    <div class="col-md-6"><strong>Academic Year:</strong> {{ $application->academic_year ?? '2025/2026' }}</div>
                </div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <a href="{{ route('portal.application') }}" class="btn btn-primary">My Application</a>
                    <a href="{{ route('portal.results') }}" class="btn btn-outline-secondary">Application Results</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.welcome-banner{background:linear-gradient(90deg,#ef233c 0 8px,#111827 8px 100%);border-radius:8px;padding:14px 16px;color:#fff}
.welcome-banner h2{margin:0 0 6px;font-size:40px;font-weight:800}
.welcome-banner p{margin:0;color:#e5e7eb}
.panel-view{display:none}
.panel-view.panel-active{display:block}
@media (max-width: 900px){.welcome-banner h2{font-size:30px}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('.js-panel-btn');
    const panels = document.querySelectorAll('.panel-view');

    links.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const panelId = this.dataset.panel;
            if (!panelId) return;

            links.forEach(l => {
                l.classList.remove('active', 'btn-primary');
                l.classList.add('btn-outline-primary');
            });
            this.classList.add('active', 'btn-primary');
            this.classList.remove('btn-outline-primary');

            panels.forEach(panel => panel.classList.remove('panel-active'));
            const target = document.getElementById(panelId);
            if (target) target.classList.add('panel-active');
        });
    });
});
</script>
@endsection

