<div class="board-card priority-{{ $task->priority ?? 'medium' }}" data-task-id="{{ $task->id }}">
    <span class="board-card-key">{{ $task->issue_key }}</span>
    <div class="board-card-title">{{ Str::limit($task->name, 80) }}</div>
    <div class="board-card-meta">
        <div class="board-card-type">
            @if($task->issueType)
                <span class="icon" style="background: {{ $task->issueType->color ?? '#6366f1' }}">
                    <i class="{{ $task->issueType->icon ?? 'ti ti-checkbox' }}"></i>
                </span>
                <span class="text-muted" style="font-size: 11px;">{{ $task->issueType->name }}</span>
            @endif
        </div>
        @if($task->story_points)
            <span class="board-card-points">{{ $task->story_points }} pts</span>
        @endif
    </div>
</div>
