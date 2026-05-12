@extends('layouts.app')

@section('content')
<div class="dashboard-wrap">
    <div class="dashboard-head">
        <h4 class="mb-0">Dashboard</h4>
        <small>Student Dashboard</small>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="hero-banner">
        <div>
            <p class="hero-greeting">Good Afternoon,</p>
            <h2>{{ explode(' ', Auth::user()->name)[0] }}!</h2>
            <p class="hero-subtext">Welcome to your Student Information Portal</p>
        </div>
        <div class="hero-icon" aria-hidden="true">
            <i class="fas fa-graduation-cap"></i>
        </div>
    </div>

    <div class="dashboard-shortcuts">
        <button class="shortcut-card active js-panel-btn" data-panel="login-info-panel" type="button">
            <span class="shortcut-icon shortcut-icon-blue"><i class="fas fa-key"></i></span>
            <span class="shortcut-text">
                <strong>Login Information</strong>
                <small>View your login details</small>
            </span>
            <i class="fas fa-chevron-right shortcut-arrow"></i>
        </button>
        <button class="shortcut-card js-panel-btn" data-panel="personal-info-panel" type="button">
            <span class="shortcut-icon shortcut-icon-green"><i class="fas fa-user"></i></span>
            <span class="shortcut-text">
                <strong>Personal Information</strong>
                <small>View your personal details</small>
            </span>
            <i class="fas fa-chevron-right shortcut-arrow"></i>
        </button>
        <button class="shortcut-card js-panel-btn" data-panel="application-info-panel" type="button">
            <span class="shortcut-icon shortcut-icon-purple"><i class="fas fa-file-alt"></i></span>
            <span class="shortcut-text">
                <strong>Application Information</strong>
                <small>View your application status</small>
            </span>
            <i class="fas fa-chevron-right shortcut-arrow"></i>
        </button>
    </div>

    <div id="login-info-panel" class="panel-view panel-active">
        <div class="info-panel">
            <div class="info-panel-header"><i class="fas fa-key me-2"></i> Your Login Information</div>
            <div class="info-panel-body">
                <div class="info-grid">
                    <div class="info-item">
                        <span>PIN (Password)</span>
                        <strong><code>{{ Auth::user()->pin ?? 'N/A' }}</code></strong>
                    </div>
                    <div class="info-item">
                        <span>Serial Number</span>
                        <strong><code>{{ Auth::user()->serial_number ?? 'N/A' }}</code></strong>
                    </div>
                    <div class="info-item">
                        <span>PIN Expires</span>
                        <strong class="{{ optional(Auth::user()->pin_expires_at)->isFuture() ? 'text-success' : 'text-danger' }}">
                            {{ optional(Auth::user()->pin_expires_at)->toDayDateTimeString() ?? 'N/A' }}
                        </strong>
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
        <div class="info-panel">
            <div class="info-panel-header"><i class="fas fa-user me-2"></i> Personal Information</div>
            <div class="info-panel-body">
                <div class="info-grid two-col">
                    <div class="info-item"><span>Name</span><strong>{{ Auth::user()->name }}</strong></div>
                    <div class="info-item"><span>Email</span><strong>{{ Auth::user()->email }}</strong></div>
                    <div class="info-item"><span>Phone</span><strong>{{ Auth::user()->phone ?? 'N/A' }}</strong></div>
                    <div class="info-item"><span>Nationality</span><strong>{{ Auth::user()->nationality ?? 'N/A' }}</strong></div>
                    <div class="info-item full-width">
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
        <div class="info-panel">
            <div class="info-panel-header"><i class="fas fa-info-circle me-2"></i> Application Information</div>
            <div class="info-panel-body">
                <div class="info-grid two-col">
                    <div class="info-item"><span>Application Number</span><strong>{{ $application->application_number ?? 'N/A' }}</strong></div>
                    <div class="info-item"><span>Academic Year</span><strong>{{ $application->academic_year ?? \App\Models\SiteSetting::currentAcademicYear() }}</strong></div>
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
.dashboard-wrap{padding:4px}
.dashboard-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.dashboard-head h4{margin:0;font-weight:700;color:#1f2937}
.dashboard-head small{color:#6b7280}

.hero-banner{
    display:flex;justify-content:space-between;align-items:center;gap:20px;
    margin-bottom:16px;padding:26px 24px;border-radius:14px;color:#fff;
    background:linear-gradient(120deg,#1e3a8a,#3758e0);
    box-shadow:0 10px 24px rgba(30,58,138,.25);
}
.hero-greeting{margin:0 0 4px;color:#dbeafe;font-size:16px}
.hero-banner h2{margin:0 0 6px;font-size:44px;line-height:1;font-weight:800}
.hero-subtext{margin:0;color:#e0e7ff;font-size:20px}
.hero-icon{font-size:70px;color:rgba(255,255,255,.85);padding-right:8px}

.dashboard-shortcuts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:16px}
.shortcut-card{
    border:1px solid #e7ebf3;border-radius:12px;background:#fff;padding:16px;
    display:flex;align-items:center;gap:12px;text-align:left;cursor:pointer;
    transition:all .2s ease;
}
.shortcut-card:hover{border-color:#c7d2fe;box-shadow:0 4px 14px rgba(15,23,42,.08)}
.shortcut-card.active{border-color:#4f46e5;box-shadow:0 0 0 3px rgba(79,70,229,.12)}
.shortcut-icon{width:42px;height:42px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
.shortcut-icon-blue{background:linear-gradient(145deg,#2563eb,#4f46e5)}
.shortcut-icon-green{background:linear-gradient(145deg,#10b981,#059669)}
.shortcut-icon-purple{background:linear-gradient(145deg,#7c3aed,#6d28d9)}
.shortcut-text{display:flex;flex-direction:column;min-width:0}
.shortcut-text strong{font-size:17px;color:#1f2937}
.shortcut-text small{color:#6b7280;font-size:14px}
.shortcut-arrow{margin-left:auto;color:#9ca3af}

.panel-view{display:none}
.panel-view.panel-active{display:block}
.info-panel{background:#fff;border:1px solid #e7ebf3;border-radius:14px;overflow:hidden}
.info-panel-header{
    padding:16px 20px;border-bottom:1px solid #edf1f6;font-weight:700;font-size:24px;
    color:#1f2937;display:flex;align-items:center
}
.info-panel-body{padding:18px 20px}
.info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;border-bottom:1px solid #edf1f6;padding-bottom:14px;margin-bottom:14px}
.info-grid.two-col{grid-template-columns:repeat(2,minmax(0,1fr))}
.info-item{padding:2px 14px;border-left:1px solid #edf1f6}
.info-item:first-child{border-left:0;padding-left:0}
.info-item.full-width{grid-column:1/-1;border-left:0;padding-left:0;padding-top:8px}
.info-item span{display:block;color:white;font-size:14px;margin-bottom:4px}
.info-item strong{font-size:18px;color:#111827;font-weight:700}

@media (max-width:1100px){
    .hero-banner h2{font-size:34px}
    .hero-subtext{font-size:16px}
    .info-panel-header{font-size:22px}
}
@media (max-width:900px){
    .dashboard-shortcuts{grid-template-columns:1fr}
    .hero-icon{display:none}
    .hero-banner h2{font-size:30px}
    .info-grid,.info-grid.two-col{grid-template-columns:1fr;gap:12px}
    .info-item{border-left:0;padding-left:0}
}
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

            links.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            panels.forEach(panel => panel.classList.remove('panel-active'));
            const target = document.getElementById(panelId);
            if (target) target.classList.add('panel-active');
        });
    });
});
</script>
@endsection

