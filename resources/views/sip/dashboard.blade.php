@extends('layouts.app')

@section('title', 'SIP Dashboard - Student Information Portal')

@section('content')
<div class="sip-dashboard">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <section class="sip-hero">
        <div>
            <h2>Welcome back, {{ $student->user->name }}</h2>
            <p>Student Information Portal</p>
        </div>
        <div class="sip-hero-icon"><i class="fas fa-graduation-cap"></i></div>
    </section>

    <section class="sip-kpi-grid">
        <article class="sip-kpi-card kpi-blue">
            <div class="kpi-head"><i class="fas fa-file-invoice-dollar"></i> Total Balance</div>
            <div class="kpi-value">GHS {{ number_format($stats['total_balance'], 2) }}</div>
            <a href="{{ route('sip.payments.invoices') }}" class="kpi-link">View Invoices <i class="fas fa-arrow-right ms-1"></i></a>
        </article>
        <article class="sip-kpi-card kpi-cyan">
            <div class="kpi-head"><i class="fas fa-percentage"></i> Payment %</div>
            <div class="kpi-value">{{ number_format($stats['payment_percentage'], 1) }}%</div>
            <a href="{{ route('sip.payments.history') }}" class="kpi-link">View Payments <i class="fas fa-arrow-right ms-1"></i></a>
        </article>
        <article class="sip-kpi-card {{ $stats['can_register'] ? 'kpi-amber-soft' : 'kpi-amber' }}">
            <div class="kpi-head"><i class="fas fa-book"></i> Course Registration</div>
            <div class="kpi-value">{{ $stats['can_register'] ? 'Enabled' : 'Blocked' }}</div>
            <a href="{{ route('sip.course-registration.show') }}" class="kpi-link">View Details <i class="fas fa-arrow-right ms-1"></i></a>
        </article>
        <article class="sip-kpi-card {{ $stats['can_generate_exam_pin'] ? 'kpi-green' : 'kpi-red' }}">
            <div class="kpi-head"><i class="fas fa-key"></i> Exam PIN</div>
            <div class="kpi-value">{{ $stats['can_generate_exam_pin'] ? 'Available' : 'Not Available' }}</div>
            <a href="{{ route('sip.exam.pins') }}" class="kpi-link">Generate PIN <i class="fas fa-arrow-right ms-1"></i></a>
        </article>
    </section>

    <section class="sip-feature-grid">
        <article class="sip-feature-card">
            <div class="feature-title"><i class="fas fa-user-circle icon-blue"></i><span>Student Profile</span><i class="fas fa-chevron-right ms-auto"></i></div>
            <p>View your biodata, programme, and academic status</p>
            <a href="{{ route('sip.profile') }}" class="btn btn-outline-primary">View Profile</a>
        </article>
        <article class="sip-feature-card">
            <div class="feature-title"><i class="fas fa-book-reader icon-cyan"></i><span>Academic Records</span><i class="fas fa-chevron-right ms-auto"></i></div>
            <p>View registered courses, results, and GPA</p>
            <a href="{{ route('sip.academic-records') }}" class="btn btn-outline-info">View Records</a>
        </article>
        <article class="sip-feature-card">
            <div class="feature-title"><i class="fas fa-download icon-green"></i><span>Downloads</span><i class="fas fa-chevron-right ms-auto"></i></div>
            <p>Download admission letter, receipts, and exam slips</p>
            <a href="{{ route('sip.downloads') }}" class="btn btn-outline-success">View Downloads</a>
        </article>
        <article class="sip-feature-card">
            <div class="feature-title"><i class="fas fa-credit-card icon-amber"></i><span>Payment</span><i class="fas fa-chevron-right ms-auto"></i></div>
            <p>View invoices and make payments</p>
            <a href="{{ route('sip.payments.invoices') }}" class="btn btn-outline-warning">Make Payment</a>
        </article>
        <article class="sip-feature-card">
            <div class="feature-title"><i class="fas fa-book icon-purple"></i><span>Course Registration</span><i class="fas fa-chevron-right ms-auto"></i></div>
            <p>Register for courses</p>
            <a href="{{ route('sip.course-registration.show') }}" class="btn btn-outline-secondary">Register Courses</a>
        </article>
        <article class="sip-feature-card">
            <div class="feature-title"><i class="fas fa-key icon-red"></i><span>Exam PIN</span><i class="fas fa-chevron-right ms-auto"></i></div>
            <p>Generate exam PIN</p>
            <a href="{{ route('sip.exam.pins') }}" class="btn btn-outline-danger">Generate PIN</a>
        </article>
        <article class="sip-feature-card">
            <div class="feature-title"><i class="fas fa-pause-circle icon-slate"></i><span>Deferment</span><i class="fas fa-chevron-right ms-auto"></i></div>
            <p>Request deferment or view status</p>
            <a href="{{ route('sip.deferment.form') }}" class="btn btn-outline-dark">Manage Deferment</a>
        </article>
    </section>

    <section class="sip-student-info">
        <div class="student-info-head"><i class="fas fa-info-circle me-2"></i> Student Information</div>
        <div class="student-info-grid">
            <div>
                <p><strong>Student ID:</strong> {{ $student->student_id }}</p>
                <p><strong>Program:</strong> {{ $student->program->name ?? 'N/A' }}</p>
                <p><strong>Academic Year:</strong> {{ $student->academic_year }}</p>
            </div>
            <div>
                <p><strong>Academic Status:</strong>
                    <span class="badge bg-{{ $student->academic_status === 'active' ? 'success' : 'warning' }}">
                        {{ ucfirst($student->academic_status) }}
                    </span>
                </p>
                <p><strong>Admission Date:</strong> {{ $student->admission_date->format('d M Y') }}</p>
            </div>
        </div>
    </section>
</div>

<style>
.sip-dashboard{padding:8px}
.sip-hero{
    margin-bottom:16px;padding:5px 11px;
    background: linear-gradient(120deg, #1e3a8a, #3758e0);
    box-shadow: 0 10px 24px rgba(30, 58, 138, .25);
    border-radius:14px;
    display:flex;align-items:center;justify-content:space-between;gap:20px;
    color:#fff;
}
.sip-hero h2{margin:0;font-size:37px;font-weight:800;}
.sip-hero p{margin:6px 0 0;font-size:16px}
.sip-hero-icon{font-size:72px;opacity:.9}

.sip-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:16px}
.sip-kpi-card{
    border:1px solid #e8edf5;border-radius:14px;padding:14px;background:#fff;
    display:flex;flex-direction:column;gap:8px;
}
.kpi-head{font-size:18px;font-weight:700;display:flex;align-items:center;gap:8px}
.kpi-value{font-size:16px;font-weight:800;line-height:1.1}
.kpi-link{
    margin-top:auto;display:inline-flex;align-items:center;justify-content:center;
    border:1px solid currentColor;border-radius:10px;padding:8px 12px;text-decoration:none;font-weight:700
}
.kpi-blue{background:#f5f8ff;color:#2563eb}
.kpi-cyan{background:#eefcff;color:#0891b2}
.kpi-amber,.kpi-amber-soft{background:#fff9ed;color:#b45309}
.kpi-green{background:#f0fdf4;color:#15803d}
.kpi-red{background:#fff1f2;color:#dc2626}

.sip-feature-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:16px}
.sip-feature-card{
    border:1px solid #e8edf5;border-radius:14px;padding:10px;
    display:flex;flex-direction:column;gap:10px;min-height:180px;
}
.feature-title{display:flex;align-items:center;gap:10px;font-weight:800;font-size:21px;color:#0f172a}
.feature-title i:first-child{
    width:46px;height:46px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:20px
}
.sip-feature-card p{margin:0;color:#64748b;font-size:15px;line-height:1.35;margin-left:17%}
.sip-feature-card .btn{align-self:flex-start;margin-top:auto;font-weight:700;border-radius:10px;margin-left:17%}

.icon-blue{background:#3b82f6}
.icon-cyan{background:#06b6d4}
.icon-green{background:#22c55e}
.icon-amber{background:#f59e0b}
.icon-purple{background:#8b5cf6}
.icon-red{background:#ef4444}
.icon-slate{background:#64748b}

.sip-student-info{background:#fff;border:1px solid #e8edf5;border-radius:14px;overflow:hidden}
.student-info-head{padding:6px 16px;border-bottom:1px solid #eef2f7;font-size:18px;font-weight:800;color:#0f172a}
.student-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:14px 16px}
.student-info-grid p{margin:0 0 8px;color:#334155;font-size:16px}
.student-info-grid strong{color:#0f172a}

@media (max-width:1200px){
    .sip-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .sip-feature-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .sip-hero h2{font-size:38px}
    .sip-hero p{font-size:20px}
    .kpi-value{font-size:30px}
    .feature-title{font-size:22px}
    .sip-feature-card p,.student-info-grid p{font-size:16px}
}
@media (max-width:768px){
    .sip-kpi-grid,.sip-feature-grid,.student-info-grid{grid-template-columns:1fr}
    .sip-hero{padding:16px}
    .sip-hero h2{font-size:28px}
    .sip-hero p{font-size:16px}
    .sip-hero-icon{display:none}
}
</style>
@endsection

