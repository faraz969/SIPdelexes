@extends('layouts.app')

@section('content')
@php
    $timeline = $application
        ? $application->getApplicationTimeline()
        : [
            ['key' => 'pending_submission', 'label' => 'Pending Submission', 'icon' => 'fas fa-edit', 'state' => 'current'],
            ['key' => 'submitted', 'label' => 'Submitted', 'icon' => 'fas fa-paper-plane', 'state' => 'pending'],
            ['key' => 'hod', 'label' => 'HOD Review Pending', 'icon' => 'fas fa-hourglass-half', 'state' => 'pending'],
            ['key' => 'registrar', 'label' => 'Registrar Review Pending', 'icon' => 'fas fa-user-tie', 'state' => 'pending'],
            ['key' => 'admitted', 'label' => 'Admitted', 'icon' => 'fas fa-graduation-cap', 'state' => 'pending'],
        ];

    $completedCount = 0;
    $currentIndex = 0;
    foreach ($timeline as $i => $step) {
        if ($step['state'] === 'completed') {
            $completedCount = $i + 1;
        }
        if (in_array($step['state'], ['current', 'rejected'], true)) {
            $currentIndex = $i;
        }
    }
    if ($completedCount === count($timeline)) {
        $currentIndex = count($timeline) - 1;
    }

    $stepCount = count($timeline);
    $progressPercent = $stepCount > 1
        ? (($currentIndex + ($timeline[$currentIndex]['state'] === 'completed' ? 1 : 0.25)) / ($stepCount - 1)) * 100
        : 0;
    $progressPercent = min(100, max(0, $progressPercent));

    $completedColors = ['#1e3a8a', '#16a34a', '#0f766e', '#7c3aed', '#0f766e'];
@endphp

<div class="container py-4">
    <h3 class="mb-4">Application Status</h3>

    <div class="timeline-card">
        <h4 class="timeline-title">Application Timelines</h4>

        <div class="app-timeline">
            <div class="timeline-track">
                <div class="timeline-track-fill" style="width: {{ $progressPercent }}%;"></div>
            </div>

            @foreach($timeline as $index => $step)
                <div class="timeline-step timeline-{{ $step['state'] }}">
                    <div class="timeline-node" style="{{ $step['state'] === 'completed' ? 'background:' . ($completedColors[$index] ?? '#1e3a8a') : '' }}">
                        <i class="{{ $step['icon'] }}"></i>
                    </div>
                    <div class="timeline-label">{{ $step['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    @if($application)
        <div class="status-summary mt-4">
            <p class="mb-1"><strong>Application #:</strong> {{ $application->application_number }}</p>
            <p class="mb-0">
                <strong>Current status:</strong>
                {{ $application->status_display }}
            </p>
            @if($application->isRejected())
                <div class="alert alert-danger mt-3 mb-0">
                    Your application was not successful at this stage. Please contact the admissions office for more information.
                </div>
            @elseif($application->registrar_status === 'approved')
                <div class="alert alert-success mt-3 mb-0">
                    Congratulations! You have been admitted. Check your email/SMS for SIP login details.
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-warning mt-4">No application found. Complete and submit your application to move past Pending Submission.</div>
    @endif
</div>

<style>
    .timeline-card {
        background: #f7f8fa;
        border-radius: 12px;
        padding: 28px 20px 36px;
        box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04);
    }

    .timeline-title {
        text-align: center;
        font-size: 1.35rem;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 36px;
    }

    .app-timeline {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        max-width: 980px;
        margin: 0 auto;
        padding: 0 10px;
    }

    .timeline-track {
        position: absolute;
        top: 26px;
        left: 8%;
        right: 8%;
        height: 4px;
        background: #d7dbe3;
        z-index: 0;
        border-radius: 4px;
    }

    .timeline-track-fill {
        height: 100%;
        background: #1e3a8a;
        border-radius: 4px;
        transition: width 0.35s ease;
    }

    .timeline-step {
        position: relative;
        z-index: 1;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .timeline-node {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: #c5c9d1;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
    }

    .timeline-label {
        margin-top: 12px;
        font-size: 0.92rem;
        color: #6b7280;
        max-width: 140px;
        line-height: 1.3;
    }

    .timeline-current .timeline-node {
        background: #f4c430;
        width: 58px;
        height: 58px;
        box-shadow: 0 0 0 10px rgba(209, 213, 219, 0.55);
        font-size: 20px;
    }

    .timeline-current .timeline-label {
        color: #374151;
        font-weight: 600;
    }

    .timeline-completed .timeline-label {
        color: #4b5563;
        font-weight: 500;
    }

    .timeline-rejected .timeline-node {
        background: #dc2626;
        box-shadow: 0 0 0 10px rgba(220, 38, 38, 0.15);
    }

    .timeline-rejected .timeline-label {
        color: #dc2626;
        font-weight: 600;
    }

    .status-summary {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 16px 20px;
    }

    @media (max-width: 768px) {
        .app-timeline {
            flex-direction: column;
            align-items: stretch;
            padding-left: 18px;
        }

        .timeline-track {
            top: 0;
            bottom: 0;
            left: 43px;
            right: auto;
            width: 4px;
            height: auto;
        }

        .timeline-track-fill {
            width: 100% !important;
            height: {{ $progressPercent }}%;
        }

        .timeline-step {
            flex-direction: row;
            align-items: center;
            margin-bottom: 22px;
        }

        .timeline-label {
            margin-top: 0;
            margin-left: 16px;
            text-align: left;
            max-width: none;
        }
    }
</style>
@endsection
