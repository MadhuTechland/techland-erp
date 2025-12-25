@extends('layouts.admin')
@section('page-title')
    {{ ucwords($project->project_name) . __("'s Backlog") }}
@endsection

@push('css-page')
<style>
    /* ===== BACKLOG HIERARCHY VIEW ===== */
    .backlog-container {
        padding: 20px;
    }

    /* Project Summary Card */
    .project-summary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 24px;
        color: #fff;
        margin-bottom: 24px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
    }

    .project-summary h3 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 16px;
    }

    .project-summary .stat-item {
        background: rgba(255,255,255,0.15);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        backdrop-filter: blur(4px);
    }

    .project-summary .stat-value {
        font-size: 28px;
        font-weight: 700;
    }

    .project-summary .stat-label {
        font-size: 12px;
        text-transform: uppercase;
        opacity: 0.9;
        letter-spacing: 0.5px;
    }

    /* Milestone Card */
    .milestone-card {
        background: #fff;
        border-radius: 16px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }

    .milestone-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .milestone-header h4 {
        font-size: 18px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .milestone-header h4 i {
        color: #8b5cf6;
        font-size: 22px;
    }

    .milestone-stats {
        display: flex;
        gap: 20px;
    }

    .milestone-stat {
        text-align: center;
        padding: 8px 16px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .milestone-stat .value {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
    }

    .milestone-stat .label {
        font-size: 11px;
        color: #64748b;
        text-transform: uppercase;
    }

    .milestone-body {
        padding: 20px 24px;
    }

    /* Epic/Story Container Card */
    .container-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 16px;
        overflow: hidden;
    }

    .container-header {
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        transition: background 0.2s;
    }

    .container-header:hover {
        background: #f1f5f9;
    }

    .container-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .container-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #fff;
    }

    .container-icon.epic {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }

    .container-icon.story {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .container-title {
        font-size: 15px;
        font-weight: 600;
        color: #1e293b;
    }

    .container-key {
        font-size: 12px;
        color: #64748b;
        background: #e2e8f0;
        padding: 2px 8px;
        border-radius: 4px;
        margin-left: 8px;
    }

    .container-meta {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .container-hours {
        font-size: 14px;
        font-weight: 600;
        color: #3b82f6;
        background: #dbeafe;
        padding: 4px 12px;
        border-radius: 20px;
    }

    .container-progress {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .progress-bar-mini {
        width: 80px;
        height: 6px;
        background: #e2e8f0;
        border-radius: 3px;
        overflow: hidden;
    }

    .progress-bar-mini .fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        border-radius: 3px;
        transition: width 0.3s;
    }

    .progress-text {
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
    }

    .toggle-icon {
        color: #94a3b8;
        transition: transform 0.2s;
    }

    .container-item.collapsed .toggle-icon {
        transform: rotate(-90deg);
    }

    /* Work Items (Tasks/Bugs) */
    .container-children {
        padding: 0 20px 16px;
    }

    .work-item {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s;
    }

    .work-item:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transform: translateY(-1px);
    }

    .work-item:last-child {
        margin-bottom: 0;
    }

    .work-item-info {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
    }

    .work-item-type {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: #fff;
    }

    .work-item-type.task {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .work-item-type.bug {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .work-item-type.subtask {
        background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
    }

    .work-item-title {
        font-size: 14px;
        font-weight: 500;
        color: #1e293b;
    }

    .work-item-title a {
        color: inherit;
        text-decoration: none;
    }

    .work-item-title a:hover {
        color: #3b82f6;
    }

    .work-item-key {
        font-size: 11px;
        color: #64748b;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 8px;
    }

    .work-item-meta {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .work-item-hours {
        font-size: 13px;
        font-weight: 500;
        color: #64748b;
    }

    .work-item-status {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .work-item-status.complete {
        background: #dcfce7;
        color: #16a34a;
    }

    .work-item-status.pending {
        background: #fef3c7;
        color: #d97706;
    }

    /* Orphan Work Items (no parent) */
    .orphan-items {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px dashed #e2e8f0;
    }

    .orphan-label {
        font-size: 12px;
        color: #94a3b8;
        text-transform: uppercase;
        margin-bottom: 12px;
        font-weight: 600;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 16px;
    }

    /* View Toggle */
    .view-toggle {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
    }

    .view-toggle .btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
    }

    .view-toggle .btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
    }

    /* Priority Badges */
    .priority-badge {
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .priority-badge.critical { background: #fee2e2; color: #dc2626; }
    .priority-badge.high { background: #fef3c7; color: #d97706; }
    .priority-badge.medium { background: #dbeafe; color: #2563eb; }
    .priority-badge.low { background: #d1fae5; color: #059669; }
</style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">{{ __('Project') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->id) }}">{{ ucwords($project->project_name) }}</a></li>
    <li class="breadcrumb-item">{{ __('Backlog') }}</li>
@endsection

@section('action-btn')
<div class="d-flex gap-2">
    <a href="{{ route('projects.tasks.index', $project->id) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="{{ __('Kanban View') }}">
        <i class="ti ti-layout-kanban"></i> {{ __('Kanban') }}
    </a>
    @can('create project task')
        <a href="#" data-size="lg" data-url="{{ route('projects.tasks.create', $project->id) }}"
            data-ajax-popup="true" data-bs-toggle="tooltip" title="{{ __('Create Task') }}" class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i>
        </a>
    @endcan
</div>
@endsection

@section('content')
<div class="backlog-container">
    <!-- Project Summary -->
    <div class="project-summary">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h3>{{ $project->project_name }}</h3>
                <p class="mb-0 opacity-75">{{ __('Backlog & Hierarchy View') }}</p>
            </div>
            <div class="col-md-6">
                <div class="row g-3">
                    <div class="col-4">
                        <div class="stat-item">
                            <div class="stat-value">{{ $projectHrs['allocated'] ?? 0 }}</div>
                            <div class="stat-label">{{ __('Total Hours') }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            <div class="stat-value">{{ $milestones->count() }}</div>
                            <div class="stat-label">{{ __('Milestones') }}</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-item">
                            @php
                                $workItemTypeIds = $workItemTypes->pluck('id')->toArray();
                                $totalWorkItems = \App\Models\ProjectTask::where('project_id', $project->id)
                                    ->where(function($q) use ($workItemTypeIds) {
                                        $q->whereIn('issue_type_id', $workItemTypeIds)->orWhereNull('issue_type_id');
                                    })->count();
                            @endphp
                            <div class="stat-value">{{ $totalWorkItems }}</div>
                            <div class="stat-label">{{ __('Work Items') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Milestones -->
    @foreach($milestones as $milestone)
        @php
            $milestoneHrs = $milestone->getTotalEstimatedHrs();
            $milestoneProgress = $milestone->getWorkItemProgress();
            $milestoneTasks = $milestone->tasks()->with(['issueType', 'children.issueType'])->whereNull('parent_id')->orderBy('order')->get();
        @endphp
        <div class="milestone-card">
            <div class="milestone-header">
                <h4>
                    <i class="ti ti-flag-2"></i>
                    {{ $milestone->title }}
                </h4>
                <div class="milestone-stats">
                    <div class="milestone-stat">
                        <div class="value">{{ $milestoneHrs }}h</div>
                        <div class="label">{{ __('Est. Hours') }}</div>
                    </div>
                    <div class="milestone-stat">
                        <div class="value">{{ $milestoneProgress['completed'] }}/{{ $milestoneProgress['total'] }}</div>
                        <div class="label">{{ __('Tasks') }}</div>
                    </div>
                    <div class="milestone-stat">
                        <div class="value">{{ $milestoneProgress['percentage'] }}%</div>
                        <div class="label">{{ __('Complete') }}</div>
                    </div>
                </div>
            </div>
            <div class="milestone-body">
                @if($milestoneTasks->count() > 0)
                    @foreach($milestoneTasks as $task)
                        @if($task->isContainer())
                            <!-- Container Item (Epic/Story) -->
                            @include('project_task.partials.container_item', ['item' => $task, 'project' => $project])
                        @else
                            <!-- Orphan Work Item -->
                            @include('project_task.partials.work_item', ['item' => $task, 'project' => $project])
                        @endif
                    @endforeach
                @else
                    <div class="empty-state">
                        <i class="ti ti-clipboard-list"></i>
                        <p>{{ __('No items in this milestone') }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <!-- Backlog Items (No Milestone) -->
    @if($backlogTasks->count() > 0)
        <div class="milestone-card">
            <div class="milestone-header">
                <h4>
                    <i class="ti ti-inbox"></i>
                    {{ __('Backlog') }}
                    <span class="badge bg-secondary ms-2">{{ __('No Milestone') }}</span>
                </h4>
                @php
                    $backlogHrs = 0;
                    $backlogComplete = 0;
                    $backlogTotal = 0;
                    foreach($backlogTasks as $bt) {
                        if($bt->isContainer()) {
                            $backlogHrs += $bt->getChildrenTotalHrs();
                            $prog = $bt->getWorkItemProgress();
                            $backlogComplete += $prog['completed'];
                            $backlogTotal += $prog['total'];
                        } else {
                            $backlogHrs += floatval($bt->estimated_hrs ?? 0);
                            $backlogTotal++;
                            if($bt->is_complete) $backlogComplete++;
                        }
                    }
                @endphp
                <div class="milestone-stats">
                    <div class="milestone-stat">
                        <div class="value">{{ $backlogHrs }}h</div>
                        <div class="label">{{ __('Est. Hours') }}</div>
                    </div>
                    <div class="milestone-stat">
                        <div class="value">{{ $backlogComplete }}/{{ $backlogTotal }}</div>
                        <div class="label">{{ __('Tasks') }}</div>
                    </div>
                </div>
            </div>
            <div class="milestone-body">
                @foreach($backlogTasks as $task)
                    @if($task->isContainer())
                        @include('project_task.partials.container_item', ['item' => $task, 'project' => $project])
                    @else
                        @include('project_task.partials.work_item', ['item' => $task, 'project' => $project])
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if($milestones->count() == 0 && $backlogTasks->count() == 0)
        <div class="milestone-card">
            <div class="milestone-body">
                <div class="empty-state">
                    <i class="ti ti-clipboard-list"></i>
                    <h5>{{ __('No items yet') }}</h5>
                    <p>{{ __('Create milestones and tasks to organize your project') }}</p>
                </div>
            </div>
        </div>
    @endif
</div>

@push('script-page')
<script>
    // Toggle container children visibility
    $(document).on('click', '.container-header', function() {
        var $container = $(this).closest('.container-item');
        $container.toggleClass('collapsed');
        $container.find('.container-children').slideToggle(200);
    });
</script>
@endpush
@endsection
