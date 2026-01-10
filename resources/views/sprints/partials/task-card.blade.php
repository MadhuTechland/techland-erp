<div class="task-card priority-{{ $task->priority ?? 'medium' }}"
     data-task-id="{{ $task->id }}"
     data-story-points="{{ $task->story_points ?? 0 }}">
    <div class="task-card-header">
        <div class="task-card-title">
            @if($task->issueType)
                <span class="issue-type-icon" style="background: {{ $task->issueType->color ?? '#6366f1' }}">
                    <i class="{{ $task->issueType->icon ?? 'ti ti-checkbox' }}"></i>
                </span>
            @endif
            {{ Str::limit($task->name, 50) }}
        </div>
        <span class="task-card-key">{{ $task->issue_key }}</span>
    </div>
    <div class="task-card-meta">
        <div>
            @if(isset($showParent) && $showParent && $task->parent)
                <span class="text-primary" style="font-size: 9px;" title="{{ $task->parent->name }}">
                    <i class="ti ti-git-branch"></i> {{ $task->parent->issue_key }}
                </span>
            @endif
            @if($task->stage)
                <span class="badge bg-light text-dark" style="font-size: 9px; padding: 1px 4px;">{{ $task->stage->name }}</span>
            @endif
        </div>
        <span class="story-points-badge" data-points="{{ $task->story_points ?? 0 }}">
            {{ $task->story_points ?? 0 }} pts
        </span>
    </div>
</div>
