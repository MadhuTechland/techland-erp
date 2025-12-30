@extends('layouts.admin')

@section('page-title')
    {{__('Task Board')}}
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dragula.min.css') }}">
    <style>
        /* ===== ELEGANT KANBAN BOARD ===== */

        /* Page Background */
        .pc-content {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
        }

        /* Filter Bar */
        .filter-bar {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: none;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            font-size: 13px;
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            height: 40px;
            transition: all 0.2s;
        }

        .filter-bar .form-control:focus,
        .filter-bar .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .filter-bar label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        /* Board Container */
        .kanban-board {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding: 8px 4px 20px;
            align-items: flex-start;
        }

        /* Kanban Column */
        .kanban-column {
            flex: 0 0 300px;
            min-width: 300px;
            max-width: 300px;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 200px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.8);
            overflow: hidden;
        }

        .kanban-column:nth-child(1) .kanban-column-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .kanban-column:nth-child(2) .kanban-column-header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .kanban-column:nth-child(3) .kanban-column-header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .kanban-column:nth-child(4) .kanban-column-header { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .kanban-column:nth-child(5) .kanban-column-header { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .kanban-column:nth-child(6) .kanban-column-header { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .kanban-column:nth-child(7) .kanban-column-header { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
        .kanban-column:nth-child(8) .kanban-column-header { background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%); }
        .kanban-column:nth-child(9) .kanban-column-header { background: linear-gradient(135deg, #667eea 0%, #5a4fcf 100%); }
        .kanban-column:nth-child(10) .kanban-column-header { background: linear-gradient(135deg, #f5576c 0%, #d63384 100%); }
        .kanban-column:nth-child(11) .kanban-column-header { background: linear-gradient(135deg, #20c997 0%, #0dcaf0 100%); }
        .kanban-column:nth-child(12) .kanban-column-header { background: linear-gradient(135deg, #6f42c1 0%, #d63384 100%); }

        /* Make kanban board scrollable during drag */
        .kanban-board {
            scroll-behavior: smooth;
            cursor: grab;
        }

        .kanban-board.dragging-active {
            cursor: grabbing;
            scroll-behavior: auto;
        }

        .kanban-column-header {
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 16px 16px 0 0;
        }

        .kanban-column-header h6 {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: #fff;
            letter-spacing: 0.5px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .kanban-column-header .task-count {
            background: rgba(255,255,255,0.25);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .kanban-column-body {
            padding: 12px;
            overflow-y: auto;
            flex: 1;
            min-height: 80px;
            background: #f8f9fa;
        }

        .kanban-column-body::-webkit-scrollbar {
            width: 6px;
        }

        .kanban-column-body::-webkit-scrollbar-track {
            background: transparent;
        }

        .kanban-column-body::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        /* Task Card */
        .task-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }

        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1), 0 3px 10px rgba(0,0,0,0.06);
            border-color: #dee2e6;
        }

        .task-card-body {
            padding: 14px;
            position: relative;
        }

        /* Card Top Border Accent */
        .task-card::before {
            content: '';
            display: block;
            height: 3px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .task-card[data-priority="0"]::before { background: linear-gradient(90deg, #28a745 0%, #20c997 100%); }
        .task-card[data-priority="1"]::before { background: linear-gradient(90deg, #ffc107 0%, #fd7e14 100%); }
        .task-card[data-priority="2"]::before { background: linear-gradient(90deg, #dc3545 0%, #e83e8c 100%); }
        .task-card[data-priority="3"]::before { background: linear-gradient(90deg, #6f42c1 0%, #e83e8c 100%); }

        /* Task Title */
        .task-card-title {
            font-size: 14px;
            font-weight: 600;
            color: #2d3748;
            margin: 0 0 10px 0;
            line-height: 1.5;
            word-break: break-word;
        }

        .task-card-title a {
            color: #2d3748;
            text-decoration: none;
            transition: color 0.2s;
        }

        .task-card-title a:hover {
            color: #667eea;
        }

        /* Task Meta */
        .task-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }

        .task-badge {
            font-size: 10px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            letter-spacing: 0.3px;
        }

        .task-badge i {
            font-size: 10px;
        }

        .task-badge.project {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #4338ca;
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .task-badge.priority-low {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .task-badge.priority-medium {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .task-badge.priority-high {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        .task-badge.priority-urgent {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #fff;
        }

        .task-badge.issue-type {
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
            background: #5e6c84; /* fallback */
        }

        /* If background is light, use dark text */
        .task-badge.issue-type[style*="background: #fff"],
        .task-badge.issue-type[style*="background: #FFF"],
        .task-badge.issue-type[style*="background:#fff"],
        .task-badge.issue-type[style*="background:#FFF"] {
            color: #333 !important;
            text-shadow: none;
        }

        /* Default task type (when no issue type set) */
        .task-badge.task-type-default {
            background: #5e6c84;
            color: #fff;
        }

        /* Task Footer */
        .task-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px solid #f1f5f9;
        }

        .task-card-icons {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
            font-size: 11px;
        }

        .task-card-icons span {
            display: flex;
            align-items: center;
            gap: 3px;
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .task-card-icons span:hover {
            background: #e2e8f0;
            color: #475569;
        }

        .task-card-icons i {
            font-size: 12px;
        }

        .task-due-date {
            font-size: 10px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #64748b;
        }

        .task-due-date.overdue {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* User Avatars */
        .task-avatars {
            display: flex;
        }

        .task-avatars img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #fff;
            margin-left: -8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .task-avatars img:first-child {
            margin-left: 0;
        }

        .task-avatars img:hover {
            transform: scale(1.15);
            z-index: 10;
        }

        /* Drag States */
        .gu-mirror {
            cursor: grabbing !important;
            opacity: 1;
            transform: rotate(4deg) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2) !important;
            border-radius: 12px;
        }

        .gu-transit {
            opacity: 0.3;
            transform: scale(0.98);
        }

        .kanban-column-body.gu-over {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%) !important;
            border-radius: 8px;
        }

        /* Empty State */
        .kanban-empty {
            padding: 30px 16px;
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            font-style: italic;
        }

        .kanban-empty::before {
            content: '📋';
            display: block;
            font-size: 28px;
            margin-bottom: 8px;
            opacity: 0.5;
        }

        /* Search Input */
        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }

        .search-box input {
            padding-left: 42px;
            background: #f9fafb;
        }

        .search-box input:focus {
            background: #fff;
        }

        /* Quick Filters */
        .quick-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .quick-filter-btn {
            font-size: 12px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 20px;
            border: 2px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: #f5f3ff;
        }

        .quick-filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Issue Key */
        .issue-key {
            font-size: 11px;
            color: #667eea;
            font-weight: 700;
            background: #f0f0ff;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Progress bar */
        .task-progress {
            height: 4px;
            background: #e5e7eb;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }

        .task-progress-bar {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .task-progress-bar.bg-success { background: linear-gradient(90deg, #10b981 0%, #34d399 100%); }
        .task-progress-bar.bg-primary { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
        .task-progress-bar.bg-warning { background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%); }
        .task-progress-bar.bg-danger { background: linear-gradient(90deg, #ef4444 0%, #f87171 100%); }

        /* Parent Info (Epic/Story) */
        .parent-info {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-bottom: 8px;
        }

        .parent-badge {
            font-size: 9px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .parent-badge.epic {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: #fff;
        }

        .parent-badge.story {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
        }

        .parent-badge i {
            font-size: 10px;
        }

        /* Hours Badge */
        .task-badge.hours {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            font-weight: 700;
            font-size: 11px;
            padding: 5px 10px;
        }

        .task-badge.hours i {
            color: #92400e;
            font-size: 12px;
        }

        /* Hours display in card header */
        .task-hours-display {
            position: absolute;
            top: 8px;
            right: 8px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.4);
        }

        .task-hours-display i {
            font-size: 11px;
            margin-right: 3px;
        }

        /* Due Date Styling */
        .task-due-date {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .task-due-date.due-overdue {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
            animation: pulse-danger 2s infinite;
        }

        .task-due-date.due-today {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
        }

        .task-due-date.due-soon {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        @keyframes pulse-danger {
            0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            50% { opacity: 0.9; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0); }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .kanban-column {
                flex: 0 0 260px;
                min-width: 260px;
            }

            .filter-bar {
                padding: 12px;
            }

            .quick-filters {
                gap: 6px;
            }

            .quick-filter-btn {
                padding: 6px 10px;
                font-size: 11px;
            }
        }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('projects.index')}}">{{__('Project')}}</a></li>
    <li class="breadcrumb-item">{{__('Task Board')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @if($view == 'grid')
            <a href="{{ route('taskBoard.view', 'list') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="{{__('List View')}}">
                <i class="ti ti-list"></i>
            </a>
        @else
            <a href="{{ route('taskBoard.view', 'grid') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="{{__('Board View')}}">
                <i class="ti ti-layout-kanban"></i>
            </a>
        @endif
    </div>
@endsection

@section('content')
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-lg-2 col-md-4">
                <div class="search-box">
                    <i class="ti ti-search"></i>
                    <input type="text" class="form-control" id="task_search" placeholder="{{__('Search tasks...')}}">
                </div>
            </div>
            <div class="col-lg col-md-4">
                <label>{{__('Project')}}</label>
                <select class="form-select" id="filter_project">
                    <option value="">{{__('All Projects')}}</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg col-md-4">
                <label>{{__('Epic')}}</label>
                <select class="form-select" id="filter_epic">
                    <option value="">{{__('All Epics')}}</option>
                    @foreach($epics as $epic)
                        <option value="{{ $epic->id }}" data-project="{{ $epic->project_id }}">{{ $epic->issue_key ? $epic->issue_key . ' - ' : '' }}{{ Str::limit($epic->name, 20) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg col-md-4">
                <label>{{__('User Story')}}</label>
                <select class="form-select" id="filter_story">
                    <option value="">{{__('All Stories')}}</option>
                    @foreach($stories as $story)
                        <option value="{{ $story->id }}" data-project="{{ $story->project_id }}" data-epic="{{ $story->parent_id }}">{{ $story->issue_key ? $story->issue_key . ' - ' : '' }}{{ Str::limit($story->name, 20) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg col-md-4">
                <label>{{__('Issue Type')}}</label>
                <select class="form-select" id="filter_issue_type">
                    <option value="">{{__('All Types')}}</option>
                    @foreach(\App\Models\IssueType::where('is_active', true)->orderBy('order')->get() as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg col-md-4">
                <label>{{__('Assignee')}}</label>
                <select class="form-select" id="filter_user">
                    <option value="">{{__('All Users')}}</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg col-md-4">
                <label>{{__('Priority')}}</label>
                <select class="form-select" id="filter_priority">
                    <option value="">{{__('All Priorities')}}</option>
                    @foreach(\App\Models\ProjectTask::$priority as $key => $val)
                        <option value="{{ $key }}">{{ __($val) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-12">
                <div class="quick-filters">
                    <button class="quick-filter-btn" data-filter="all">{{__('All')}}</button>
                    <button class="quick-filter-btn active" data-filter="my_tasks">{{__('My Tasks')}}</button>
                    <button class="quick-filter-btn" data-filter="overdue">{{__('Overdue')}}</button>
                    <button class="quick-filter-btn" data-filter="due_today">{{__('Due Today')}}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="kanban-board">
        @forelse($stages as $stage)
            <div class="kanban-column" data-stage-id="{{ $stage->id }}">
                <div class="kanban-column-header">
                    <h6>
                        <span style="width: 8px; height: 8px; border-radius: 50%; background: {{ $stage->color ?? '#5e6c84' }}; display: inline-block;"></span>
                        {{ $stage->name }}
                    </h6>
                    <span class="task-count">{{ count($stage->tasks) }}</span>
                </div>
                <div class="kanban-column-body" id="stage-{{ $stage->id }}">
                    @forelse($stage->tasks as $task)
                        <?php
                            $taskEpicId = '';
                            $taskStoryId = '';
                            $parentEpic = null;
                            $parentStory = null;
                            // Check if task has a parent
                            if ($task->parent_id) {
                                $parent = \App\Models\ProjectTask::find($task->parent_id);
                                if ($parent && $parent->issueType) {
                                    if ($parent->issueType->name == 'Epic') {
                                        $taskEpicId = $parent->id;
                                        $parentEpic = $parent;
                                    } elseif ($parent->issueType->name == 'Story') {
                                        $taskStoryId = $parent->id;
                                        $parentStory = $parent;
                                        // Story might have Epic as parent
                                        if ($parent->parent_id) {
                                            $taskEpicId = $parent->parent_id;
                                            $parentEpic = \App\Models\ProjectTask::find($parent->parent_id);
                                        }
                                    }
                                }
                            }
                            // Calculate due date status
                            $dueDateClass = '';
                            $dueDateIcon = '';
                            if (!empty($task->end_date) && $task->end_date != '0000-00-00') {
                                $daysUntilDue = (int) \Carbon\Carbon::parse($task->end_date)->diffInDays(now(), false);
                                if ($daysUntilDue > 0) {
                                    $dueDateClass = 'due-overdue';
                                    $dueDateIcon = 'ti-alert-circle';
                                } elseif ($daysUntilDue == 0) {
                                    $dueDateClass = 'due-today';
                                    $dueDateIcon = 'ti-clock';
                                } elseif ($daysUntilDue >= -2) {
                                    $dueDateClass = 'due-soon';
                                    $dueDateIcon = 'ti-clock';
                                }
                            }
                        ?>
                        <div class="task-card" data-task-id="{{ $task->id }}" data-project-id="{{ $task->project_id }}" data-user-id="{{ $task->assign_to }}" data-priority="{{ $task->priority }}" data-epic-id="{{ $taskEpicId }}" data-story-id="{{ $taskStoryId }}" data-issue-type="{{ $task->issue_type_id }}">
                            <div class="task-card-body">
                                @if($task->estimated_hours > 0)
                                    <span class="task-hours-display" data-bs-toggle="tooltip" title="{{ __('Estimated Hours') }}">
                                        <i class="ti ti-clock"></i>{{ $task->estimated_hours }}h
                                    </span>
                                @endif
                                <div class="task-card-meta">
                                    @if($task->issue_key)
                                        <span class="issue-key">{{ $task->issue_key }}</span>
                                    @endif
                                    @if($task->project)
                                        <span class="task-badge project" title="{{ $task->project->project_name }}">
                                            <i class="ti ti-folder"></i> {{ Str::limit($task->project->project_name, 15) }}
                                        </span>
                                    @endif
                                </div>
                                <h6 class="task-card-title">
                                    <a href="#" data-url="{{ route('projects.tasks.show', [$task->project_id, $task->id]) }}" data-ajax-popup="true" data-size="lg" data-bs-original-title="{{ $task->name }}">
                                        {{ $task->name }}
                                    </a>
                                </h6>
                                @if($parentEpic || $parentStory)
                                    <div class="parent-info">
                                        @if($parentEpic)
                                            <span class="parent-badge epic" data-bs-toggle="tooltip" title="{{ $parentEpic->name }}">
                                                <i class="ti ti-bolt"></i> {{ $parentEpic->issue_key ?? Str::limit($parentEpic->name, 12) }}
                                            </span>
                                        @endif
                                        @if($parentStory)
                                            <span class="parent-badge story" data-bs-toggle="tooltip" title="{{ $parentStory->name }}">
                                                <i class="ti ti-bookmark"></i> {{ $parentStory->issue_key ?? Str::limit($parentStory->name, 12) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <div class="task-card-meta">
                                    @if($task->issueType)
                                        <span class="task-badge issue-type" style="background: {{ $task->issueType->color ?? '#5e6c84' }}; {{ (strtolower($task->issueType->color ?? '') == '#ffffff' || strtolower($task->issueType->color ?? '') == '#fff' || strtolower($task->issueType->color ?? '') == 'white') ? 'color: #333; text-shadow: none;' : '' }}">
                                            <i class="{{ $task->issueType->icon ?? 'ti ti-subtask' }}"></i> {{ $task->issueType->name }}
                                        </span>
                                    @else
                                        <span class="task-badge task-type-default">
                                            <i class="ti ti-subtask"></i> {{ __('Task') }}
                                        </span>
                                    @endif
                                    <span class="task-badge priority-{{ strtolower(\App\Models\ProjectTask::$priority[$task->priority] ?? 'medium') }}">
                                        {{ __(\App\Models\ProjectTask::$priority[$task->priority] ?? 'Medium') }}
                                    </span>
                                    @if($task->estimated_hours > 0)
                                        <span class="task-badge hours">
                                            <i class="ti ti-hourglass"></i> {{ $task->estimated_hours }}h
                                        </span>
                                    @endif
                                </div>
                                @php
                                    $progress = $task->taskProgress($task);
                                    $percentage = str_replace('%', '', $progress['percentage']);
                                @endphp
                                @if($percentage > 0)
                                    <div class="task-progress">
                                        <div class="task-progress-bar bg-{{ $progress['color'] }}" style="width: {{ $progress['percentage'] }}"></div>
                                    </div>
                                @endif
                                <div class="task-card-footer">
                                    <div class="task-card-icons">
                                        @if(count($task->taskFiles) > 0)
                                            <span><i class="ti ti-paperclip"></i> {{ count($task->taskFiles) }}</span>
                                        @endif
                                        @if(count($task->comments) > 0)
                                            <span><i class="ti ti-message"></i> {{ count($task->comments) }}</span>
                                        @endif
                                        @if($task->checklist->count() > 0)
                                            <span><i class="ti ti-list-check"></i> {{ $task->countTaskChecklist() }}</span>
                                        @endif
                                        @if(!empty($task->end_date) && $task->end_date != '0000-00-00')
                                            <span class="task-due-date {{ $dueDateClass }}" data-bs-toggle="tooltip" title="{{ __('Due: ') . \Carbon\Carbon::parse($task->end_date)->format('M d, Y') }}">
                                                @if($dueDateIcon)<i class="ti {{ $dueDateIcon }}"></i>@else<i class="ti ti-calendar"></i>@endif
                                                {{ \Carbon\Carbon::parse($task->end_date)->format('M d') }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="task-avatars">
                                        @php $users = $task->users(); @endphp
                                        @foreach($users as $key => $user)
                                            @if($key < 2)
                                                <img src="{{ $user->avatar ? asset('/storage/uploads/avatar/'.$user->avatar) : asset('/storage/uploads/avatar/avatar.png') }}"
                                                     alt="{{ $user->name }}" title="{{ $user->name }}" data-bs-toggle="tooltip">
                                            @endif
                                        @endforeach
                                        @if(count($users) > 2)
                                            <span class="task-badge" style="margin-left: -6px; background: #dfe1e6; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 10px;">+{{ count($users) - 2 }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="kanban-empty">
                            {{__('No tasks')}}
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <h6>{{__('No stages found. Please create task stages first.')}}</h6>
            </div>
        @endforelse
    </div>
@endsection

@push('script-page')
    <script src="{{ asset('assets/js/plugins/dragula.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // ===== DRAG-SCROLL FUNCTIONALITY =====
            // Enables horizontal scrolling when dragging cards near edges
            var kanbanBoard = document.querySelector('.kanban-board');
            var scrollSpeed = 15;
            var scrollZone = 100; // pixels from edge to trigger scroll
            var scrollInterval = null;
            var isDragging = false;

            if (kanbanBoard) {
                // Mouse wheel horizontal scroll - only when not over a column body
                kanbanBoard.addEventListener('wheel', function(e) {
                    // Check if mouse is over a scrollable column body
                    var target = e.target;
                    var columnBody = target.closest('.kanban-column-body');

                    // If over a column body that has scrollable content, let it scroll vertically
                    if (columnBody) {
                        var hasVerticalScroll = columnBody.scrollHeight > columnBody.clientHeight;
                        if (hasVerticalScroll) {
                            // Allow normal vertical scroll within column
                            return;
                        }
                    }

                    // Otherwise, convert to horizontal scroll
                    if (e.deltaY !== 0) {
                        e.preventDefault();
                        kanbanBoard.scrollLeft += e.deltaY;
                    }
                }, { passive: false });

                // Auto-scroll during drag
                document.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;

                    var rect = kanbanBoard.getBoundingClientRect();
                    var mouseX = e.clientX - rect.left;

                    clearInterval(scrollInterval);

                    if (mouseX < scrollZone) {
                        // Scroll left
                        scrollInterval = setInterval(function() {
                            kanbanBoard.scrollLeft -= scrollSpeed;
                        }, 16);
                    } else if (mouseX > rect.width - scrollZone) {
                        // Scroll right
                        scrollInterval = setInterval(function() {
                            kanbanBoard.scrollLeft += scrollSpeed;
                        }, 16);
                    }
                });

                document.addEventListener('mouseup', function() {
                    isDragging = false;
                    clearInterval(scrollInterval);
                    kanbanBoard.classList.remove('dragging-active');
                });
            }

            // Initialize Dragula for drag and drop
            var containers = [];
            $('.kanban-column-body').each(function() {
                containers.push(this);
            });

            var drake = dragula(containers, {
                moves: function(el, container, handle) {
                    return !el.classList.contains('kanban-empty');
                }
            });

            // Track dragging state for scroll
            drake.on('drag', function(el) {
                isDragging = true;
                if (kanbanBoard) kanbanBoard.classList.add('dragging-active');
            });

            drake.on('dragend', function(el) {
                isDragging = false;
                clearInterval(scrollInterval);
                if (kanbanBoard) kanbanBoard.classList.remove('dragging-active');
            });

            drake.on('drop', function(el, target, source, sibling) {
                var taskId = $(el).data('task-id');
                var newStageId = $(target).closest('.kanban-column').data('stage-id');
                var oldStageId = $(source).closest('.kanban-column').data('stage-id');

                if (newStageId !== oldStageId) {
                    // Update task stage via AJAX
                    $.ajax({
                        url: '{{ route("task.update.stage") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            task_id: taskId,
                            stage_id: newStageId
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update counts
                                updateStageCounts();
                                show_toastr('success', response.message, 'success');
                            }
                        },
                        error: function() {
                            show_toastr('error', '{{__("Failed to update task stage")}}', 'error');
                            // Revert the move
                            $(source).append(el);
                        }
                    });
                }
            });

            // Update stage counts
            function updateStageCounts() {
                $('.kanban-column').each(function() {
                    var count = $(this).find('.task-card').length;
                    $(this).find('.task-count').text(count);
                });
            }

            // Filter functionality
            function filterTasks() {
                var search = $('#task_search').val().toLowerCase();
                var project = $('#filter_project').val();
                var epic = $('#filter_epic').val();
                var story = $('#filter_story').val();
                var issueType = $('#filter_issue_type').val();
                var user = $('#filter_user').val();
                var priority = $('#filter_priority').val();
                var quickFilter = $('.quick-filter-btn.active').data('filter');
                var today = new Date().toISOString().split('T')[0];

                $('.task-card').each(function() {
                    var $card = $(this);
                    var taskTitle = $card.find('.task-card-title').text().toLowerCase();
                    var taskProject = $card.data('project-id').toString();
                    var taskUser = $card.data('user-id') ? $card.data('user-id').toString() : '';
                    var taskPriority = $card.data('priority').toString();
                    var taskEpic = $card.data('epic-id') ? $card.data('epic-id').toString() : '';
                    var taskStory = $card.data('story-id') ? $card.data('story-id').toString() : '';
                    var taskIssueType = $card.data('issue-type') ? $card.data('issue-type').toString() : '';
                    var isOverdue = $card.find('.task-due-date.overdue').length > 0;
                    var dueDate = $card.find('.task-due-date').text().trim();

                    var show = true;

                    // Search filter
                    if (search && taskTitle.indexOf(search) === -1) {
                        show = false;
                    }

                    // Project filter
                    if (project && taskProject !== project) {
                        show = false;
                    }

                    // Epic filter
                    if (epic && taskEpic !== epic) {
                        show = false;
                    }

                    // Story filter
                    if (story && taskStory !== story) {
                        show = false;
                    }

                    // Issue Type filter
                    if (issueType && taskIssueType !== issueType) {
                        show = false;
                    }

                    // User filter
                    if (user && taskUser.indexOf(user) === -1) {
                        show = false;
                    }

                    // Priority filter
                    if (priority && taskPriority !== priority) {
                        show = false;
                    }

                    // Quick filters
                    if (quickFilter === 'my_tasks') {
                        var currentUserId = '{{ Auth::user()->id }}';
                        if (taskUser.indexOf(currentUserId) === -1) {
                            show = false;
                        }
                    } else if (quickFilter === 'overdue' && !isOverdue) {
                        show = false;
                    } else if (quickFilter === 'due_today') {
                        // Check if due today
                        var cardDueDate = $card.find('.task-due-date').length > 0;
                        if (!cardDueDate) show = false;
                    }

                    $card.toggle(show);
                });

                // Show/hide empty message
                $('.kanban-column-body').each(function() {
                    var visibleCards = $(this).find('.task-card:visible').length;
                    var emptyMsg = $(this).find('.kanban-empty');
                    if (visibleCards === 0 && emptyMsg.length === 0) {
                        $(this).append('<div class="kanban-empty">{{__("No tasks")}}</div>');
                    } else if (visibleCards > 0) {
                        emptyMsg.remove();
                    }
                });

                updateStageCounts();
            }

            // Filter epics/stories based on selected project
            $('#filter_project').on('change', function() {
                var selectedProject = $(this).val();

                // Filter epics dropdown
                $('#filter_epic option').each(function() {
                    if ($(this).val() === '') {
                        $(this).show();
                    } else if (selectedProject === '' || $(this).data('project').toString() === selectedProject) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                $('#filter_epic').val('');

                // Filter stories dropdown
                $('#filter_story option').each(function() {
                    if ($(this).val() === '') {
                        $(this).show();
                    } else if (selectedProject === '' || $(this).data('project').toString() === selectedProject) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                $('#filter_story').val('');

                filterTasks();
            });

            // Filter stories based on selected epic
            $('#filter_epic').on('change', function() {
                var selectedEpic = $(this).val();

                // Filter stories dropdown based on selected epic
                $('#filter_story option').each(function() {
                    if ($(this).val() === '') {
                        $(this).show();
                    } else if (selectedEpic === '') {
                        $(this).show();
                    } else if ($(this).data('epic') && $(this).data('epic').toString() === selectedEpic) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                $('#filter_story').val('');

                filterTasks();
            });

            // Event listeners for filters
            $('#task_search').on('keyup', filterTasks);
            $('#filter_story, #filter_issue_type, #filter_user, #filter_priority').on('change', filterTasks);

            // Quick filter buttons
            $('.quick-filter-btn').on('click', function() {
                $('.quick-filter-btn').removeClass('active');
                $(this).addClass('active');
                filterTasks();
            });

            // Apply default filter (My Tasks) on page load
            filterTasks();
        });
    </script>
@endpush
