@extends('layouts.admin')
@section('page-title')
    {{ $sprint->name }} - {{ __('Sprint Board') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">{{ __('Projects') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->id) }}">{{ $project->project_name }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sprints.planning', $project->id) }}">{{ __('Sprint Planning') }}</a></li>
    <li class="breadcrumb-item">{{ $sprint->name }}</li>
@endsection

@section('action-btn')
    <div class="float-end d-flex align-items-center gap-2">
        <!-- Sprint Selector -->
        <select class="form-select form-select-sm" id="sprint-selector" style="width: auto;">
            @foreach($allSprints as $s)
                <option value="{{ $s->id }}" {{ $s->id == $sprint->id ? 'selected' : '' }}>
                    {{ $s->name }} ({{ \App\Models\Sprint::$statuses[$s->status] }})
                </option>
            @endforeach
        </select>

        <a href="{{ route('sprints.planning', $project->id) }}" class="btn btn-sm btn-secondary">
            <i class="ti ti-arrow-left"></i> {{ __('Back to Planning') }}
        </a>
    </div>
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dragula.min.css') }}">
    <style>
        /* Sprint Header Bar */
        .sprint-header-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            color: #fff;
        }

        .sprint-header-bar h4 {
            margin: 0;
            font-weight: 700;
        }

        .sprint-header-bar p {
            margin: 4px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .sprint-progress {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sprint-progress .progress {
            width: 200px;
            height: 8px;
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
        }

        .sprint-progress .progress-bar {
            background: #fff;
            border-radius: 4px;
        }

        .sprint-days {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Burndown Mini */
        .burndown-mini {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .burndown-mini h6 {
            margin: 0 0 12px 0;
            font-weight: 600;
            color: #1e293b;
        }

        /* Kanban Board */
        .kanban-wrapper {
            display: flex;
            gap: 16px;
            overflow-x: auto;
            padding-bottom: 20px;
        }

        .kanban-wrapper > .col {
            flex: 0 0 300px;
            max-width: 300px;
            min-width: 300px;
            padding: 0;
        }

        .crm-sales-card {
            background: #f8fafc;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .crm-sales-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            padding: 14px 18px;
            min-height: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kanban-wrapper > .col:nth-child(1) .crm-sales-card .card-header { background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%); }
        .kanban-wrapper > .col:nth-child(2) .crm-sales-card .card-header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .kanban-wrapper > .col:nth-child(3) .crm-sales-card .card-header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .kanban-wrapper > .col:nth-child(4) .crm-sales-card .card-header { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .kanban-wrapper > .col:nth-child(5) .crm-sales-card .card-header { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

        .crm-sales-card .card-header h4 {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            color: #fff;
            margin: 0;
        }

        .count {
            background: rgba(255,255,255,0.25);
            padding: 3px 10px;
            font-size: 12px;
            font-weight: 600;
            color: #fff;
            border-radius: 15px;
        }

        .kanban-box {
            min-height: 250px;
            padding: 12px;
            max-height: calc(100vh - 350px);
            overflow-y: auto;
            background: #f8fafc;
        }

        /* Board Card */
        .board-card {
            background: #fff;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            cursor: grab;
            transition: all 0.2s ease;
            border-left: 4px solid #e2e8f0;
        }

        .board-card:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .board-card.priority-critical { border-left-color: #ef4444; }
        .board-card.priority-high { border-left-color: #f59e0b; }
        .board-card.priority-medium { border-left-color: #3b82f6; }
        .board-card.priority-low { border-left-color: #10b981; }

        .board-card-key {
            font-size: 11px;
            color: #6366f1;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }

        .board-card-title {
            font-size: 13px;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .board-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .board-card-type {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .board-card-type .icon {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #fff;
        }

        .board-card-points {
            background: #e0e7ff;
            color: #4338ca;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }

        /* Dragula states */
        .gu-mirror { opacity: 0.9; transform: rotate(3deg); }
        .gu-transit { opacity: 0.4; }

        /* Bugs Section */
        .bugs-board-section {
            margin-top: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .bugs-board-header {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #fecaca;
        }

        .bugs-board-header h5 {
            margin: 0;
            color: #991b1b;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bugs-board-content {
            padding: 16px;
        }

        .bug-status-columns {
            display: flex;
            gap: 16px;
            overflow-x: auto;
        }

        .bug-status-column {
            flex: 0 0 260px;
            min-width: 260px;
            background: #fafafa;
            border-radius: 10px;
            overflow: hidden;
        }

        .bug-status-header {
            background: #fee2e2;
            padding: 10px 14px;
            font-weight: 600;
            font-size: 12px;
            color: #991b1b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bug-status-box {
            min-height: 100px;
            padding: 10px;
            max-height: 300px;
            overflow-y: auto;
        }

        .board-bug-card {
            background: #fff;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            cursor: grab;
            border-left: 4px solid #ef4444;
            transition: all 0.2s ease;
        }

        .board-bug-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
        }

        .board-bug-card.severity-critical { border-left-color: #dc2626; background: #fef2f2; }
        .board-bug-card.severity-major { border-left-color: #ea580c; background: #fff7ed; }
        .board-bug-card.severity-minor { border-left-color: #f59e0b; }
        .board-bug-card.severity-trivial { border-left-color: #94a3b8; }

        .board-bug-card-key {
            font-size: 10px;
            color: #ef4444;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
        }

        .board-bug-card-title {
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .board-bug-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .severity-badge-sm {
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: 600;
        }

        .severity-badge-sm.critical { background: #fef2f2; color: #dc2626; }
        .severity-badge-sm.major { background: #fff7ed; color: #ea580c; }
        .severity-badge-sm.minor { background: #fefce8; color: #ca8a04; }
        .severity-badge-sm.trivial { background: #f1f5f9; color: #64748b; }
    </style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Sprint Header -->
        <div class="sprint-header-bar">
            <div class="sprint-info">
                <h4>{{ $sprint->name }}</h4>
                @if($sprint->goal)
                    <p>{{ $sprint->goal }}</p>
                @endif
            </div>
            <div class="sprint-progress">
                <div class="progress">
                    <div class="progress-bar" style="width: {{ $sprint->progress_percentage }}%"></div>
                </div>
                <span>{{ $sprint->completed_story_points }}/{{ $sprint->total_story_points }} pts ({{ $sprint->progress_percentage }}%)</span>
            </div>
            <div class="sprint-days">
                @if($sprint->isActive())
                    <i class="ti ti-clock"></i> {{ $sprint->days_remaining }} {{ __('days remaining') }}
                @else
                    <span class="badge bg-{{ \App\Models\Sprint::$statusColors[$sprint->status] }}">
                        {{ \App\Models\Sprint::$statuses[$sprint->status] }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Burndown Chart -->
        <div class="burndown-mini">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6><i class="ti ti-chart-line"></i> {{ __('Burndown Chart') }}</h6>
                <div>
                    <span class="text-muted me-3"><span class="text-primary">---</span> {{ __('Ideal') }}</span>
                    <span class="text-muted"><span class="text-success">___</span> {{ __('Actual') }}</span>
                </div>
            </div>
            <div id="burndown-chart" style="height: 200px;"></div>
        </div>

        <!-- Kanban Board -->
        <div class="kanban-wrapper" data-plugin="dragula">
            @foreach($stages as $stage)
                <div class="col">
                    <div class="crm-sales-card">
                        <div class="card-header">
                            <h4>{{ $stage->name }}</h4>
                            <span class="count" id="count-{{ $stage->id }}">{{ $stage->sprintTasks->count() }}</span>
                        </div>
                        <div class="kanban-box" id="stage-{{ $stage->id }}" data-stage-id="{{ $stage->id }}">
                            @foreach($stage->sprintTasks as $task)
                                @include('sprints.partials.board-card', ['task' => $task])
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Bugs Section -->
        @if($sprintBugs->count() > 0 || $bugStatuses->count() > 0)
            <div class="bugs-board-section">
                <div class="bugs-board-header">
                    <h5><i class="ti ti-bug"></i> {{ __('Sprint Bugs') }} ({{ $sprintBugs->count() }})</h5>
                    <span class="text-muted">{{ __('Drag bugs between statuses') }}</span>
                </div>
                <div class="bugs-board-content">
                    <div class="bug-status-columns">
                        @foreach($bugStatuses as $status)
                            @php
                                $statusBugs = $sprintBugs->where('status', $status->id);
                            @endphp
                            <div class="bug-status-column">
                                <div class="bug-status-header">
                                    <span>{{ $status->title }}</span>
                                    <span class="badge bg-light text-dark bug-status-count" id="bug-count-{{ $status->id }}">{{ $statusBugs->count() }}</span>
                                </div>
                                <div class="bug-status-box" data-status-id="{{ $status->id }}">
                                    @foreach($statusBugs as $bug)
                                        <div class="board-bug-card severity-{{ $bug->severity ?? 'minor' }}" data-bug-id="{{ $bug->id }}">
                                            <span class="board-bug-card-key">{{ $bug->bug_id }}</span>
                                            <div class="board-bug-card-title">{{ Str::limit($bug->title, 60) }}</div>
                                            <div class="board-bug-card-meta">
                                                <span class="severity-badge-sm {{ $bug->severity ?? 'minor' }}">{{ ucfirst($bug->severity ?? 'minor') }}</span>
                                                <span class="board-card-points">{{ $bug->story_points ?? 0 }} pts</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('script-page')
    <script src="{{ asset('assets/js/plugins/dragula.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>
    <script>
        var csrfToken = '{{ csrf_token() }}';
        var projectId = {{ $project->id }};
        var sprintId = {{ $sprint->id }};

        $(document).ready(function() {
            // Sprint selector
            $('#sprint-selector').on('change', function() {
                var selectedSprintId = $(this).val();
                window.location.href = '/projects/' + projectId + '/sprint-board/' + selectedSprintId;
            });

            // Initialize Kanban Dragula
            var containers = [];
            document.querySelectorAll('.kanban-box').forEach(function(el) {
                containers.push(el);
            });

            var drake = dragula(containers, {
                moves: function(el) {
                    return el.classList.contains('board-card');
                }
            });

            drake.on('drop', function(el, target, source) {
                var taskId = el.getAttribute('data-task-id');
                var stageId = target.getAttribute('data-stage-id');
                var order = Array.from(target.children).filter(c => c.classList.contains('board-card')).indexOf(el);

                $.ajax({
                    url: '{{ route("sprints.update-task-stage") }}',
                    type: 'POST',
                    data: {
                        task_id: taskId,
                        stage_id: stageId,
                        order: order,
                        _token: csrfToken
                    },
                    success: function(response) {
                        updateStageCounts();
                        show_toastr('success', response.message || 'Task updated', 'success');
                        // Refresh burndown
                        fetchBurndownData();
                    },
                    error: function() {
                        show_toastr('error', 'Failed to update task', 'error');
                        drake.cancel(true);
                    }
                });
            });

            function updateStageCounts() {
                document.querySelectorAll('.kanban-box').forEach(function(box) {
                    var stageId = box.getAttribute('data-stage-id');
                    var count = box.querySelectorAll('.board-card').length;
                    $('#count-' + stageId).text(count);
                });
            }

            // Initialize Bug Dragula
            var bugContainers = [];
            document.querySelectorAll('.bug-status-box').forEach(function(el) {
                bugContainers.push(el);
            });

            if (bugContainers.length > 0) {
                var drakeBug = dragula(bugContainers, {
                    moves: function(el) {
                        return el.classList.contains('board-bug-card');
                    }
                });

                drakeBug.on('drop', function(el, target, source) {
                    var bugId = el.getAttribute('data-bug-id');
                    var statusId = target.getAttribute('data-status-id');
                    var order = Array.from(target.children).filter(c => c.classList.contains('board-bug-card')).indexOf(el);

                    $.ajax({
                        url: '{{ route("sprints.update-bug-status") }}',
                        type: 'POST',
                        data: {
                            bug_id: bugId,
                            status_id: statusId,
                            order: order,
                            _token: csrfToken
                        },
                        success: function(response) {
                            updateBugStatusCounts();
                            show_toastr('success', response.message || 'Bug status updated', 'success');
                            // Refresh burndown
                            fetchBurndownData();
                        },
                        error: function() {
                            show_toastr('error', 'Failed to update bug status', 'error');
                            drakeBug.cancel(true);
                        }
                    });
                });
            }

            function updateBugStatusCounts() {
                document.querySelectorAll('.bug-status-box').forEach(function(box) {
                    var statusId = box.getAttribute('data-status-id');
                    var count = box.querySelectorAll('.board-bug-card').length;
                    $('#bug-count-' + statusId).text(count);
                });
            }

            // Burndown Chart
            var burndownData = @json($burndownData);

            var burndownOptions = {
                chart: {
                    type: 'line',
                    height: 200,
                    toolbar: { show: false },
                    animations: { enabled: true }
                },
                series: [
                    {
                        name: '{{ __("Ideal") }}',
                        data: burndownData.ideal
                    },
                    {
                        name: '{{ __("Actual") }}',
                        data: burndownData.actual.filter(v => v !== null)
                    }
                ],
                xaxis: {
                    categories: burndownData.dates,
                    labels: { style: { fontSize: '11px' } }
                },
                yaxis: {
                    min: 0,
                    labels: { style: { fontSize: '11px' } }
                },
                colors: ['#94a3b8', '#10b981'],
                stroke: {
                    curve: 'straight',
                    width: [2, 3],
                    dashArray: [5, 0]
                },
                markers: {
                    size: [0, 5]
                },
                legend: {
                    show: false
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 3
                }
            };

            var burndownChart = new ApexCharts(document.querySelector("#burndown-chart"), burndownOptions);
            burndownChart.render();

            function fetchBurndownData() {
                $.get('/projects/' + projectId + '/sprints/' + sprintId + '/burndown', function(data) {
                    burndownChart.updateSeries([
                        { name: '{{ __("Ideal") }}', data: data.ideal },
                        { name: '{{ __("Actual") }}', data: data.actual.filter(v => v !== null) }
                    ]);
                });
            }
        });
    </script>
@endpush
