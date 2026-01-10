@extends('layouts.admin')

@section('page-title')
    {{ __('Review Backlog - BRD Parser') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('brd.index') }}">{{ __('BRD Parser') }}</a></li>
    <li class="breadcrumb-item">{{ __('Review') }}</li>
@endsection

@push('css-page')
<style>
    .wizard-container {
        background: var(--bs-card-bg, #fff);
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .wizard-header {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        padding: 30px;
        color: white;
    }

    .wizard-steps {
        display: flex;
        justify-content: center;
        padding: 20px;
        background: rgba(0, 0, 0, 0.02);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .wizard-step {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 24px;
        position: relative;
    }

    .wizard-step:not(:last-child)::after {
        content: '';
        position: absolute;
        right: -20px;
        width: 40px;
        height: 2px;
        background: #e5e7eb;
        top: 50%;
    }

    .step-number {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        color: #6b7280;
    }

    .wizard-step.active .step-number {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
    }

    .wizard-step.completed .step-number {
        background: #22c55e;
        color: white;
    }

    .wizard-body {
        padding: 40px;
    }

    /* Processing State */
    .processing-container {
        text-align: center;
        padding: 60px 20px;
    }

    .processing-animation {
        width: 120px;
        height: 120px;
        margin: 0 auto 30px;
        position: relative;
    }

    .processing-animation::before {
        content: '';
        position: absolute;
        inset: 0;
        border: 4px solid #e5e7eb;
        border-top-color: #6366f1;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .processing-animation::after {
        content: '\eb4b'; /* ti-brain icon */
        font-family: 'tabler-icons';
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: #6366f1;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .processing-text {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .processing-hint {
        color: #6b7280;
        font-size: 14px;
    }

    /* Error State */
    .error-container {
        text-align: center;
        padding: 40px 20px;
        background: #fef2f2;
        border-radius: 12px;
    }

    .error-icon {
        width: 60px;
        height: 60px;
        background: #fee2e2;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: #dc2626;
        font-size: 28px;
    }

    /* Backlog Tree */
    .backlog-summary {
        display: flex;
        gap: 20px;
        padding: 20px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
        border-radius: 12px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }

    .summary-stat {
        text-align: center;
        padding: 0 20px;
        border-right: 1px solid rgba(0, 0, 0, 0.1);
    }

    .summary-stat:last-child {
        border-right: none;
    }

    .summary-stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #6366f1;
    }

    .summary-stat-label {
        font-size: 12px;
        color: #6b7280;
        text-transform: uppercase;
    }

    .backlog-tree {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
    }

    .epic-item {
        border-bottom: 1px solid #e5e7eb;
    }

    .epic-item:last-child {
        border-bottom: none;
    }

    .epic-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        background: #f9fafb;
        cursor: pointer;
        transition: background 0.2s;
    }

    .epic-header:hover {
        background: #f3f4f6;
    }

    .epic-icon {
        width: 36px;
        height: 36px;
        background: #8b5cf6;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
    }

    .epic-info {
        flex: 1;
    }

    .epic-name {
        font-weight: 600;
        font-size: 15px;
        color: #1f2937;
    }

    .epic-meta {
        font-size: 12px;
        color: #6b7280;
    }

    .milestone-badge {
        padding: 4px 12px;
        background: #ddd6fe;
        color: #7c3aed;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .toggle-icon {
        transition: transform 0.2s;
    }

    .epic-item.expanded .toggle-icon {
        transform: rotate(90deg);
    }

    .epic-content {
        display: none;
        padding: 0 20px 20px 68px;
    }

    .epic-item.expanded .epic-content {
        display: block;
    }

    .story-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .story-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        cursor: pointer;
    }

    .story-header:hover {
        background: #fafafa;
    }

    .story-icon {
        width: 28px;
        height: 28px;
        background: #22c55e;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 14px;
    }

    .story-info {
        flex: 1;
    }

    .story-name {
        font-weight: 500;
        font-size: 14px;
    }

    .story-meta {
        font-size: 11px;
        color: #6b7280;
    }

    .priority-badge {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }

    .priority-badge.critical { background: #fee2e2; color: #dc2626; }
    .priority-badge.high { background: #ffedd5; color: #ea580c; }
    .priority-badge.medium { background: #fef3c7; color: #d97706; }
    .priority-badge.low { background: #dcfce7; color: #16a34a; }

    .hours-badge {
        padding: 3px 10px;
        background: #e0e7ff;
        color: #4f46e5;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .assignee-badge {
        padding: 3px 10px;
        background: #f3f4f6;
        color: #374151;
        border-radius: 12px;
        font-size: 11px;
    }

    .story-content {
        display: none;
        padding: 0 16px 16px;
        border-top: 1px solid #f3f4f6;
    }

    .story-item.expanded .story-content {
        display: block;
    }

    .story-description {
        font-size: 13px;
        color: #4b5563;
        margin-bottom: 12px;
        padding-top: 12px;
    }

    .task-list {
        background: #f9fafb;
        border-radius: 8px;
        padding: 12px;
    }

    .task-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .task-item:last-child {
        border-bottom: none;
    }

    .task-icon {
        width: 20px;
        height: 20px;
        background: #6366f1;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 10px;
    }

    .task-name {
        flex: 1;
        font-size: 13px;
    }

    .task-hours {
        font-size: 12px;
        color: #6b7280;
    }

    /* Generate Button */
    .generate-btn {
        width: 100%;
        padding: 16px;
        font-size: 16px;
        font-weight: 600;
    }

    .generate-btn.processing {
        pointer-events: none;
        opacity: 0.7;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <div class="wizard-container">
            <div class="wizard-header">
                <h1 class="h4 mb-1">Review & Generate Backlog</h1>
                <p class="mb-0 opacity-75">{{ $brd->project_name }}</p>
            </div>

            <div class="wizard-steps">
                <div class="wizard-step completed">
                    <div class="step-number"><i class="ti ti-check"></i></div>
                    <div class="step-label">Upload BRD</div>
                </div>
                <div class="wizard-step completed">
                    <div class="step-number"><i class="ti ti-check"></i></div>
                    <div class="step-label">Team Setup</div>
                </div>
                <div class="wizard-step completed">
                    <div class="step-number"><i class="ti ti-check"></i></div>
                    <div class="step-label">Milestones</div>
                </div>
                <div class="wizard-step active">
                    <div class="step-number">4</div>
                    <div class="step-label">Generate</div>
                </div>
            </div>

            <div class="wizard-body">
                <!-- Processing State -->
                <div id="processingState" style="display: none;">
                    <div class="processing-container">
                        <div class="processing-animation"></div>
                        <div class="processing-text">AI is analyzing your BRD...</div>
                        <div class="processing-hint">This may take 30-60 seconds depending on document size</div>
                    </div>
                </div>

                <!-- Error State -->
                <div id="errorState" style="display: none;">
                    <div class="error-container">
                        <div class="error-icon">
                            <i class="ti ti-alert-triangle"></i>
                        </div>
                        <h5>Generation Failed</h5>
                        <p class="text-muted mb-3" id="errorMessage">An error occurred while processing.</p>
                        <button type="button" class="btn btn-primary" onclick="generateBacklog()">
                            <i class="ti ti-refresh me-2"></i> Try Again
                        </button>
                    </div>
                </div>

                <!-- Initial State (no data yet) -->
                <div id="initialState" @if($brd->parsed_data) style="display: none;" @endif>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <img src="{{ asset('assets/images/ai-generate.svg') }}" alt="AI Generate" style="max-width: 200px;" onerror="this.style.display='none'">
                        </div>
                        <h5>Ready to Generate Product Backlog</h5>
                        <p class="text-muted mb-4">Click the button below to let AI analyze your BRD and create Epics, Stories, and Tasks</p>
                        <button type="button" class="btn btn-primary btn-lg generate-btn" onclick="generateBacklog()">
                            <i class="ti ti-sparkles me-2"></i> Generate Backlog with AI
                        </button>
                    </div>
                </div>

                <!-- Results State -->
                <div id="resultsState" @if(!$brd->parsed_data) style="display: none;" @endif>
                    <!-- Summary Stats -->
                    <div class="backlog-summary">
                        <div class="summary-stat">
                            <div class="summary-stat-value" id="statEpics">{{ $brd->getBacklogStats()['epics'] }}</div>
                            <div class="summary-stat-label">Epics</div>
                        </div>
                        <div class="summary-stat">
                            <div class="summary-stat-value" id="statStories">{{ $brd->getBacklogStats()['stories'] }}</div>
                            <div class="summary-stat-label">Stories</div>
                        </div>
                        <div class="summary-stat">
                            <div class="summary-stat-value" id="statTasks">{{ $brd->getBacklogStats()['tasks'] }}</div>
                            <div class="summary-stat-label">Tasks</div>
                        </div>
                        <div class="summary-stat">
                            <div class="summary-stat-value" id="statHours">{{ $brd->getBacklogStats()['total_hours'] }}</div>
                            <div class="summary-stat-label">Total Hours</div>
                        </div>
                        <div class="ms-auto">
                            <button type="button" class="btn btn-light" onclick="generateBacklog()">
                                <i class="ti ti-refresh me-1"></i> Regenerate
                            </button>
                        </div>
                    </div>

                    <!-- Backlog Tree -->
                    <div class="backlog-tree" id="backlogTree">
                        @if($brd->parsed_data)
                            @foreach($brd->parsed_data['epics'] ?? [] as $epicIndex => $epic)
                            <div class="epic-item">
                                <div class="epic-header" onclick="toggleEpic(this)">
                                    <i class="ti ti-chevron-right toggle-icon"></i>
                                    <div class="epic-icon"><i class="ti ti-layout-kanban"></i></div>
                                    <div class="epic-info">
                                        <div class="epic-name">{{ $epic['name'] }}</div>
                                        <div class="epic-meta">{{ count($epic['stories'] ?? []) }} stories</div>
                                    </div>
                                    @if(!empty($epic['milestone']))
                                    <span class="milestone-badge">{{ $epic['milestone'] }}</span>
                                    @endif
                                </div>
                                <div class="epic-content">
                                    <p class="text-muted small mb-3">{{ $epic['description'] ?? '' }}</p>
                                    @foreach($epic['stories'] ?? [] as $storyIndex => $story)
                                    <div class="story-item">
                                        <div class="story-header" onclick="toggleStory(this)">
                                            <i class="ti ti-chevron-right toggle-icon"></i>
                                            <div class="story-icon"><i class="ti ti-file-text"></i></div>
                                            <div class="story-info">
                                                <div class="story-name">{{ $story['name'] }}</div>
                                                <div class="story-meta">{{ count($story['tasks'] ?? []) }} tasks</div>
                                            </div>
                                            <span class="priority-badge {{ $story['priority'] ?? 'medium' }}">{{ ucfirst($story['priority'] ?? 'medium') }}</span>
                                            <span class="hours-badge">{{ $story['estimated_hrs'] ?? 0 }}h</span>
                                            @if(!empty($story['suggested_assignee']))
                                            <span class="assignee-badge"><i class="ti ti-user me-1"></i>{{ $story['suggested_assignee'] }}</span>
                                            @endif
                                        </div>
                                        <div class="story-content">
                                            <div class="story-description">{{ $story['description'] ?? '' }}</div>
                                            @if(!empty($story['acceptance_criteria']))
                                            <div class="mb-3">
                                                <strong class="small">Acceptance Criteria:</strong>
                                                <ul class="small mb-0">
                                                    @foreach($story['acceptance_criteria'] as $criterion)
                                                    <li>{{ $criterion }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @endif
                                            @if(!empty($story['tasks']))
                                            <div class="task-list">
                                                <strong class="small d-block mb-2">Tasks:</strong>
                                                @foreach($story['tasks'] as $task)
                                                <div class="task-item">
                                                    <div class="task-icon"><i class="ti ti-check"></i></div>
                                                    <div class="task-name">{{ $task['name'] }}</div>
                                                    <div class="task-hours">{{ $task['estimated_hrs'] ?? 0 }}h</div>
                                                </div>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('brd.milestones', $brd->id) }}" class="btn btn-light btn-lg">
                            <i class="ti ti-arrow-left me-2"></i> Back
                        </a>
                        <form action="{{ route('brd.confirm', $brd->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg" @if(!$brd->parsed_data) disabled @endif>
                                <i class="ti ti-check me-2"></i> Create Backlog in Project
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
const brdId = {{ $brd->id }};

function generateBacklog() {
    document.getElementById('initialState').style.display = 'none';
    document.getElementById('resultsState').style.display = 'none';
    document.getElementById('errorState').style.display = 'none';
    document.getElementById('processingState').style.display = 'block';

    fetch('{{ route("brd.generate", $brd->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('processingState').style.display = 'none';

        if (data.success) {
            // Update stats
            document.getElementById('statEpics').textContent = data.stats.epics;
            document.getElementById('statStories').textContent = data.stats.stories;
            document.getElementById('statTasks').textContent = data.stats.tasks;
            document.getElementById('statHours').textContent = data.stats.total_hours;

            // Render backlog tree
            renderBacklogTree(data.data);

            document.getElementById('resultsState').style.display = 'block';
        } else {
            document.getElementById('errorMessage').textContent = data.message;
            document.getElementById('errorState').style.display = 'block';
        }
    })
    .catch(error => {
        document.getElementById('processingState').style.display = 'none';
        document.getElementById('errorMessage').textContent = error.message || 'An unexpected error occurred';
        document.getElementById('errorState').style.display = 'block';
    });
}

function renderBacklogTree(data) {
    const tree = document.getElementById('backlogTree');
    let html = '';

    (data.epics || []).forEach((epic, epicIndex) => {
        html += `
        <div class="epic-item">
            <div class="epic-header" onclick="toggleEpic(this)">
                <i class="ti ti-chevron-right toggle-icon"></i>
                <div class="epic-icon"><i class="ti ti-layout-kanban"></i></div>
                <div class="epic-info">
                    <div class="epic-name">${epic.name}</div>
                    <div class="epic-meta">${(epic.stories || []).length} stories</div>
                </div>
                ${epic.milestone ? `<span class="milestone-badge">${epic.milestone}</span>` : ''}
            </div>
            <div class="epic-content">
                <p class="text-muted small mb-3">${epic.description || ''}</p>
                ${(epic.stories || []).map((story, storyIndex) => `
                <div class="story-item">
                    <div class="story-header" onclick="toggleStory(this)">
                        <i class="ti ti-chevron-right toggle-icon"></i>
                        <div class="story-icon"><i class="ti ti-file-text"></i></div>
                        <div class="story-info">
                            <div class="story-name">${story.name}</div>
                            <div class="story-meta">${(story.tasks || []).length} tasks</div>
                        </div>
                        <span class="priority-badge ${story.priority || 'medium'}">${(story.priority || 'medium').charAt(0).toUpperCase() + (story.priority || 'medium').slice(1)}</span>
                        <span class="hours-badge">${story.estimated_hrs || 0}h</span>
                        ${story.suggested_assignee ? `<span class="assignee-badge"><i class="ti ti-user me-1"></i>${story.suggested_assignee}</span>` : ''}
                    </div>
                    <div class="story-content">
                        <div class="story-description">${story.description || ''}</div>
                        ${story.acceptance_criteria && story.acceptance_criteria.length > 0 ? `
                        <div class="mb-3">
                            <strong class="small">Acceptance Criteria:</strong>
                            <ul class="small mb-0">
                                ${story.acceptance_criteria.map(c => `<li>${c}</li>`).join('')}
                            </ul>
                        </div>
                        ` : ''}
                        ${story.tasks && story.tasks.length > 0 ? `
                        <div class="task-list">
                            <strong class="small d-block mb-2">Tasks:</strong>
                            ${story.tasks.map(task => `
                            <div class="task-item">
                                <div class="task-icon"><i class="ti ti-check"></i></div>
                                <div class="task-name">${task.name}</div>
                                <div class="task-hours">${task.estimated_hrs || 0}h</div>
                            </div>
                            `).join('')}
                        </div>
                        ` : ''}
                    </div>
                </div>
                `).join('')}
            </div>
        </div>
        `;
    });

    tree.innerHTML = html;

    // Enable confirm button
    document.querySelector('button[type="submit"]').disabled = false;
}

function toggleEpic(header) {
    header.closest('.epic-item').classList.toggle('expanded');
}

function toggleStory(header) {
    header.closest('.story-item').classList.toggle('expanded');
}

// Auto-generate if status is milestones_setup
@if($brd->status === 'milestones_setup')
document.addEventListener('DOMContentLoaded', function() {
    generateBacklog();
});
@endif
</script>
@endpush
