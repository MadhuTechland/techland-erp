@extends('layouts.admin')

@section('page-title')
    {{ __('Resource Timeline') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Resource Timeline') }}</li>
@endsection

@push('css-page')
<style>
    .timeline-container {
        background: var(--bs-card-bg, #fff);
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .timeline-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        flex-wrap: wrap;
        gap: 15px;
    }

    .timeline-controls {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .view-toggle {
        display: flex;
        background: rgba(108, 117, 125, 0.1);
        border-radius: 8px;
        padding: 4px;
    }

    .view-toggle button {
        padding: 8px 16px;
        border: none;
        background: transparent;
        border-radius: 6px;
        font-weight: 500;
        color: #6c757d;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .view-toggle button.active {
        background: var(--bs-primary, #6366f1);
        color: white;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    }

    .date-nav {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .date-nav button {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        background: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .date-nav button:hover {
        background: var(--bs-primary, #6366f1);
        color: white;
        border-color: var(--bs-primary, #6366f1);
    }

    .date-picker-wrapper {
        position: relative;
    }

    .date-picker-wrapper input {
        padding: 8px 16px;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        font-weight: 500;
        min-width: 160px;
        cursor: pointer;
    }

    .today-btn {
        padding: 8px 16px;
        border: 1px solid var(--bs-primary, #6366f1);
        background: transparent;
        color: var(--bs-primary, #6366f1);
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .today-btn:hover {
        background: var(--bs-primary, #6366f1);
        color: white;
    }

    /* Summary Stats */
    .timeline-summary {
        display: flex;
        gap: 20px;
        padding: 15px 24px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(168, 85, 247, 0.05) 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        flex-wrap: wrap;
    }

    .summary-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .summary-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .summary-icon.total { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
    .summary-icon.assigned { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
    .summary-icon.unassigned { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

    .summary-text {
        display: flex;
        flex-direction: column;
    }

    .summary-value {
        font-size: 20px;
        font-weight: 700;
        line-height: 1.2;
    }

    .summary-label {
        font-size: 12px;
        color: #6c757d;
    }

    /* Timeline Grid */
    .timeline-grid-wrapper {
        overflow-x: auto;
        position: relative;
    }

    .timeline-grid {
        display: table;
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
    }

    .timeline-row {
        display: table-row;
    }

    .timeline-row:hover .timeline-user-cell {
        background: rgba(99, 102, 241, 0.03);
    }

    .timeline-row.no-tasks {
        background: linear-gradient(90deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.03) 100%);
    }

    .timeline-row.no-tasks .timeline-user-cell {
        border-left: 3px solid #ef4444;
    }

    .timeline-header-row {
        background: rgba(0, 0, 0, 0.02);
    }

    .timeline-cell {
        display: table-cell;
        padding: 12px 8px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        vertical-align: middle;
        position: relative;
    }

    .timeline-header-cell {
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        color: #6c757d;
        text-align: center;
        padding: 16px 8px;
        min-width: 80px;
        border-bottom: 2px solid rgba(0, 0, 0, 0.08);
    }

    .timeline-header-cell.today {
        background: rgba(99, 102, 241, 0.1);
        color: var(--bs-primary, #6366f1);
    }

    .timeline-header-cell .day-number {
        display: block;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .timeline-user-cell {
        width: 220px;
        min-width: 220px;
        padding: 12px 16px;
        position: sticky;
        left: 0;
        background: var(--bs-card-bg, #fff);
        z-index: 10;
        border-right: 1px solid rgba(0, 0, 0, 0.08);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        object-fit: cover;
        border: 2px solid rgba(0, 0, 0, 0.08);
    }

    .user-details {
        flex: 1;
        min-width: 0;
    }

    .user-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .user-stats {
        font-size: 11px;
        color: #6c757d;
    }

    .no-task-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 8px;
    }

    /* Task Blocks Container */
    .timeline-tasks-cell {
        position: relative;
        min-height: 56px;
    }

    .tasks-row {
        display: flex;
        gap: 4px;
        flex-wrap: nowrap;
        position: relative;
        min-height: 48px;
        align-items: center;
    }

    /* Task Block */
    .task-block {
        position: absolute;
        top: 4px;
        height: calc(100% - 8px);
        min-height: 40px;
        border-radius: 8px;
        padding: 6px 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow: hidden;
        z-index: 5;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .task-block:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 20;
    }

    .task-block.priority-critical { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; }
    .task-block.priority-high { background: linear-gradient(135deg, #fd7e14 0%, #e96b02 100%); color: white; }
    .task-block.priority-medium { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #333; }
    .task-block.priority-low { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; }

    .task-block.completed {
        opacity: 0.7;
        text-decoration: line-through;
    }

    .task-name {
        font-weight: 600;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }

    .task-meta {
        font-size: 10px;
        opacity: 0.85;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .task-progress {
        height: 3px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
        margin-top: 4px;
        overflow: hidden;
    }

    .task-progress-bar {
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 2px;
        transition: width 0.3s ease;
    }

    /* Empty state message in row */
    .empty-row-message {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        color: #ef4444;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Loading State */
    .timeline-loading {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
        flex-direction: column;
        gap: 15px;
    }

    .timeline-loading .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(99, 102, 241, 0.2);
        border-top-color: var(--bs-primary, #6366f1);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Task Detail Popup */
    .task-popup {
        position: fixed;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        padding: 20px;
        z-index: 1000;
        min-width: 280px;
        max-width: 320px;
        display: none;
    }

    .task-popup.show {
        display: block;
    }

    .task-popup-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .task-popup-title {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 4px;
    }

    .task-popup-project {
        font-size: 12px;
        color: #6c757d;
    }

    .task-popup-close {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #6c757d;
        padding: 0;
        line-height: 1;
    }

    .task-popup-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 15px;
    }

    .task-stat {
        background: rgba(0, 0, 0, 0.03);
        padding: 10px;
        border-radius: 8px;
    }

    .task-stat-label {
        font-size: 11px;
        color: #6c757d;
        margin-bottom: 2px;
    }

    .task-stat-value {
        font-weight: 600;
        font-size: 14px;
    }

    .task-popup-progress {
        margin-bottom: 15px;
    }

    .task-popup-progress-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
        font-size: 12px;
    }

    .task-popup-progress-bar {
        height: 8px;
        background: rgba(0, 0, 0, 0.08);
        border-radius: 4px;
        overflow: hidden;
    }

    .task-popup-progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .task-popup-actions {
        display: flex;
        gap: 10px;
    }

    .task-popup-actions a {
        flex: 1;
        text-align: center;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
    }

    .task-popup-actions .btn-view {
        background: var(--bs-primary, #6366f1);
        color: white;
    }

    .task-popup-actions .btn-view:hover {
        background: #4f46e5;
    }

    /* Filter dropdown */
    .project-filter {
        min-width: 180px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .timeline-header {
            padding: 15px;
        }

        .timeline-controls {
            width: 100%;
            justify-content: center;
        }

        .timeline-user-cell {
            width: 150px;
            min-width: 150px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
        }
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="timeline-container">
            <!-- Header -->
            <div class="timeline-header">
                <div class="timeline-controls">
                    <div class="view-toggle">
                        <button type="button" id="dailyViewBtn" class="active">{{ __('Daily') }}</button>
                        <button type="button" id="weeklyViewBtn">{{ __('Weekly') }}</button>
                    </div>

                    <div class="date-nav">
                        <button type="button" id="prevBtn" title="{{ __('Previous') }}">
                            <i class="ti ti-chevron-left"></i>
                        </button>
                        <div class="date-picker-wrapper">
                            <input type="text" id="timelineDatePicker" class="form-control" readonly>
                        </div>
                        <button type="button" id="nextBtn" title="{{ __('Next') }}">
                            <i class="ti ti-chevron-right"></i>
                        </button>
                    </div>

                    <button type="button" class="today-btn" id="todayBtn">{{ __('Today') }}</button>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <select class="form-select project-filter" id="projectFilter">
                        <option value="">{{ __('All Projects') }}</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="timeline-summary">
                <div class="summary-item">
                    <div class="summary-icon total">
                        <i class="ti ti-users"></i>
                    </div>
                    <div class="summary-text">
                        <span class="summary-value" id="totalUsers">0</span>
                        <span class="summary-label">{{ __('Total Users') }}</span>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-icon assigned">
                        <i class="ti ti-user-check"></i>
                    </div>
                    <div class="summary-text">
                        <span class="summary-value" id="assignedUsers">0</span>
                        <span class="summary-label">{{ __('With Tasks') }}</span>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-icon unassigned">
                        <i class="ti ti-user-x"></i>
                    </div>
                    <div class="summary-text">
                        <span class="summary-value" id="unassignedUsers">0</span>
                        <span class="summary-label">{{ __('No Tasks') }}</span>
                    </div>
                </div>
            </div>

            <!-- Timeline Grid -->
            <div class="timeline-grid-wrapper">
                <div id="timelineGrid" class="timeline-grid">
                    <div class="timeline-loading">
                        <div class="spinner"></div>
                        <span>{{ __('Loading timeline...') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Detail Popup -->
<div class="task-popup" id="taskPopup">
    <div class="task-popup-header">
        <div>
            <div class="task-popup-title" id="popupTaskName"></div>
            <div class="task-popup-project" id="popupProjectName"></div>
        </div>
        <button class="task-popup-close" id="closePopup">&times;</button>
    </div>
    <div class="task-popup-stats">
        <div class="task-stat">
            <div class="task-stat-label">{{ __('Estimated') }}</div>
            <div class="task-stat-value" id="popupEstimated">0h</div>
        </div>
        <div class="task-stat">
            <div class="task-stat-label">{{ __('Actual') }}</div>
            <div class="task-stat-value" id="popupActual">0h</div>
        </div>
        <div class="task-stat">
            <div class="task-stat-label">{{ __('Priority') }}</div>
            <div class="task-stat-value" id="popupPriority">-</div>
        </div>
        <div class="task-stat">
            <div class="task-stat-label">{{ __('Stage') }}</div>
            <div class="task-stat-value" id="popupStage">-</div>
        </div>
    </div>
    <div class="task-popup-progress">
        <div class="task-popup-progress-header">
            <span>{{ __('Progress') }}</span>
            <span id="popupProgressPercent">0%</span>
        </div>
        <div class="task-popup-progress-bar">
            <div class="task-popup-progress-fill" id="popupProgressBar" style="width: 0%; background: #6366f1;"></div>
        </div>
    </div>
    <div class="task-popup-actions">
        <a href="#" class="btn-view" id="popupViewTask">{{ __('View Task') }}</a>
    </div>
</div>
@endsection

@push('script-page')
<script src="{{ asset('assets/js/plugins/flatpickr.min.js') }}"></script>
<script>
class ResourceTimeline {
    constructor() {
        this.viewType = 'daily';
        this.currentDate = new Date();
        this.data = null;
        this.datePicker = null;
        this.slotWidth = 80; // pixels per slot
    }

    init() {
        this.initDatePicker();
        this.bindEvents();
        this.loadData();
    }

    initDatePicker() {
        this.datePicker = flatpickr('#timelineDatePicker', {
            dateFormat: 'Y-m-d',
            defaultDate: this.currentDate,
            onChange: (selectedDates) => {
                if (selectedDates.length > 0) {
                    this.currentDate = selectedDates[0];
                    this.loadData();
                }
            }
        });
        this.updateDateDisplay();
    }

    bindEvents() {
        document.getElementById('dailyViewBtn').addEventListener('click', () => this.switchView('daily'));
        document.getElementById('weeklyViewBtn').addEventListener('click', () => this.switchView('weekly'));
        document.getElementById('prevBtn').addEventListener('click', () => this.navigate(-1));
        document.getElementById('nextBtn').addEventListener('click', () => this.navigate(1));
        document.getElementById('todayBtn').addEventListener('click', () => this.goToToday());
        document.getElementById('projectFilter').addEventListener('change', () => this.loadData());
        document.getElementById('closePopup').addEventListener('click', () => this.hidePopup());

        // Close popup when clicking outside
        document.addEventListener('click', (e) => {
            const popup = document.getElementById('taskPopup');
            if (!popup.contains(e.target) && !e.target.closest('.task-block')) {
                this.hidePopup();
            }
        });
    }

    switchView(type) {
        this.viewType = type;
        document.getElementById('dailyViewBtn').classList.toggle('active', type === 'daily');
        document.getElementById('weeklyViewBtn').classList.toggle('active', type === 'weekly');
        this.updateDateDisplay();
        this.loadData();
    }

    navigate(direction) {
        if (this.viewType === 'daily') {
            this.currentDate.setDate(this.currentDate.getDate() + direction);
        } else {
            this.currentDate.setDate(this.currentDate.getDate() + (direction * 7));
        }
        this.datePicker.setDate(this.currentDate);
        this.updateDateDisplay();
        this.loadData();
    }

    goToToday() {
        this.currentDate = new Date();
        this.datePicker.setDate(this.currentDate);
        this.updateDateDisplay();
        this.loadData();
    }

    updateDateDisplay() {
        const options = this.viewType === 'daily'
            ? { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
            : { year: 'numeric', month: 'long', day: 'numeric' };

        document.getElementById('timelineDatePicker').value = this.currentDate.toLocaleDateString('en-US', options);
    }

    async loadData() {
        const grid = document.getElementById('timelineGrid');
        grid.innerHTML = '<div class="timeline-loading"><div class="spinner"></div><span>Loading timeline...</span></div>';

        const projectId = document.getElementById('projectFilter').value;
        const dateStr = this.formatDate(this.currentDate);

        try {
            const response = await fetch(`{{ route('resource.timeline.data') }}?view_type=${this.viewType}&date=${dateStr}&project_id=${projectId}`);
            const data = await response.json();

            if (data.success) {
                this.data = data;
                this.renderGrid(data);
                this.updateSummary(data.summary);
            } else {
                grid.innerHTML = '<div class="timeline-loading"><span>Failed to load data</span></div>';
            }
        } catch (error) {
            console.error('Error loading timeline:', error);
            grid.innerHTML = '<div class="timeline-loading"><span>Error loading timeline</span></div>';
        }
    }

    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    renderGrid(data) {
        const grid = document.getElementById('timelineGrid');
        const slots = data.time_slots;
        const users = data.users;

        let html = '';

        // Header Row
        html += '<div class="timeline-row timeline-header-row">';
        html += '<div class="timeline-cell timeline-header-cell timeline-user-cell">Team Members</div>';

        slots.forEach(slot => {
            const isToday = slot.is_today ? 'today' : '';
            if (this.viewType === 'weekly') {
                html += `<div class="timeline-cell timeline-header-cell ${isToday}">
                    <span class="day-number">${slot.day}</span>
                    ${slot.label}
                </div>`;
            } else {
                html += `<div class="timeline-cell timeline-header-cell">${slot.label}</div>`;
            }
        });
        html += '</div>';

        // User Rows
        users.forEach(user => {
            const noTaskClass = user.has_tasks ? '' : 'no-tasks';
            html += `<div class="timeline-row ${noTaskClass}">`;

            // User Cell
            html += `<div class="timeline-cell timeline-user-cell">
                <div class="user-info">
                    <img src="${user.avatar}" alt="${user.name}" class="user-avatar" onerror="this.src='{{ asset('assets/images/user/avatar-1.jpg') }}'">
                    <div class="user-details">
                        <div class="user-name">${user.name}${!user.has_tasks ? '<span class="no-task-badge"><i class="ti ti-alert-circle"></i> No Tasks</span>' : ''}</div>
                        <div class="user-stats">${user.total_actual_hrs}h / ${user.total_estimated_hrs}h</div>
                    </div>
                </div>
            </div>`;

            // Tasks Cell (spans all time columns)
            html += `<div class="timeline-cell timeline-tasks-cell" colspan="${slots.length}" style="position: relative;">`;
            html += `<div class="tasks-row" style="width: ${slots.length * this.slotWidth}px;">`;

            if (!user.has_tasks) {
                html += `<div class="empty-row-message"><i class="ti ti-calendar-off"></i> No tasks scheduled</div>`;
            } else {
                user.tasks.forEach(task => {
                    const left = task.start_slot * this.slotWidth;
                    const width = Math.max(task.span * this.slotWidth - 4, 60);
                    const completedClass = task.is_complete ? 'completed' : '';

                    html += `<div class="task-block priority-${task.priority} ${completedClass}"
                        style="left: ${left}px; width: ${width}px;"
                        data-task='${JSON.stringify(task).replace(/'/g, "&#39;")}'
                        onclick="timeline.showTaskDetails(this)">
                        <div class="task-name">${task.name}</div>
                        <div class="task-meta">${task.project_name} | ${task.actual_hrs}h/${task.estimated_hrs}h</div>
                        <div class="task-progress">
                            <div class="task-progress-bar" style="width: ${task.progress}%"></div>
                        </div>
                    </div>`;
                });
            }

            html += '</div></div>';
            html += '</div>';
        });

        grid.innerHTML = html;
    }

    updateSummary(summary) {
        document.getElementById('totalUsers').textContent = summary.total_users;
        document.getElementById('assignedUsers').textContent = summary.users_with_tasks;
        document.getElementById('unassignedUsers').textContent = summary.users_without_tasks;
    }

    showTaskDetails(element) {
        const task = JSON.parse(element.dataset.task);
        const popup = document.getElementById('taskPopup');

        document.getElementById('popupTaskName').textContent = task.name;
        document.getElementById('popupProjectName').textContent = task.project_name;
        document.getElementById('popupEstimated').textContent = task.estimated_hrs + 'h';
        document.getElementById('popupActual').textContent = task.actual_hrs + 'h';
        document.getElementById('popupPriority').textContent = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
        document.getElementById('popupStage').textContent = task.stage;
        document.getElementById('popupProgressPercent').textContent = task.progress + '%';
        document.getElementById('popupProgressBar').style.width = task.progress + '%';
        document.getElementById('popupProgressBar').style.background = task.color;
        document.getElementById('popupViewTask').href = `/projects/${task.project_id}/task-board?task_id=${task.id}`;

        // Position popup
        const rect = element.getBoundingClientRect();
        popup.style.top = (rect.bottom + 10) + 'px';
        popup.style.left = Math.min(rect.left, window.innerWidth - 340) + 'px';
        popup.classList.add('show');
    }

    hidePopup() {
        document.getElementById('taskPopup').classList.remove('show');
    }
}

const timeline = new ResourceTimeline();
document.addEventListener('DOMContentLoaded', () => timeline.init());
</script>
@endpush
