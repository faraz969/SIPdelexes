@extends('layouts.app')

@section('title', 'Registration Rules - Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-rules"></i> Registration Rules</h2>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="{{ route('admin.registration-rules.create') }}" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> Create Rule</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($rules->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No registration rules found. Create one to enable course registration controls.
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rule Name</th>
                                <th>Min Payment %</th>
                                <th>Late Fee</th>
                                <th>Late Days</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rules as $rule)
                                <tr>
                                    <td><strong>{{ $rule->rule_name }}</strong></td>
                                    <td>{{ number_format($rule->minimum_payment_percentage, 1) }}%</td>
                                    <td>GHS {{ number_format($rule->late_registration_fee, 2) }}</td>
                                    <td>{{ $rule->late_registration_days }} days</td>
                                    <td>
                                        @if($rule->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.registration-rules.edit', $rule->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.registration-rules.destroy', $rule->id) }}" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

