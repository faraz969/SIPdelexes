@extends('layouts.app')

@section('title', 'Students - ERP Admin')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-users"></i> Students</h2>
            <a href="{{ route('admin.erp.dashboard') }}" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left"></i> Back to ERP Dashboard</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Academic Year</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td><strong>{{ $student->student_id }}</strong></td>
                                <td>{{ $student->user->name }}</td>
                                <td>{{ $student->user->email }}</td>
                                <td>{{ $student->program->name ?? 'N/A' }}</td>
                                <td>{{ $student->academic_year }}</td>
                                <td>
                                    <span class="badge bg-{{ $student->academic_status === 'active' ? 'success' : 'warning' }}">
                                        {{ ucfirst($student->academic_status) }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-{{ $student->getTotalBalance() > 0 ? 'danger' : 'success' }}">
                                        GHS {{ number_format($student->getTotalBalance(), 2) }}
                                    </strong>
                                </td>
                                <td>
                                    <a href="{{ route('admin.erp.students.show', $student->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $students->links() }}
        </div>
    </div>
</div>
@endsection

