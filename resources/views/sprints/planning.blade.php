@extends('layouts.admin')
@section('page-title')
    {{ $project->project_name }} - {{ __('Sprint Planning') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">{{ __('Projects') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->id) }}">{{ $project->project_name }}</a></li>
    <li class="breadcrumb-item">{{ __('Sprint Planning') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('sprints.board', $project->id) }}" class="btn btn-sm btn-primary me-2" data-bs-toggle="tooltip" title="{{ __('Sprint Board') }}">
            <i class="ti ti-layout-kanban"></i> {{ __('Sprint Board') }}
        </a>
        <a href="#" data-url="{{ route('sprints.create', $project->id) }}" data-ajax-popup="true" data-size="md" data-title="{{ __('Create Sprint') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Create Sprint') }}">
            <i class="ti ti-plus"></i> {{ __('Create Sprint') }}
        </a>
    </div>
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dragula.min.css') }}">
    <style>
        /* Sprint Planning Layout */
        .sprint-planning-container {
            display: flex;
            gap: 20px;
            min-height: calc(100vh - 200px);
        }

        /* Backlog Panel */
        .backlog-panel {
            flex: 0 0 480px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 200px);
        }

        .backlog-panel .panel-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 16px 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .backlog-panel .panel-header h5 {
            color: #fff;
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .backlog-panel .panel-header .badge {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(4px);
        }

        .backlog-items {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
            min-height: 300px;
        }

        /* Hierarchy Styles */
        .hierarchy-milestone {
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }

        .milestone-header {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 10px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
        }

        .milestone-header:hover {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        }

        .milestone-header i.chevron {
            transition: transform 0.2s;
        }

        .milestone-header.collapsed i.chevron {
            transform: rotate(-90deg);
        }

        .milestone-header .milestone-icon {
            color: #6366f1;
        }

        .milestone-header .due-date {
            margin-left: auto;
            font-size: 11px;
            color: #64748b;
            font-weight: normal;
        }

        .milestone-content {
            padding: 8px;
            background: #fafafa;
        }

        .milestone-content.collapsed {
            display: none;
        }

        /* Epic Styles */
        .hierarchy-epic {
            margin-bottom: 8px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e0e7ff;
        }

        .epic-header {
            background: #eef2ff;
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 13px;
        }

        .epic-header:hover {
            background: #e0e7ff;
        }

        .epic-header .epic-icon {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 10px;
        }

        .epic-header .epic-key {
            color: #6366f1;
            font-size: 11px;
            font-weight: 600;
        }

        .epic-content {
            padding: 6px 8px;
            background: #f8faff;
        }

        .epic-content.collapsed {
            display: none;
        }

        /* Story Styles */
        .hierarchy-story {
            margin-bottom: 6px;
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #dbeafe;
            margin-left: 8px;
        }

        .story-header {
            background: #eff6ff;
            padding: 6px 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
        }

        .story-header:hover {
            background: #dbeafe;
        }

        .story-header .story-icon {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 9px;
        }

        .story-header .story-key {
            color: #3b82f6;
            font-size: 10px;
            font-weight: 600;
        }

        .story-content {
            padding: 4px 6px;
            background: #f0f9ff;
        }

        .story-content.collapsed {
            display: none;
        }

        /* Task list within story */
        .task-list {
            min-height: 30px;
        }

        /* Task Card */
        .task-card {
            background: #fff;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            cursor: grab;
            border-left: 3px solid #e2e8f0;
            transition: all 0.2s ease;
            font-size: 12px;
        }

        .task-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
        }

        .task-card.priority-critical { border-left-color: #ef4444; }
        .task-card.priority-high { border-left-color: #f59e0b; }
        .task-card.priority-medium { border-left-color: #3b82f6; }
        .task-card.priority-low { border-left-color: #10b981; }

        .task-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .task-card-title {
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
            flex: 1;
            margin-right: 6px;
        }

        .task-card-key {
            font-size: 10px;
            color: #64748b;
            font-weight: 500;
        }

        .task-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #64748b;
        }

        .story-points-badge {
            background: #e0e7ff;
            color: #4338ca;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .story-points-badge:hover {
            background: #c7d2fe;
        }

        .issue-type-icon {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #fff;
            margin-right: 4px;
        }

        /* Sprints Panel */
        .sprints-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
            padding-right: 8px;
        }

        /* Sprint Container */
        .sprint-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .sprint-container.active {
            border: 2px solid #3b82f6;
        }

        .sprint-header {
            background: #f1f5f9;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            cursor: pointer;
            border-bottom: 1px solid #e2e8f0;
        }

        .sprint-header:hover {
            background: #e2e8f0;
        }

        .sprint-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sprint-info h5 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }

        .sprint-stats {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            color: #64748b;
        }

        .sprint-stats .points {
            font-weight: 600;
            color: #3b82f6;
        }

        .sprint-items {
            min-height: 80px;
            padding: 12px;
            background: #fafafa;
        }

        .sprint-items.collapsed {
            display: none;
        }

        /* Story Points Dropdown */
        .story-points-dropdown {
            position: fixed;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 8px;
            z-index: 9999;
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            max-width: 200px;
        }

        .sp-option {
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            background: #f1f5f9;
            transition: all 0.15s;
        }

        .sp-option:hover, .sp-option.active {
            background: #3b82f6;
            color: #fff;
        }

        /* Dragula states */
        .gu-mirror {
            opacity: 0.9;
            transform: rotate(3deg);
        }

        .gu-transit {
            opacity: 0.4;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            font-size: 12px;
        }

        .empty-state i {
            font-size: 24px;
            margin-bottom: 8px;
        }

        /* Sprint Status Badges */
        .badge-planning { background: #94a3b8; }
        .badge-active { background: #3b82f6; }
        .badge-completed { background: #10b981; }
        .badge-cancelled { background: #ef4444; }

        /* Count badges in headers */
        .item-count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: auto;
        }

        /* Bug Card Styles */
        .bug-card {
            background: #fff;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            cursor: grab;
            border-left: 3px solid #ef4444;
            transition: all 0.2s ease;
            font-size: 12px;
        }

        .bug-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
        }

        .bug-card.severity-critical { border-left-color: #dc2626; background: #fef2f2; }
        .bug-card.severity-major { border-left-color: #ea580c; background: #fff7ed; }
        .bug-card.severity-minor { border-left-color: #f59e0b; }
        .bug-card.severity-trivial { border-left-color: #94a3b8; }

        .bug-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .bug-card-title {
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
            flex: 1;
            margin-right: 6px;
        }

        .bug-card-key {
            font-size: 10px;
            color: #ef4444;
            font-weight: 500;
        }

        .bug-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #64748b;
        }

        .bug-icon {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: #fff;
            background: #ef4444;
            margin-right: 4px;
        }

        .severity-badge {
            padding: 1px 5px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 600;
        }

        .severity-badge.critical { background: #fef2f2; color: #dc2626; }
        .severity-badge.major { background: #fff7ed; color: #ea580c; }
        .severity-badge.minor { background: #fefce8; color: #ca8a04; }
        .severity-badge.trivial { background: #f1f5f9; color: #64748b; }

        /* Bugs Section in Backlog */
        .bugs-section {
            margin-top: 16px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            overflow: hidden;
        }

        .bugs-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 10px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #991b1b;
            border-bottom: 1px solid #fecaca;
        }

        .bugs-header:hover {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        }

        .bugs-content {
            padding: 8px;
            background: #fffbfb;
        }

        .bugs-content.collapsed {
            display: none;
        }

        .bug-list {
            min-height: 30px;
        }

        /* Clickable card styles */
        .task-card,
        .bug-card {
            cursor: pointer;
            position: relative;
        }

        .task-card::after,
        .bug-card::after {
            content: '\eb1c';
            font-family: 'tabler-icons';
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.2s;
            color: #94a3b8;
            font-size: 14px;
        }

        .task-card:hover::after,
        .bug-card:hover::after {
            opacity: 1;
        }

        .task-card.gu-mirror::after,
        .bug-card.gu-mirror::after {
            display: none;
        }
    </style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="sprint-planning-container">
            <!-- Product Backlog Panel (Hierarchical) -->
            <div class="backlog-panel">
                <div class="panel-header">
                    <h5><i class="ti ti-inbox"></i> {{ __('Product Backlog') }}</h5>
                    <span class="badge bg-light text-dark" id="backlog-count">{{ $backlogCount }} {{ __('items') }}</span>
                </div>
                <div class="backlog-items" id="backlog-container">
                    @forelse($backlogHierarchy as $milestone)
                        <div class="hierarchy-milestone">
                            <div class="milestone-header" onclick="toggleMilestone({{ $milestone['id'] }})">
                                <i class="ti ti-chevron-down chevron" id="milestone-chevron-{{ $milestone['id'] }}"></i>
                                <i class="ti ti-flag milestone-icon"></i>
                                <span>{{ $milestone['title'] }}</span>
                                @if($milestone['due_date'])
                                    <span class="due-date"><i class="ti ti-calendar"></i> {{ \Carbon\Carbon::parse($milestone['due_date'])->format('M d') }}</span>
                                @endif
                            </div>
                            <div class="milestone-content" id="milestone-content-{{ $milestone['id'] }}">
                                {{-- Epics under this milestone --}}
                                @foreach($milestone['epics'] as $epic)
                                    <div class="hierarchy-epic">
                                        <div class="epic-header" onclick="toggleEpic({{ $epic['id'] }})">
                                            <i class="ti ti-chevron-down chevron" id="epic-chevron-{{ $epic['id'] }}"></i>
                                            <span class="epic-icon" style="background: {{ $epic['color'] }}">
                                                <i class="ti ti-bolt"></i>
                                            </span>
                                            <span class="epic-key">{{ $epic['issue_key'] }}</span>
                                            <span>{{ Str::limit($epic['name'], 35) }}</span>
                                        </div>
                                        <div class="epic-content" id="epic-content-{{ $epic['id'] }}">
                                            {{-- Stories under this epic --}}
                                            @foreach($epic['stories'] as $story)
                                                <div class="hierarchy-story">
                                                    <div class="story-header" onclick="toggleStory({{ $story['id'] }})">
                                                        <i class="ti ti-chevron-down chevron" id="story-chevron-{{ $story['id'] }}"></i>
                                                        <span class="story-icon" style="background: {{ $story['color'] }}">
                                                            <i class="ti ti-book"></i>
                                                        </span>
                                                        <span class="story-key">{{ $story['issue_key'] }}</span>
                                                        <span>{{ Str::limit($story['name'], 30) }}</span>
                                                        <span class="item-count">{{ count($story['tasks']) }}</span>
                                                    </div>
                                                    <div class="story-content" id="story-content-{{ $story['id'] }}">
                                                        <div class="task-list" data-sprint-id="" data-parent-id="{{ $story['id'] }}">
                                                            @foreach($story['tasks'] as $task)
                                                                @include('sprints.partials.task-card', ['task' => $task])
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            {{-- Direct tasks under epic (no story) --}}
                                            @if(count($epic['direct_tasks']) > 0)
                                                <div class="task-list ms-2" data-sprint-id="" data-parent-id="{{ $epic['id'] }}">
                                                    @foreach($epic['direct_tasks'] as $task)
                                                        @include('sprints.partials.task-card', ['task' => $task])
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                {{-- Standalone tasks under milestone --}}
                                @if(count($milestone['standalone_tasks']) > 0)
                                    <div class="task-list" data-sprint-id="" data-parent-id="">
                                        @foreach($milestone['standalone_tasks'] as $task)
                                            @include('sprints.partials.task-card', ['task' => $task])
                                        @endforeach
                                    </div>
                                @endif

                                @if(count($milestone['epics']) == 0 && count($milestone['standalone_tasks']) == 0)
                                    <div class="empty-state">
                                        <i class="ti ti-folder-off"></i>
                                        <p>{{ __('No items in this milestone') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="ti ti-clipboard-list"></i>
                            <p>{{ __('Backlog is empty') }}</p>
                        </div>
                    @endforelse

                    {{-- Bugs Section --}}
                    @if($backlogBugs->count() > 0 || true)
                        <div class="bugs-section">
                            <div class="bugs-header" onclick="toggleBugsSection()">
                                <i class="ti ti-chevron-down chevron" id="bugs-chevron"></i>
                                <i class="ti ti-bug"></i>
                                <span>{{ __('Bugs') }}</span>
                                <span class="item-count" id="bugs-count">{{ $backlogBugs->count() }}</span>
                            </div>
                            <div class="bugs-content" id="bugs-content">
                                <div class="bug-list" data-sprint-id="" data-type="bugs">
                                    @forelse($backlogBugs as $bug)
                                        @include('sprints.partials.bug-card', ['bug' => $bug])
                                    @empty
                                        <div class="empty-state empty-bugs">
                                            <i class="ti ti-bug-off"></i>
                                            <p>{{ __('No bugs in backlog') }}</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sprints Panel -->
            <div class="sprints-panel">
                @forelse($sprints as $sprint)
                    <div class="sprint-container {{ $sprint->isActive() ? 'active' : '' }}" data-sprint-id="{{ $sprint->id }}">
                        <div class="sprint-header" onclick="toggleSprint({{ $sprint->id }})">
                            <div class="sprint-info">
                                <i class="ti ti-chevron-down" id="chevron-{{ $sprint->id }}"></i>
                                <h5>{{ $sprint->name }}</h5>
                                <span class="badge badge-{{ $sprint->status }}">{{ \App\Models\Sprint::$statuses[$sprint->status] }}</span>
                            </div>
                            <div class="sprint-stats">
                                <span><i class="ti ti-calendar"></i> {{ $sprint->start_date->format('M d') }} - {{ $sprint->end_date->format('M d') }}</span>
                                <span class="points"><i class="ti ti-chart-dots"></i> <span id="sprint-points-{{ $sprint->id }}">{{ ($sprint->sprintTasks->sum('story_points') ?? 0) + ($sprint->sprintBugs ? $sprint->sprintBugs->sum('story_points') : 0) }}</span> pts</span>
                                <span><i class="ti ti-subtask"></i> {{ $sprint->sprintTasks->count() }} {{ __('tasks') }}</span>
                                @if($sprint->sprintBugs && $sprint->sprintBugs->count() > 0)
                                    <span class="text-danger"><i class="ti ti-bug"></i> {{ $sprint->sprintBugs->count() }} {{ __('bugs') }}</span>
                                @endif
                            </div>
                            <div class="sprint-actions" onclick="event.stopPropagation()">
                                @if($sprint->canStart())
                                    <button class="btn btn-sm btn-success start-sprint" data-sprint-id="{{ $sprint->id }}" data-project-id="{{ $project->id }}">
                                        <i class="ti ti-player-play"></i> {{ __('Start') }}
                                    </button>
                                @elseif($sprint->isActive())
                                    <button class="btn btn-sm btn-warning complete-sprint" data-sprint-id="{{ $sprint->id }}" data-project-id="{{ $project->id }}">
                                        <i class="ti ti-check"></i> {{ __('Complete') }}
                                    </button>
                                @endif
                                <a href="#" data-url="{{ route('sprints.edit', [$project->id, $sprint->id]) }}" data-ajax-popup="true" data-size="md" data-title="{{ __('Edit Sprint') }}" class="btn btn-sm btn-secondary">
                                    <i class="ti ti-pencil"></i>
                                </a>
                            </div>
                        </div>
                        <div class="sprint-items" id="sprint-{{ $sprint->id }}" data-sprint-id="{{ $sprint->id }}">
                            {{-- Tasks --}}
                            @forelse($sprint->sprintTasks as $task)
                                @include('sprints.partials.task-card', ['task' => $task, 'showParent' => true])
                            @empty
                                @if(!$sprint->sprintBugs || $sprint->sprintBugs->count() == 0)
                                    <div class="empty-state empty-sprint-{{ $sprint->id }}">
                                        <i class="ti ti-drag-drop"></i>
                                        <p>{{ __('Drag tasks here') }}</p>
                                    </div>
                                @endif
                            @endforelse
                        </div>
                        {{-- Bugs in Sprint --}}
                        @if($sprint->sprintBugs && $sprint->sprintBugs->count() > 0)
                            <div class="sprint-bugs px-3 pb-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="ti ti-bug text-danger"></i>
                                    <small class="text-muted fw-bold">{{ __('Bugs') }} ({{ $sprint->sprintBugs->count() }})</small>
                                </div>
                                <div class="bug-list" data-sprint-id="{{ $sprint->id }}" data-type="bugs">
                                    @foreach($sprint->sprintBugs as $bug)
                                        @include('sprints.partials.bug-card', ['bug' => $bug])
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="bug-list px-3 pb-2" data-sprint-id="{{ $sprint->id }}" data-type="bugs" style="min-height: 20px;">
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="ti ti-rocket" style="font-size: 48px; color: #94a3b8;"></i>
                            <h5 class="mt-3">{{ __('No sprints yet') }}</h5>
                            <p class="text-muted">{{ __('Create your first sprint to start planning') }}</p>
                            <a href="#" data-url="{{ route('sprints.create', $project->id) }}" data-ajax-popup="true" data-size="md" data-title="{{ __('Create Sprint') }}" class="btn btn-primary mt-2">
                                <i class="ti ti-plus"></i> {{ __('Create Sprint') }}
                            </a>
                        </div>
                    </div>
                @endforelse

                @if($sprints->count() > 0)
                    <a href="#" data-url="{{ route('sprints.create', $project->id) }}" data-ajax-popup="true" data-size="md" data-title="{{ __('Create Sprint') }}" class="btn btn-outline-primary">
                        <i class="ti ti-plus"></i> {{ __('Create Sprint') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Sprint Complete Modal -->
<div class="modal fade" id="sprintCompleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Complete Sprint') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('What would you like to do with incomplete tasks?') }}</p>
                <div id="incomplete-tasks-list" class="mb-3"></div>
                <div class="form-group">
                    <label>{{ __('Move incomplete tasks to') }}:</label>
                    <select class="form-select" id="move-destination">
                        <option value="backlog">{{ __('Product Backlog') }}</option>
                        @foreach($sprints->where('status', 'planning') as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="confirm-complete">{{ __('Complete Sprint') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
    <script src="{{ asset('assets/js/plugins/dragula.min.js') }}"></script>
    <script>
        var csrfToken = '{{ csrf_token() }}';
        var moveTaskUrl = '{{ route("sprints.move-task") }}';
        var moveBugUrl = '{{ route("sprints.move-bug") }}';
        var reorderUrl = '{{ route("sprints.reorder-tasks") }}';
        var projectId = {{ $project->id }};

        // Toggle bugs section
        function toggleBugsSection() {
            var content = document.getElementById('bugs-content');
            var chevron = document.getElementById('bugs-chevron');
            if (content.classList.contains('collapsed')) {
                content.classList.remove('collapsed');
                chevron.style.transform = 'rotate(0deg)';
            } else {
                content.classList.add('collapsed');
                chevron.style.transform = 'rotate(-90deg)';
            }
        }

        // Toggle functions for hierarchy
        function toggleMilestone(id) {
            var content = document.getElementById('milestone-content-' + id);
            var chevron = document.getElementById('milestone-chevron-' + id);
            var header = chevron.closest('.milestone-header');
            if (content.classList.contains('collapsed')) {
                content.classList.remove('collapsed');
                header.classList.remove('collapsed');
            } else {
                content.classList.add('collapsed');
                header.classList.add('collapsed');
            }
        }

        function toggleEpic(id) {
            var content = document.getElementById('epic-content-' + id);
            var chevron = document.getElementById('epic-chevron-' + id);
            if (content.classList.contains('collapsed')) {
                content.classList.remove('collapsed');
                chevron.style.transform = 'rotate(0deg)';
            } else {
                content.classList.add('collapsed');
                chevron.style.transform = 'rotate(-90deg)';
            }
        }

        function toggleStory(id) {
            var content = document.getElementById('story-content-' + id);
            var chevron = document.getElementById('story-chevron-' + id);
            if (content.classList.contains('collapsed')) {
                content.classList.remove('collapsed');
                chevron.style.transform = 'rotate(0deg)';
            } else {
                content.classList.add('collapsed');
                chevron.style.transform = 'rotate(-90deg)';
            }
        }

        function toggleSprint(sprintId) {
            var items = document.getElementById('sprint-' + sprintId);
            var chevron = document.getElementById('chevron-' + sprintId);
            if (items.classList.contains('collapsed')) {
                items.classList.remove('collapsed');
                chevron.classList.remove('ti-chevron-right');
                chevron.classList.add('ti-chevron-down');
            } else {
                items.classList.add('collapsed');
                chevron.classList.remove('ti-chevron-down');
                chevron.classList.add('ti-chevron-right');
            }
        }

        $(document).ready(function() {
            // Initialize Dragula for TASKS - collect all task lists and sprint containers
            var taskContainers = [];

            // All task-list containers in backlog
            document.querySelectorAll('.task-list').forEach(function(el) {
                taskContainers.push(el);
            });

            // All sprint containers
            document.querySelectorAll('.sprint-items').forEach(function(el) {
                taskContainers.push(el);
            });

            var drakeTask = dragula(taskContainers, {
                moves: function(el, source, handle) {
                    return el.classList.contains('task-card');
                },
                accepts: function(el, target) {
                    // Tasks can only go into task-list or sprint-items, not bug-list
                    return !target.classList.contains('bug-list');
                }
            });

            drakeTask.on('drop', function(el, target, source, sibling) {
                var taskId = el.getAttribute('data-task-id');
                var targetSprintId = target.getAttribute('data-sprint-id') || null;
                var sourceSprintId = source.getAttribute('data-sprint-id') || null;

                // Get new order
                var order = Array.from(target.children).filter(c => c.classList.contains('task-card')).indexOf(el);

                // Hide empty states in target
                target.querySelectorAll('.empty-state').forEach(e => e.style.display = 'none');

                // Show empty state in source if empty
                if (source.querySelectorAll('.task-card').length === 0) {
                    source.querySelectorAll('.empty-state').forEach(e => e.style.display = 'block');
                }

                // AJAX call
                $.ajax({
                    url: moveTaskUrl,
                    type: 'POST',
                    data: {
                        task_id: taskId,
                        sprint_id: targetSprintId,
                        order: order,
                        _token: csrfToken
                    },
                    success: function(response) {
                        updateCounts();
                        show_toastr('success', response.message || 'Task moved successfully', 'success');
                    },
                    error: function(xhr) {
                        show_toastr('error', 'Failed to move task', 'error');
                        drakeTask.cancel(true);
                    }
                });
            });

            // Initialize Dragula for BUGS - collect all bug lists
            var bugContainers = [];
            document.querySelectorAll('.bug-list').forEach(function(el) {
                bugContainers.push(el);
            });

            var drakeBug = dragula(bugContainers, {
                moves: function(el, source, handle) {
                    return el.classList.contains('bug-card');
                },
                accepts: function(el, target) {
                    return target.classList.contains('bug-list');
                }
            });

            drakeBug.on('drop', function(el, target, source, sibling) {
                var bugId = el.getAttribute('data-bug-id');
                var targetSprintId = target.getAttribute('data-sprint-id') || null;

                // Get new order
                var order = Array.from(target.children).filter(c => c.classList.contains('bug-card')).indexOf(el);

                // Hide empty states in target
                target.querySelectorAll('.empty-state').forEach(e => e.style.display = 'none');

                // Show empty state in source if empty
                if (source.querySelectorAll('.bug-card').length === 0) {
                    source.querySelectorAll('.empty-state').forEach(e => e.style.display = 'block');
                }

                // AJAX call
                $.ajax({
                    url: moveBugUrl,
                    type: 'POST',
                    data: {
                        bug_id: bugId,
                        sprint_id: targetSprintId,
                        order: order,
                        _token: csrfToken
                    },
                    success: function(response) {
                        updateCounts();
                        show_toastr('success', response.message || 'Bug moved successfully', 'success');
                    },
                    error: function(xhr) {
                        show_toastr('error', 'Failed to move bug', 'error');
                        drakeBug.cancel(true);
                    }
                });
            });

            // Update counts
            function updateCounts() {
                // Update backlog count (tasks + bugs)
                var backlogTasks = document.querySelectorAll('.backlog-items .task-card').length;
                var backlogBugs = document.querySelectorAll('.backlog-items .bug-list[data-sprint-id=""] .bug-card').length;
                $('#backlog-count').text((backlogTasks + backlogBugs) + ' items');

                // Update bugs count
                $('#bugs-count').text(backlogBugs);

                // Update sprint counts
                document.querySelectorAll('.sprint-container').forEach(function(sprintContainer) {
                    var sprintId = sprintContainer.getAttribute('data-sprint-id');
                    if (sprintId) {
                        var totalPoints = 0;
                        // Task points
                        sprintContainer.querySelectorAll('.sprint-items .task-card').forEach(function(card) {
                            totalPoints += parseFloat(card.getAttribute('data-story-points') || 0);
                        });
                        // Bug points
                        sprintContainer.querySelectorAll('.bug-list[data-sprint-id="' + sprintId + '"] .bug-card').forEach(function(card) {
                            totalPoints += parseFloat(card.getAttribute('data-story-points') || 0);
                        });
                        $('#sprint-points-' + sprintId).text(totalPoints);
                    }
                });
            }

            // Story points inline edit (for both tasks and bugs)
            $(document).on('click', '.story-points-badge', function(e) {
                e.stopPropagation();
                var $badge = $(this);
                var currentPoints = parseFloat($badge.data('points')) || 0;
                var $taskCard = $badge.closest('.task-card');
                var $bugCard = $badge.closest('.bug-card');

                var itemId, itemType;
                if ($taskCard.length) {
                    itemId = $taskCard.data('task-id');
                    itemType = 'task';
                } else if ($bugCard.length) {
                    itemId = $bugCard.data('bug-id');
                    itemType = 'bug';
                } else {
                    return;
                }

                // Remove existing dropdown
                $('.story-points-dropdown').remove();

                var pointOptions = [0, 0.5, 1, 2, 3, 5, 8, 13, 21];
                var $dropdown = $('<div class="story-points-dropdown"></div>');

                pointOptions.forEach(function(pts) {
                    var $option = $('<span class="sp-option' + (pts == currentPoints ? ' active' : '') + '">' + pts + '</span>');
                    $option.on('click', function(e) {
                        e.stopPropagation();
                        updateStoryPoints(itemId, pts, $badge, itemType);
                        $dropdown.remove();
                    });
                    $dropdown.append($option);
                });

                $('body').append($dropdown);
                var offset = $badge.offset();
                $dropdown.css({
                    top: offset.top + $badge.outerHeight() + 4,
                    left: offset.left
                });

                setTimeout(function() {
                    $(document).one('click', function() {
                        $dropdown.remove();
                    });
                }, 100);
            });

            function updateStoryPoints(itemId, points, $badge, itemType) {
                var url = itemType === 'bug' ? '/bugs/' + itemId + '/story-points' : '/tasks/' + itemId + '/story-points';

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        story_points: points,
                        _token: csrfToken
                    },
                    success: function() {
                        $badge.text(points + ' pts').data('points', points);
                        if (itemType === 'bug') {
                            $badge.closest('.bug-card').attr('data-story-points', points);
                        } else {
                            $badge.closest('.task-card').attr('data-story-points', points);
                        }
                        updateCounts();
                        show_toastr('success', 'Story points updated', 'success');
                    }
                });
            }

            // Start Sprint
            $(document).on('click', '.start-sprint', function(e) {
                e.stopPropagation();
                var sprintId = $(this).data('sprint-id');
                var projectId = $(this).data('project-id');

                if (confirm('Start this sprint?')) {
                    $.post('/projects/' + projectId + '/sprints/' + sprintId + '/start', {
                        _token: csrfToken
                    }).done(function(response) {
                        show_toastr('success', response.message, 'success');
                        location.reload();
                    }).fail(function(xhr) {
                        show_toastr('error', xhr.responseJSON?.error || 'Failed to start sprint', 'error');
                    });
                }
            });

            // Complete Sprint
            var currentSprintId = null;
            $(document).on('click', '.complete-sprint', function(e) {
                e.stopPropagation();
                currentSprintId = $(this).data('sprint-id');
                var projectId = $(this).data('project-id');

                $.post('/projects/' + projectId + '/sprints/' + currentSprintId + '/complete', {
                    _token: csrfToken
                }).done(function(response) {
                    if (response.incomplete_count > 0) {
                        var html = '<p class="text-muted">' + response.incomplete_count + ' incomplete tasks:</p><ul class="list-group">';
                        response.incomplete_tasks.forEach(function(task) {
                            html += '<li class="list-group-item d-flex justify-content-between"><span>' + task.name + '</span><span class="badge bg-primary">' + (task.story_points || 0) + ' pts</span></li>';
                        });
                        html += '</ul>';
                        $('#incomplete-tasks-list').html(html);
                        $('#sprintCompleteModal').modal('show');
                    } else {
                        show_toastr('success', response.message, 'success');
                        location.reload();
                    }
                }).fail(function(xhr) {
                    show_toastr('error', xhr.responseJSON?.error || 'Failed to complete sprint', 'error');
                });
            });

            // Confirm complete sprint
            $('#confirm-complete').on('click', function() {
                var destination = $('#move-destination').val();
                var nextSprintId = destination !== 'backlog' ? destination : null;

                $.post('/projects/' + projectId + '/sprints/' + currentSprintId + '/move-incomplete', {
                    destination: destination === 'backlog' ? 'backlog' : 'sprint',
                    next_sprint_id: nextSprintId,
                    _token: csrfToken
                }).done(function() {
                    $('#sprintCompleteModal').modal('hide');
                    show_toastr('success', 'Sprint completed successfully', 'success');
                    location.reload();
                });
            });

            // Task card click - View/Edit task
            var isDragging = false;
            var dragStartTime = 0;

            // Track drag state
            drakeTask.on('drag', function() {
                isDragging = true;
                dragStartTime = Date.now();
            });
            drakeTask.on('dragend', function() {
                setTimeout(function() {
                    isDragging = false;
                }, 100);
            });

            drakeBug.on('drag', function() {
                isDragging = true;
                dragStartTime = Date.now();
            });
            drakeBug.on('dragend', function() {
                setTimeout(function() {
                    isDragging = false;
                }, 100);
            });

            // Click handler for task cards
            $(document).on('click', '.task-card', function(e) {
                // Don't open if clicking on story points badge
                if ($(e.target).closest('.story-points-badge').length > 0) {
                    return;
                }

                // Don't open if was dragging
                if (isDragging || (Date.now() - dragStartTime) < 200) {
                    return;
                }

                var viewUrl = $(this).data('view-url');
                if (viewUrl) {
                    // Open in ajax popup
                    var $this = $(this);
                    var popupSize = 'xl';
                    var title = '{{ __("View Task") }}';

                    $.ajax({
                        url: viewUrl,
                        success: function(data) {
                            $('#commonModal .modal-title').text(title);
                            $('#commonModal .modal-dialog').addClass('modal-' + popupSize);
                            $('#commonModal .modal-body').html(data);
                            $('#commonModal').modal('show');
                        },
                        error: function() {
                            show_toastr('error', '{{ __("Error loading task") }}', 'error');
                        }
                    });
                }
            });

            // Click handler for bug cards
            $(document).on('click', '.bug-card', function(e) {
                // Don't open if clicking on story points badge
                if ($(e.target).closest('.story-points-badge').length > 0) {
                    return;
                }

                // Don't open if was dragging
                if (isDragging || (Date.now() - dragStartTime) < 200) {
                    return;
                }

                var viewUrl = $(this).data('view-url');
                if (viewUrl) {
                    // Open in ajax popup
                    $.ajax({
                        url: viewUrl,
                        success: function(data) {
                            $('#commonModal .modal-title').text('{{ __("View Bug") }}');
                            $('#commonModal .modal-dialog').addClass('modal-xl');
                            $('#commonModal .modal-body').html(data);
                            $('#commonModal').modal('show');
                        },
                        error: function() {
                            show_toastr('error', '{{ __("Error loading bug") }}', 'error');
                        }
                    });
                }
            });
        });
    </script>
@endpush
