@extends('layouts.app')

@section('title', 'Activity Logs - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-history"></i> Activity Logs</h2>
            <a href="{{ route('admin.erp.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to ERP Dashboard</a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h6>Total Logs</h6>
                    <h4>{{ $stats['total'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h6>SIP</h6>
                    <h4>{{ $stats['sip'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6>ERP</h6>
                    <h4>{{ $stats['erp'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h6>API</h6>
                    <h4>{{ $stats['api'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.erp.activity-logs') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">System Source</label>
                        <select class="form-select" name="system_source">
                            <option value="">All</option>
                            <option value="SIP" {{ request('system_source') == 'SIP' ? 'selected' : '' }}>SIP</option>
                            <option value="ERP" {{ request('system_source') == 'ERP' ? 'selected' : '' }}>ERP</option>
                            <option value="API" {{ request('system_source') == 'API' ? 'selected' : '' }}>API</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Action</label>
                        <input type="text" class="form-control" name="action" value="{{ request('action') }}" placeholder="Search action...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>System</th>
                            <th>IP Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td><span class="badge bg-secondary">{{ $log->role ?? 'N/A' }}</span></td>
                                <td>{{ $log->action }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->system_source === 'SIP' ? 'info' : ($log->system_source === 'ERP' ? 'success' : 'warning') }}">
                                        {{ $log->system_source }}
                                    </span>
                                </td>
                                <td><small>{{ $log->ip_address ?? 'N/A' }}</small></td>
                                <td>
                                    <a href="{{ route('admin.erp.activity-logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection

