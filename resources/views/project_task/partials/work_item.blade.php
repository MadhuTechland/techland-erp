{{-- Work Item (Task/Bug/Subtask) --}}
@php
    $typeClass = 'task';
    if($item->issueType) {
        $key = strtolower($item->issueType->key ?? 'task');
        if($key === 'bug') $typeClass = 'bug';
        elseif($key === 'subtask') $typeClass = 'subtask';
    }
@endphp
<div class="work-item">
    <div class="work-item-info">
        <div class="work-item-type {{ $typeClass }}">
            <i class="{{ $item->issueType->icon ?? 'ti ti-checkbox' }}"></i>
        </div>
        <div>
            <span class="work-item-title">
                <a href="#" data-url="{{ route('projects.tasks.show', [$project->id, $item->id]) }}"
                   data-ajax-popup="true" data-size="lg">
                    {{ $item->name }}
                </a>
            </span>
            @if($item->issue_key)
                <span class="work-item-key">{{ $item->issue_key }}</span>
            @endif
        </div>
    </div>
    <div class="work-item-meta">
        <span class="priority-badge {{ $item->priority }}">{{ ucfirst($item->priority) }}</span>
        <span class="work-item-hours">{{ floatval($item->estimated_hrs ?? 0) }}h</span>
        @if($item->is_complete)
            <span class="work-item-status complete">{{ __('Done') }}</span>
        @else
            <span class="work-item-status pending">{{ $item->stage->name ?? __('To Do') }}</span>
        @endif
    </div>
</div>
