<style>
    /* ===== JIRA-STYLE TASK POPUP ===== */
    .task-popup-modal {
        max-height: 85vh;
        overflow-y: auto;
    }

    .task-popup-modal::-webkit-scrollbar {
        width: 8px;
    }

    .task-popup-modal::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .task-popup-modal::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }

    .task-popup-modal::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }

    /* Header */
    .task-header {
        padding: 16px 20px;
        border-bottom: 1px solid #dfe1e6;
        background: linear-gradient(to right, #f8f9fa, #fff);
    }

    .task-header-badges {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .task-issue-key {
        font-size: 14px;
        font-weight: 600;
        color: #0052cc;
        text-decoration: none;
        background: #deebff;
        padding: 4px 10px;
        border-radius: 4px;
    }

    .task-issue-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .task-title {
        font-size: 20px;
        font-weight: 600;
        color: #172b4d;
        margin: 12px 0 8px;
        line-height: 1.4;
    }

    .task-parent-info {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
    }

    .parent-link {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: #f4f5f7;
        border-radius: 4px;
        font-size: 12px;
        color: #5e6c84;
        text-decoration: none;
        transition: all 0.2s;
    }

    .parent-link:hover {
        background: #ebecf0;
        color: #172b4d;
    }

    /* Main Layout */
    .task-main-layout {
        display: flex;
        gap: 0;
    }

    .task-content {
        flex: 1;
        min-width: 0;
        padding: 20px;
        border-right: 1px solid #dfe1e6;
    }

    .task-sidebar {
        width: 300px;
        flex-shrink: 0;
        padding: 20px;
        background: #f4f5f7;
    }

    /* Section styling */
    .task-section {
        margin-bottom: 24px;
    }

    .task-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #0052cc;
    }

    .task-section-title {
        font-size: 14px;
        font-weight: 700;
        color: #172b4d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .task-section-title i {
        color: #5e6c84;
        font-size: 16px;
    }

    .task-section-count {
        background: #dfe1e6;
        color: #5e6c84;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 10px;
    }

    /* Description */
    .task-description {
        background: #fff;
        border: 1px solid #dfe1e6;
        border-radius: 8px;
        padding: 16px;
        min-height: 100px;
    }

    .task-description-content {
        font-size: 15px;
        line-height: 1.8;
        color: #172b4d;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .task-description-empty {
        color: #5e6c84;
        font-style: italic;
        font-size: 14px;
    }

    /* Sidebar Details */
    .detail-item {
        margin-bottom: 16px;
    }

    .detail-label {
        font-size: 11px;
        font-weight: 700;
        color: #5e6c84;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .detail-value {
        font-size: 14px;
        color: #172b4d;
        font-weight: 500;
    }

    .detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
    }

    .detail-badge.hours {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #fff;
    }

    .detail-badge.milestone {
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        color: #fff;
    }

    .detail-badge.priority-low {
        background: #d1fae5;
        color: #065f46;
    }

    .detail-badge.priority-medium {
        background: #fef3c7;
        color: #92400e;
    }

    .detail-badge.priority-high {
        background: #fee2e2;
        color: #991b1b;
    }

    .detail-badge.priority-urgent {
        background: #dc2626;
        color: #fff;
    }

    /* Progress */
    .task-progress-wrapper {
        margin-top: 8px;
    }

    .task-progress-bar {
        height: 8px;
        background: #dfe1e6;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 8px;
    }

    .task-progress-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .task-progress-fill.low { background: linear-gradient(90deg, #ef4444, #f87171); }
    .task-progress-fill.medium { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .task-progress-fill.high { background: linear-gradient(90deg, #10b981, #34d399); }

    .task-progress-text {
        font-size: 13px;
        font-weight: 600;
        color: #172b4d;
    }

    /* Assignees */
    .assignee-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .assignee-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        background: #fff;
        border: 1px solid #dfe1e6;
        border-radius: 20px;
    }

    .assignee-avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
    }

    .assignee-name {
        font-size: 12px;
        font-weight: 500;
        color: #172b4d;
    }

    /* Subtasks */
    .subtask-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .subtask-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 12px;
        background: #fff;
        border: 1px solid #dfe1e6;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .subtask-item:hover {
        border-color: #0052cc;
        box-shadow: 0 2px 4px rgba(0,82,204,0.1);
    }

    .subtask-left {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 0;
    }

    .subtask-type-icon {
        width: 20px;
        height: 20px;
        border-radius: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: #fff;
        flex-shrink: 0;
    }

    .subtask-key {
        font-size: 12px;
        color: #5e6c84;
        font-weight: 500;
        flex-shrink: 0;
    }

    .subtask-name {
        font-size: 13px;
        color: #172b4d;
        text-decoration: none;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .subtask-name:hover {
        color: #0052cc;
        text-decoration: underline;
    }

    .subtask-right {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .subtask-status {
        font-size: 10px;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 3px;
        text-transform: uppercase;
    }

    .subtask-complete {
        color: #10b981;
        font-size: 16px;
    }

    .subtask-empty {
        text-align: center;
        padding: 30px 20px;
        color: #5e6c84;
    }

    .subtask-empty i {
        font-size: 32px;
        margin-bottom: 8px;
        opacity: 0.5;
    }

    /* Checklist */
    .checklist-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: #fff;
        border: 1px solid #dfe1e6;
        border-radius: 6px;
        margin-bottom: 8px;
    }

    .checklist-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .checklist-item label {
        flex: 1;
        font-size: 13px;
        color: #172b4d;
        cursor: pointer;
        margin: 0;
    }

    .checklist-item .delete-btn {
        opacity: 0;
        transition: opacity 0.2s;
    }

    .checklist-item:hover .delete-btn {
        opacity: 1;
    }

    /* Attachments */
    .attachment-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #fff;
        border: 1px solid #dfe1e6;
        border-radius: 6px;
        margin-bottom: 8px;
    }

    .attachment-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #6366f1, #4f46e5);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 18px;
    }

    .attachment-info {
        flex: 1;
        min-width: 0;
    }

    .attachment-name {
        font-size: 13px;
        font-weight: 500;
        color: #172b4d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .attachment-size {
        font-size: 11px;
        color: #5e6c84;
    }

    .attachment-actions {
        display: flex;
        gap: 8px;
    }

    /* Activity & Comments */
    .activity-item {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f4f5f7;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .activity-content {
        flex: 1;
        min-width: 0;
    }

    .activity-header {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }

    .activity-user {
        font-size: 13px;
        font-weight: 600;
        color: #172b4d;
    }

    .activity-time {
        font-size: 11px;
        color: #5e6c84;
    }

    .activity-text {
        font-size: 13px;
        color: #172b4d;
        line-height: 1.5;
    }

    .comment-text {
        font-size: 14px;
        color: #172b4d;
        line-height: 1.6;
        background: #f4f5f7;
        padding: 10px 12px;
        border-radius: 6px;
        margin-top: 6px;
    }

    /* Comment form */
    .comment-form {
        display: flex;
        gap: 12px;
        padding: 16px;
        background: #f4f5f7;
        border-radius: 8px;
        margin-top: 16px;
    }

    .comment-form .avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }

    .comment-form .form-control {
        flex: 1;
        border: 1px solid #dfe1e6;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 14px;
        resize: none;
        transition: all 0.2s;
    }

    .comment-form .form-control:focus {
        border-color: #0052cc;
        box-shadow: 0 0 0 2px rgba(0,82,204,0.2);
    }

    .comment-form .btn-send {
        background: linear-gradient(135deg, #0052cc, #0747a6);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 10px 16px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .comment-form .btn-send:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,82,204,0.3);
    }

    /* Buttons */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 500;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-action.primary {
        background: #0052cc;
        color: #fff;
    }

    .btn-action.primary:hover {
        background: #0747a6;
    }

    .btn-action.secondary {
        background: #f4f5f7;
        color: #5e6c84;
    }

    .btn-action.secondary:hover {
        background: #ebecf0;
        color: #172b4d;
    }

    .btn-action.danger {
        background: #de350b;
        color: #fff;
    }

    .btn-action.danger:hover {
        background: #bf2600;
    }

    /* Collapsible form */
    .add-form {
        background: #fff;
        border: 1px solid #dfe1e6;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 12px;
    }

    .add-form .form-control {
        border: 1px solid #dfe1e6;
        border-radius: 4px;
        font-size: 13px;
        padding: 8px 12px;
    }

    .add-form .form-control:focus {
        border-color: #0052cc;
        box-shadow: 0 0 0 2px rgba(0,82,204,0.1);
    }

    /* Dates */
    .date-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        background: #fff;
        border: 1px solid #dfe1e6;
        border-radius: 6px;
        margin-bottom: 8px;
    }

    .date-item i {
        color: #5e6c84;
        font-size: 16px;
    }

    .date-item .label {
        font-size: 11px;
        color: #5e6c84;
        text-transform: uppercase;
    }

    .date-item .value {
        font-size: 13px;
        font-weight: 500;
        color: #172b4d;
        margin-left: auto;
    }

    .date-item.overdue {
        border-color: #de350b;
        background: #ffebe6;
    }

    .date-item.overdue .value {
        color: #de350b;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .task-main-layout {
            flex-direction: column;
        }

        .task-sidebar {
            width: 100%;
            border-right: none;
            border-top: 1px solid #dfe1e6;
        }

        .task-content {
            border-right: none;
        }
    }
</style>

<div class="modal-body task-id task-popup-modal" id="{{$task->id}}">
    <!-- Header -->
    <div class="task-header">
        <div class="task-header-badges">
            @if($task->issue_key)
                <span class="task-issue-key">{{ $task->issue_key }}</span>
            @endif
            @if($task->issueType)
                <span class="task-issue-type-badge" style="background: {{ $task->issueType->color ?? '#5e6c84' }};">
                    <i class="{{ $task->issueType->icon ?? 'ti ti-subtask' }}"></i>
                    {{ $task->issueType->name }}
                </span>
            @endif
        </div>
        <h2 class="task-title">{{ $task->name }}</h2>
        @if($task->parent)
            <div class="task-parent-info">
                <i class="ti ti-arrow-up text-muted"></i>
                <a href="#" class="parent-link" data-url="{{ route('projects.tasks.show', [$task->project_id, $task->parent->id]) }}" data-ajax-popup="true" data-size="lg">
                    @if($task->parent->issueType)
                        <span style="width: 16px; height: 16px; border-radius: 3px; background: {{ $task->parent->issueType->color ?? '#5e6c84' }}; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="{{ $task->parent->issueType->icon ?? 'ti ti-subtask' }}" style="font-size: 10px; color: #fff;"></i>
                        </span>
                    @endif
                    <span>{{ $task->parent->issue_key }}</span>
                    <span style="color: #172b4d;">{{ Str::limit($task->parent->name, 30) }}</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Main Layout -->
    <div class="task-main-layout">
        <!-- Left Content -->
        <div class="task-content">
            <!-- Description -->
            <div class="task-section">
                <div class="task-section-header">
                    <span class="task-section-title">
                        <i class="ti ti-align-left"></i> {{__('Description')}}
                    </span>
                </div>
                <div class="task-description">
                    @if(!empty($task->description))
                        <div class="task-description-content">{!! nl2br(e($task->description)) !!}</div>
                    @else
                        <div class="task-description-empty">{{__('No description provided. Click Edit to add a description.')}}</div>
                    @endif
                </div>
            </div>

            <!-- Sub-tasks -->
            <div class="task-section">
                <div class="task-section-header">
                    <span class="task-section-title">
                        <i class="ti ti-subtask"></i> {{__('Sub-tasks')}}
                        @if($task->children && $task->children->count() > 0)
                            <span class="task-section-count">{{ $task->children->count() }}</span>
                        @endif
                    </span>
                    <a href="#" data-size="lg" data-url="{{ route('projects.tasks.create', $task->project_id) }}?parent={{ $task->id }}&type=subtask" data-ajax-popup="true" class="btn-action primary">
                        <i class="ti ti-plus"></i> {{__('Add')}}
                    </a>
                </div>
                @if($task->children && $task->children->count() > 0)
                    <div class="subtask-list">
                        @foreach($task->children as $subtask)
                            <div class="subtask-item">
                                <div class="subtask-left">
                                    @if($subtask->issueType)
                                        <span class="subtask-type-icon" style="background: {{ $subtask->issueType->color ?? '#5e6c84' }};">
                                            <i class="{{ $subtask->issueType->icon ?? 'ti ti-subtask' }}"></i>
                                        </span>
                                    @endif
                                    <span class="subtask-key">{{ $subtask->issue_key }}</span>
                                    <a href="#" class="subtask-name" data-url="{{ route('projects.tasks.show', [$task->project_id, $subtask->id]) }}" data-ajax-popup="true" data-size="lg">
                                        {{ $subtask->name }}
                                    </a>
                                </div>
                                <div class="subtask-right">
                                    @if($subtask->stage)
                                        <span class="subtask-status" style="background: {{ $subtask->stage->color }}; color: #fff;">
                                            {{ $subtask->stage->name }}
                                        </span>
                                    @endif
                                    @if($subtask->is_complete)
                                        <i class="ti ti-check subtask-complete"></i>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="subtask-empty">
                        <i class="ti ti-subtask"></i>
                        <p>{{__('No sub-tasks yet')}}</p>
                    </div>
                @endif
            </div>

            <!-- Checklist -->
            <div class="task-section">
                <div class="task-section-header">
                    <span class="task-section-title">
                        <i class="ti ti-list-check"></i> {{__('Checklist')}}
                        @if($task->checklist->count() > 0)
                            <span class="task-section-count">{{ $task->checklist->where('status', 1)->count() }}/{{ $task->checklist->count() }}</span>
                        @endif
                    </span>
                    <a data-bs-toggle="collapse" href="#form-checklist" class="btn-action secondary">
                        <i class="ti ti-plus"></i> {{__('Add')}}
                    </a>
                </div>
                <div id="checklist">
                    <form id="form-checklist" class="collapse add-form" data-action="{{route('checklist.store',[$task->project_id,$task->id])}}">
                        <div class="d-flex gap-2">
                            @csrf
                            <input type="text" name="name" required class="form-control" placeholder="{{__('Add checklist item...')}}"/>
                            <button class="btn-action primary" type="button" id="checklist_submit">
                                <i class="ti ti-check"></i>
                            </button>
                        </div>
                    </form>
                    @foreach($task->checklist as $checklist)
                        <div class="checklist-item checklist-member">
                            <input type="checkbox" id="check-item-{{ $checklist->id }}" @if($checklist->status) checked @endif data-url="{{route('checklist.update',[$task->project_id,$checklist->id])}}">
                            <label for="check-item-{{ $checklist->id }}" style="{{ $checklist->status ? 'text-decoration: line-through; color: #5e6c84;' : '' }}">{{ $checklist->name }}</label>
                            <a href="#" class="btn-action danger delete-btn delete-checklist" data-url="{{ route('checklist.destroy',[$task->project_id,$checklist->id]) }}">
                                <i class="ti ti-trash"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Attachments -->
            <div class="task-section">
                <div class="task-section-header">
                    <span class="task-section-title">
                        <i class="ti ti-paperclip"></i> {{__('Attachments')}}
                        @if(count($task->taskFiles) > 0)
                            <span class="task-section-count">{{ count($task->taskFiles) }}</span>
                        @endif
                    </span>
                    <a data-bs-toggle="collapse" href="#add_file" class="btn-action secondary">
                        <i class="ti ti-plus"></i> {{__('Add')}}
                    </a>
                </div>
                <div id="attachments">
                    <form id="add_file" class="collapse add-form">
                        <div class="d-flex gap-2">
                            @csrf
                            <input type="file" name="task_attachment" id="task_attachment" required class="form-control"/>
                            <button class="btn-action primary" type="button" id="file_attachment_submit" data-action="{{ route('comment.store.file',[$task->project_id,$task->id]) }}">
                                <i class="ti ti-upload"></i>
                            </button>
                        </div>
                    </form>
                    <div id="comments-file">
                        @foreach($task->taskFiles as $file)
                            <div class="attachment-item task-file">
                                <div class="attachment-icon">
                                    <i class="ti ti-file"></i>
                                </div>
                                <div class="attachment-info">
                                    <div class="attachment-name">{{ $file->name }}</div>
                                    <div class="attachment-size">{{ $file->file_size }}</div>
                                </div>
                                <div class="attachment-actions">
                                    <a href="{{asset(Storage::url('uploads/tasks/'.$file->file))}}" download class="btn-action secondary">
                                        <i class="ti ti-download"></i>
                                    </a>
                                    @auth('web')
                                        <a href="#" class="btn-action danger delete-comment-file" data-url="{{ route('comment.destroy.file',[$task->project_id,$task->id,$file->id]) }}">
                                            <i class="ti ti-trash"></i>
                                        </a>
                                    @endauth
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Activity -->
            <div class="task-section">
                <div class="task-section-header">
                    <span class="task-section-title">
                        <i class="ti ti-activity"></i> {{__('Activity')}}
                    </span>
                </div>
                <div id="activity">
                    @foreach($task->activity_log() as $activity)
                        @php $user = \App\Models\User::find($activity->user_id); @endphp
                        <div class="activity-item">
                            <img class="activity-avatar" @if($user->avatar) src="{{asset('/storage/uploads/avatar/'.$user->avatar)}}" @else src="{{asset('/storage/uploads/avatar/avatar.png')}}" @endif alt="{{ $user->name }}">
                            <div class="activity-content">
                                <div class="activity-header">
                                    <span class="activity-user">{{ $user->name }}</span>
                                    <span class="activity-time">{{ $activity->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="activity-text">{!! $activity->getRemark() !!}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Comments -->
            <div class="task-section">
                <div class="task-section-header">
                    <span class="task-section-title">
                        <i class="ti ti-messages"></i> {{__('Comments')}}
                        @if(count($task->comments) > 0)
                            <span class="task-section-count">{{ count($task->comments) }}</span>
                        @endif
                    </span>
                    @php $settings = \App\Models\Utility::settings(); @endphp
                    @if($settings['ai_chatgpt_enable'] == 'on')
                        <a href="#" data-size="md" class="btn-action secondary" data-ajax-popup-over="true" id="grammarCheck" data-url="{{ route('grammar',['grammar']) }}" data-bs-placement="top" data-title="{{ __('Grammar check with AI') }}">
                            <i class="ti ti-sparkles"></i> {{__('AI Grammar')}}
                        </a>
                    @endif
                </div>
                <div id="comments">
                    @foreach($task->comments as $comment)
                        @php $user = \App\Models\User::find($comment->user_id); @endphp
                        <div class="activity-item">
                            <img class="activity-avatar" @if($user->avatar) src="{{asset('/storage/uploads/avatar/'.$user->avatar)}}" @else src="{{asset('/storage/uploads/avatar/avatar.png')}}" @endif alt="{{ $user->name }}">
                            <div class="activity-content">
                                <div class="activity-header">
                                    <span class="activity-user">{{ $user->name }}</span>
                                    <span class="activity-time">{{ $comment->created_at->diffForHumans() }}</span>
                                    <a href="#" class="btn-action danger delete-comment" style="margin-left: auto;" data-url="{{ route('comment.destroy',[$task->project_id,$task->id,$comment->id]) }}">
                                        <i class="ti ti-trash"></i>
                                    </a>
                                </div>
                                <div class="comment-text">{{ $comment->comment }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="comment-form">
                    <img class="avatar" @if(\Auth::user()->avatar) src="{{asset('/storage/uploads/avatar/'.\Auth::user()->avatar)}}" @else src="{{asset('/storage/uploads/avatar/avatar.png')}}" @endif alt="{{ Auth::user()->name }}">
                    <form method="post" id="form-comment" data-action="{{route('task.comment.store',[$task->project_id,$task->id])}}" style="flex: 1; display: flex; gap: 10px;">
                        <textarea rows="1" class="form-control grammer_textarea" name="comment" placeholder="{{__('Write a comment...')}}"></textarea>
                        <button type="button" id="comment_submit" class="btn-send">
                            <i class="ti ti-send"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="task-sidebar">
            <!-- Status -->
            @if($task->stage)
                <div class="detail-item">
                    <div class="detail-label">{{__('Status')}}</div>
                    <span class="detail-badge" style="background: {{ $task->stage->color ?? '#5e6c84' }}; color: #fff;">
                        {{ $task->stage->name }}
                    </span>
                </div>
            @endif

            <!-- Priority -->
            <div class="detail-item">
                <div class="detail-label">{{__('Priority')}}</div>
                @php
                    $priorityClass = 'priority-medium';
                    $priorityText = 'Medium';
                    if(isset(\App\Models\ProjectTask::$priority[$task->priority])) {
                        $priorityText = \App\Models\ProjectTask::$priority[$task->priority];
                        $priorityClass = 'priority-' . strtolower($priorityText);
                    }
                @endphp
                <span class="detail-badge {{ $priorityClass }}">
                    {{ __($priorityText) }}
                </span>
            </div>

            <!-- Estimated Hours -->
            <div class="detail-item">
                <div class="detail-label">{{__('Estimated Hours')}}</div>
                <span class="detail-badge hours">
                    <i class="ti ti-clock"></i>
                    {{ (!empty($task->estimated_hrs)) ? number_format($task->estimated_hrs) . 'h' : '-' }}
                </span>
            </div>

            <!-- Milestone -->
            @if($task->milestone)
                <div class="detail-item">
                    <div class="detail-label">{{__('Milestone')}}</div>
                    <span class="detail-badge milestone">
                        <i class="ti ti-flag"></i>
                        {{ $task->milestone->title }}
                    </span>
                </div>
            @endif

            <!-- Progress -->
            @if($allow_progress == 'false')
                <div class="detail-item">
                    <div class="detail-label">{{__('Progress')}}</div>
                    <div class="task-progress-wrapper">
                        @php
                            $progress = $task->progress;
                            $progressClass = $progress < 30 ? 'low' : ($progress < 70 ? 'medium' : 'high');
                        @endphp
                        <div class="task-progress-bar">
                            <div class="task-progress-fill {{ $progressClass }}" style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="task-progress-text"><span id="t_percentage">{{ $progress }}</span>%</span>
                            <input type="range" class="task_progress" value="{{ $progress }}" id="task_progress" name="progress" style="width: 120px;" data-url="{{ route('change.progress',[$task->project_id,$task->id]) }}">
                        </div>
                    </div>
                </div>
            @endif

            <!-- Dates -->
            <div class="detail-item">
                <div class="detail-label">{{__('Dates')}}</div>
                @if($task->start_date && $task->start_date != '0000-00-00')
                    <div class="date-item">
                        <i class="ti ti-calendar-event"></i>
                        <span class="label">{{__('Start')}}</span>
                        <span class="value">{{ \Carbon\Carbon::parse($task->start_date)->format('M d, Y') }}</span>
                    </div>
                @endif
                @if($task->end_date && $task->end_date != '0000-00-00')
                    @php
                        $isOverdue = \Carbon\Carbon::parse($task->end_date)->isPast();
                    @endphp
                    <div class="date-item {{ $isOverdue ? 'overdue' : '' }}">
                        <i class="ti ti-calendar-due"></i>
                        <span class="label">{{__('Due')}}</span>
                        <span class="value">{{ \Carbon\Carbon::parse($task->end_date)->format('M d, Y') }}</span>
                    </div>
                @endif
            </div>

            <!-- Assignees -->
            <div class="detail-item">
                <div class="detail-label">{{__('Assignees')}}</div>
                <div class="assignee-list">
                    @php $users = $task->users(); @endphp
                    @forelse($users as $user)
                        <div class="assignee-item">
                            <img class="assignee-avatar" @if($user->avatar) src="{{asset('/storage/uploads/avatar/'.$user->avatar)}}" @else src="{{asset('/storage/uploads/avatar/avatar.png')}}" @endif alt="{{ $user->name }}">
                            <span class="assignee-name">{{ $user->name }}</span>
                        </div>
                    @empty
                        <span style="color: #5e6c84; font-size: 13px;">{{__('Unassigned')}}</span>
                    @endforelse
                </div>
            </div>

            <!-- Project -->
            @if($task->project)
                <div class="detail-item">
                    <div class="detail-label">{{__('Project')}}</div>
                    <div class="detail-value">
                        <i class="ti ti-folder text-primary"></i>
                        {{ $task->project->project_name }}
                    </div>
                </div>
            @endif

            <!-- Created -->
            <div class="detail-item">
                <div class="detail-label">{{__('Created')}}</div>
                <div class="detail-value" style="font-size: 12px; color: #5e6c84;">
                    {{ $task->created_at->format('M d, Y \a\t h:i A') }}
                </div>
            </div>

            <!-- Updated -->
            <div class="detail-item">
                <div class="detail-label">{{__('Updated')}}</div>
                <div class="detail-value" style="font-size: 12px; color: #5e6c84;">
                    {{ $task->updated_at->diffForHumans() }}
                </div>
            </div>
        </div>
    </div>
</div>

@push('script-page')
    <script>
        $(document).ready(function () {
            $(".colorPickSelector").colorPick({
                'onColorSelected': function () {
                    var task_id = this.element.parents('.side-modal').attr('id');
                    var color = this.color;

                    if (task_id) {
                        this.element.css({'backgroundColor': color});
                        $.ajax({
                            url: '{{ route('update.task.priority.color') }}',
                            method: 'PATCH',
                            data: {
                                'task_id': task_id,
                                'color': color,
                            },
                            success: function (data) {
                                $('.task-list-items').find('#' + task_id).attr('style', 'border-left:2px solid ' + color + ' !important');
                            }
                        });
                    }
                }
            });
        });
    </script>
@endpush
