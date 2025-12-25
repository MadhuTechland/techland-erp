{{-- Container Item (Epic/Story) --}}
@php
    $totalHrs = $item->getChildrenTotalHrs();
    $progress = $item->getWorkItemProgress();
    $containerClass = strtolower($item->issueType->key ?? 'epic');
@endphp
<div class="container-item">
    <div class="container-header">
        <div class="container-info">
            <div class="container-icon {{ $containerClass }}">
                <i class="{{ $item->issueType->icon ?? 'ti ti-bolt' }}"></i>
            </div>
            <div>
                <span class="container-title">
                    <a href="#" data-url="{{ route('projects.tasks.show', [$project->id, $item->id]) }}"
                       data-ajax-popup="true" data-size="lg">
                        {{ $item->name }}
                    </a>
                </span>
                @if($item->issue_key)
                    <span class="container-key">{{ $item->issue_key }}</span>
                @endif
            </div>
        </div>
        <div class="container-meta">
            <span class="container-hours">{{ $totalHrs }}h</span>
            <div class="container-progress">
                <div class="progress-bar-mini">
                    <div class="fill" style="width: {{ $progress['percentage'] }}%"></div>
                </div>
                <span class="progress-text">{{ $progress['completed'] }}/{{ $progress['total'] }}</span>
            </div>
            <i class="ti ti-chevron-down toggle-icon"></i>
        </div>
    </div>
    <div class="container-children">
        @if($item->children && $item->children->count() > 0)
            @foreach($item->children as $child)
                @if($child->isContainer())
                    {{-- Nested container (e.g., Story inside Epic) --}}
                    @include('project_task.partials.container_item', ['item' => $child, 'project' => $project])
                @else
                    {{-- Work item --}}
                    @include('project_task.partials.work_item', ['item' => $child, 'project' => $project])
                @endif
            @endforeach
        @else
            <div class="text-center py-3 text-muted">
                <small>{{ __('No child items') }}</small>
            </div>
        @endif
    </div>
</div>
