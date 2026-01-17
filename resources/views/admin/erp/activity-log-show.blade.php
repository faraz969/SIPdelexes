@extends('layouts.app')

@section('title', 'Activity Log Details - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-history"></i> Activity Log Details</h2>
            <a href="{{ route('admin.erp.activity-logs') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Logs</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Log Entry #{{ $activityLog->id }}</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Date & Time:</th>
                            <td>{{ $activityLog->created_at->format('d M Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>User:</th>
                            <td>{{ $activityLog->user->name ?? 'System' }}</td>
                        </tr>
                        <tr>
                            <th>Role:</th>
                            <td><span class="badge bg-secondary">{{ $activityLog->role ?? 'N/A' }}</span></td>
                        </tr>
                        <tr>
                            <th>Action:</th>
                            <td><strong>{{ $activityLog->action }}</strong></td>
                        </tr>
                        <tr>
                            <th>System Source:</th>
                            <td>
                                <span class="badge bg-{{ $activityLog->system_source === 'SIP' ? 'info' : ($activityLog->system_source === 'ERP' ? 'success' : 'warning') }}">
                                    {{ $activityLog->system_source }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>IP Address:</th>
                            <td>{{ $activityLog->ip_address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Model Type:</th>
                            <td>{{ $activityLog->model_type ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Model ID:</th>
                            <td>{{ $activityLog->model_id ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td>{{ $activityLog->description ?? 'N/A' }}</td>
                        </tr>
                    </table>

                    @if($activityLog->old_value || $activityLog->new_value)
                        <hr>
                        <h6>Value Changes:</h6>
                        <div class="row">
                            @if($activityLog->old_value)
                                <div class="col-md-6">
                                    <strong>Old Value:</strong>
                                    <pre class="bg-light p-2 rounded">{{ $activityLog->old_value }}</pre>
                                </div>
                            @endif
                            @if($activityLog->new_value)
                                <div class="col-md-6">
                                    <strong>New Value:</strong>
                                    <pre class="bg-light p-2 rounded">{{ $activityLog->new_value }}</pre>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($activityLog->metadata)
                        <hr>
                        <h6>Metadata:</h6>
                        <pre class="bg-light p-2 rounded">{{ json_encode($activityLog->metadata, JSON_PRETTY_PRINT) }}</pre>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

