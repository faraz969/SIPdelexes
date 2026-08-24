<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'DELEXES UNIVERSITY COLLEGE')</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    
    <!-- jQuery (required for Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/delexes-custom.css') }}" rel="stylesheet">
    
    @yield('head')
</head>
<body>
    @php
        $hideNavbar = trim((string) $__env->yieldContent('hide_navbar')) === '1'
            || (!Auth::check() && request()->routeIs('login', 'register', 'registration.create'));
    @endphp
    <div id="app">
        @if($hideNavbar)
            <main style="min-height: 100vh; min-height: 100dvh; margin: 0; padding: 0;">
                @yield('content')
            </main>
        @elseif(Auth::check())
            @php
                $user = Auth::user();
                $isStudent = !$user->isStaff() && !$user->isLecturer() && !$user->isBank();
                $hasSIPAccount = $isStudent ? \App\Models\Student::where('user_id', Auth::id())->where('sip_account_created', true)->exists() : false;
                $panelTitle = $isStudent ? 'Student Panel' : 'Admin Panel';
                $navActive = function (...$patterns) {
                    return request()->routeIs(...$patterns) ? 'active' : '';
                };
            @endphp

            <div class="app-shell">
                <header class="app-top">
                    <div class="app-top-title">
                        <strong>{{ ucfirst($user->role) }} Workspace</strong>
                        <small>{{ $panelTitle }}</small>
                    </div>
                    <div class="app-user">
                        <span class="app-user-badge"><i class="fas fa-user"></i></span>
                        <div class="app-user-meta">
                            <strong>{{ $user->name }}</strong>
                            <small>{{ ucfirst($user->role) }}</small>
                        </div>
                        <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();">Logout</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </div>
                </header>

                <div class="app-body">
                <aside class="app-sidebar">
                    <a class="sidebar-brand" href="{{ url('/') }}">
                        <img src="{{ asset('images/logo.png') }}" alt="DELEXES">
                        <div>
                            <strong>DELEXES</strong>
                            <small>{{ $panelTitle }}</small>
                        </div>
                    </a>

                    <nav class="sidebar-nav">
                        @if($isStudent)
                            @if($hasSIPAccount)
                                <a class="{{ $navActive('sip.*') }}" href="{{ route('sip.dashboard') }}"><i class="fas fa-graduation-cap"></i> SIP Portal</a>
                            @endif
                            <a class="{{ $navActive('portal.dashboard') }}" href="{{ route('portal.dashboard') }}"><i class="fas fa-gauge-high"></i> Dashboard</a>
                            <a class="{{ $navActive('portal.application', 'portal.application.save', 'portal.application.submit', 'portal.application.print', 'portal.waec.fetch') }}" href="{{ route('portal.application') }}"><i class="fas fa-file-lines"></i> My Application</a>
                            <a class="{{ $navActive('portal.results') }}" href="{{ route('portal.results') }}"><i class="fas fa-chart-line"></i> Application Status</a>
                        @elseif($user->isBank())
                            <a class="{{ $navActive('bank.*') }}" href="{{ route('bank.dashboard') }}"><i class="fas fa-building-columns"></i> Bank Dashboard</a>
                        @elseif($user->isLecturer())
                            <a class="{{ $navActive('lecturer.*') }}" href="{{ route('lecturer.dashboard') }}"><i class="fas fa-chalkboard-teacher"></i> Lecturer Dashboard</a>
                        @else
                            <div class="sidebar-group-label">DASHBOARD</div>
                            @if($user->isHOD())
                                <a class="{{ $navActive('hod.dashboard') }}" href="{{ route('hod.dashboard') }}"><i class="fas fa-gauge-high"></i> HOD Dashboard</a>
                                <div class="sidebar-group-label">APPLICATIONS</div>
                                <a class="{{ $navActive('hod.applications.pending') }}" href="{{ route('hod.applications.pending') }}"><i class="fas fa-clock"></i> Pending Applications</a>
                                <a class="{{ $navActive('hod.applications.approved') }}" href="{{ route('hod.applications.approved') }}"><i class="fas fa-check-circle"></i> Approved Applications</a>
                                <a class="{{ $navActive('hod.applications.rejected') }}" href="{{ route('hod.applications.rejected') }}"><i class="fas fa-times-circle"></i> Rejected Applications</a>
                                <div class="sidebar-group-label">COURSES</div>
                                <a class="{{ request()->routeIs('hod.courses.*') && !request()->routeIs('hod.courses.create') ? 'active' : '' }}" href="{{ route('hod.courses.index') }}"><i class="fas fa-book"></i> Manage Courses</a>
                                <a class="{{ $navActive('hod.courses.create') }}" href="{{ route('hod.courses.create') }}"><i class="fas fa-plus"></i> Add Course</a>
                                <a class="{{ $navActive('hod.semester-offerings*') }}" href="{{ route('hod.semester-offerings.index') }}"><i class="fas fa-clipboard-list"></i> Semester Packages</a>
                                <a class="{{ $navActive('hod.course-enrollments*') }}" href="{{ route('hod.course-enrollments') }}"><i class="fas fa-users"></i> Course Enrollments</a>
                            @elseif($user->isPresident())
                                <a class="{{ $navActive('president.*') }}" href="{{ route('president.dashboard') }}"><i class="fas fa-crown"></i> President Dashboard</a>
                            @elseif($user->isRegistrar())
                                <a class="{{ $navActive('registrar.dashboard') }}" href="{{ route('registrar.dashboard') }}"><i class="fas fa-gauge-high"></i> Registrar Dashboard</a>
                                <div class="sidebar-group-label">APPLICATIONS</div>
                                <a class="{{ $navActive('registrar.applications.pending') }}" href="{{ route('registrar.applications.pending') }}"><i class="fas fa-clock"></i> Pending Applications</a>
                                <a class="{{ $navActive('registrar.applications.approved') }}" href="{{ route('registrar.applications.approved') }}"><i class="fas fa-check-circle"></i> Approved Applications</a>
                                <a class="{{ $navActive('registrar.applications.rejected') }}" href="{{ route('registrar.applications.rejected') }}"><i class="fas fa-times-circle"></i> Rejected Applications</a>
                                <div class="sidebar-group-label">COURSES</div>
                                <a class="{{ $navActive('registrar.semester-offerings*') }}" href="{{ route('registrar.semester-offerings.index') }}"><i class="fas fa-clipboard-list"></i> Semester Packages</a>
                                <a class="{{ $navActive('registrar.course-enrollments*') }}" href="{{ route('registrar.course-enrollments') }}"><i class="fas fa-users"></i> Course Enrollments</a>
                                <div class="sidebar-group-label">DEFERMENTS</div>
                                <a class="{{ $navActive('registrar.deferments*') }}" href="{{ route('registrar.deferments') }}"><i class="fas fa-pause-circle"></i> Deferments</a>
                            @else
                                <a class="{{ $navActive('admin.dashboard', 'admin.applications.*') }}" href="{{ route('admin.dashboard') }}"><i class="fas fa-gauge-high"></i> Admin Dashboard</a>
                                <div class="sidebar-group-label">MANAGE</div>
                                <a class="{{ request()->routeIs('admin.departments.*') && !request()->routeIs('admin.departments.create') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}"><i class="fas fa-building"></i> Departments</a>
                                <a class="{{ request()->routeIs('admin.programs.*') && !request()->routeIs('admin.programs.create') ? 'active' : '' }}" href="{{ route('admin.programs.index') }}"><i class="fas fa-graduation-cap"></i> Programs</a>
                                <a class="{{ request()->routeIs('admin.courses.*') && !request()->routeIs('admin.courses.create') ? 'active' : '' }}" href="{{ route('admin.courses.index') }}"><i class="fas fa-book"></i> Courses</a>
                                <a class="{{ request()->routeIs('admin.lecturers.*') && !request()->routeIs('admin.lecturers.create') ? 'active' : '' }}" href="{{ route('admin.lecturers.index') }}"><i class="fas fa-chalkboard-teacher"></i> Lecturers</a>
                                <a class="{{ request()->routeIs('admin.users.*') && !request()->routeIs('admin.users.create') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="fas fa-users"></i> Users</a>
                                <a class="{{ request()->routeIs('admin.form-types.*') && !request()->routeIs('admin.form-types.create') ? 'active' : '' }}" href="{{ route('admin.form-types.index') }}"><i class="fas fa-file-alt"></i> Form Types</a>
                                <a class="{{ $navActive('waec.*') }}" href="{{ route('waec.search') }}"><i class="fas fa-file-signature"></i> WAEC Lookup</a>

                                <div class="sidebar-group-label">QUICK ADD</div>
                                <a class="{{ $navActive('admin.departments.create') }}" href="{{ route('admin.departments.create') }}"><i class="fas fa-plus"></i> Add Department</a>
                                <a class="{{ $navActive('admin.programs.create') }}" href="{{ route('admin.programs.create') }}"><i class="fas fa-plus"></i> Add Program</a>
                                <a class="{{ $navActive('admin.courses.create') }}" href="{{ route('admin.courses.create') }}"><i class="fas fa-plus"></i> Add Course</a>
                                <a class="{{ $navActive('admin.users.create') }}" href="{{ route('admin.users.create') }}"><i class="fas fa-user-plus"></i> Add User</a>
                                <a class="{{ $navActive('admin.form-types.create') }}" href="{{ route('admin.form-types.create') }}"><i class="fas fa-plus"></i> Add Form Type</a>

                                <div class="sidebar-group-label">SYSTEM</div>
                                <a class="{{ request()->routeIs('admin.erp.*') && !request()->routeIs('admin.erp.student-emails') ? 'active' : '' }}" href="{{ route('admin.erp.dashboard') }}"><i class="fas fa-cogs"></i> ERP Management</a>
                                <a class="{{ $navActive('admin.admission-payments.*') }}" href="{{ route('admin.admission-payments.index') }}"><i class="fas fa-receipt"></i> Admission Payments</a>
                                <a class="{{ $navActive('admin.bank-payment-slips.*') }}" href="{{ route('admin.bank-payment-slips.index') }}"><i class="fas fa-file-invoice"></i> Bank Payment Slips</a>
                                <a class="{{ $navActive('admin.erp.student-emails') }}" href="{{ route('admin.erp.student-emails') }}"><i class="fas fa-envelope"></i> Student Emails & Passwords</a>
                                <a class="{{ $navActive('admin.registration-rules.*') }}" href="{{ route('admin.registration-rules.index') }}"><i class="fas fa-ruler-combined"></i> Registration Rules</a>
                                <a class="{{ $navActive('admin.admission-form-settings.*') }}" href="{{ route('admin.admission-form-settings.edit') }}"><i class="fas fa-sliders"></i> Admission Form Defaults</a>

                                <div class="sidebar-group-label">ACADEMIC SETUP</div>
                                <a class="{{ $navActive('admin.academic-year-settings.*') }}" href="{{ route('admin.academic-year-settings.edit') }}"><i class="fas fa-calendar-check"></i> Academic Year</a>
                                <a class="{{ $navActive('admin.sessions.*') }}" href="{{ route('admin.sessions.index') }}"><i class="fas fa-clock"></i> Sessions</a>
                                <a class="{{ $navActive('admin.campuses.*') }}" href="{{ route('admin.campuses.index') }}"><i class="fas fa-school"></i> Campuses</a>
                                <a class="{{ $navActive('admin.intakes.*') }}" href="{{ route('admin.intakes.index') }}"><i class="fas fa-calendar"></i> Intakes</a>
                            @endif
                        @endif
                    </nav>
                </aside>

                <div class="app-main">
                    <main class="app-content">
                        <div class="app-content-inner">
                            @yield('content')
                        </div>
                    </main>
                </div>
                </div>
            </div>
        @else
            <nav class="navbar navbar-expand-md navbar-dark shadow-sm" style="background-color: #1e3a8a;">
                <div class="container">
                    <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                        <img src="{{ asset('images/logo.png') }}" alt="DELEXES UNIVERSITY COLLEGE" height="50" class="me-3">
                        <div class="fw-bold text-white">DELEXES UNIVERSITY COLLEGE</div>
                    </a>
                    <div class="navbar-nav ms-auto">
                        @if (Route::has('login'))
                            <a class="nav-link text-white" href="{{ route('login') }}">{{ __('Login') }}</a>
                        @endif
                        @if (Route::has('registration.create'))
                            <a class="nav-link text-white" href="{{ route('registration.create') }}">{{ __('Register') }}</a>
                        @endif
                    </div>
                </div>
            </nav>
            <main style="min-height: calc(100vh - 76px);">
                @yield('content')
            </main>
        @endif
    </div>

    <style>
        .app-shell{display:flex;flex-direction:column;height:100vh;background:#f3f5f9;overflow:hidden}
        .app-top{height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 20px;border-bottom:1px solid rgba(148,163,184,.2);background:rgba(15,23,42,.92);color:#e2e8f0}
        .app-top-title{display:flex;flex-direction:column;line-height:1.15}
        .app-top-title strong{font-size:15px}
        .app-top-title small{font-size:12px;color:#93c5fd}
        .app-user{display:flex;align-items:center;gap:10px}
        .app-user-badge{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:rgba(59,130,246,.22);color:#93c5fd}
        .app-user-meta{display:flex;flex-direction:column;line-height:1.1}
        .app-user-meta strong{font-size:13px;color:#f1f5f9}
        .app-user-meta small{font-size:11px;color:#94a3b8}
        .app-user a{color:#93c5fd;text-decoration:none;font-size:13px;font-weight:600}
        .app-body{display:grid;grid-template-columns:250px 1fr;gap:14px;flex:1;min-height:0;padding:14px}
        .app-sidebar{background:rgba(2,6,23,.92);border:1px solid rgba(148,163,184,.2);border-radius:14px;padding:14px;height:100%;overflow-y:auto;box-shadow:0 8px 24px rgba(2,6,23,.12)}
        .sidebar-brand{display:flex;gap:10px;align-items:center;text-decoration:none;color:#fff;padding:8px 10px;margin-bottom:8px}
        .sidebar-brand img{width:36px;height:36px;object-fit:contain}
        .sidebar-brand small{display:block;color:#93c5fd;font-size:11px}
        .sidebar-nav{display:flex;flex-direction:column;gap:4px}
        .sidebar-group-label{font-size:11px;letter-spacing:.08em;color:#93a4c0;padding:10px 10px 4px;font-weight:700}
        .sidebar-nav a{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:10px;color:#cbd5e1;text-decoration:none;font-weight:600}
        .sidebar-nav a i{width:18px;text-align:center;color:#94a3b8}
        .sidebar-nav a:hover{background:rgba(148,163,184,.16);color:#fff}
        .sidebar-nav a:hover i{color:#fff}
        .sidebar-nav a.active{background:rgba(59,130,246,.22);color:#fff;box-shadow:inset 3px 0 0 #3b82f6}
        .sidebar-nav a.active i{color:#93c5fd}
        .app-main{display:flex;flex-direction:column;min-width:0;min-height:0;overflow:hidden}
        .app-content{overflow-y:auto;min-height:0}
        .app-content-inner{min-height:100%;background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:16px}
        @media (max-width:992px){.app-shell{height:auto;overflow:visible}.app-body{grid-template-columns:1fr;height:auto;overflow:visible}.app-sidebar{height:auto;overflow:visible}.app-main{min-height:0;overflow:visible}.app-content{height:auto;overflow:visible}.app-content-inner{min-height:auto}}
    </style>
    @stack('scripts')
</body>
</html>
