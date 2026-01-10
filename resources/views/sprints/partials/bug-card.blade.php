<div class="bug-card severity-{{ $bug->severity ?? 'minor' }}"
     data-bug-id="{{ $bug->id }}"
     data-story-points="{{ $bug->story_points ?? 0 }}">
    <div class="bug-card-header">
        <div class="bug-card-title">
            <span class="bug-icon">
                <i class="ti ti-bug"></i>
            </span>
            {{ Str::limit($bug->title, 45) }}
        </div>
        <span class="bug-card-key">{{ $bug->bug_id }}</span>
    </div>
    <div class="bug-card-meta">
        <div>
            @if($bug->bug_status)
                <span class="badge bg-light text-dark" style="font-size: 9px; padding: 1px 4px;">{{ $bug->bug_status->title }}</span>
            @endif
            <span class="severity-badge {{ $bug->severity ?? 'minor' }}">{{ ucfirst($bug->severity ?? 'minor') }}</span>
        </div>
        <span class="story-points-badge" data-points="{{ $bug->story_points ?? 0 }}" data-bug-id="{{ $bug->id }}">
            {{ $bug->story_points ?? 0 }} pts
        </span>
    </div>
</div>
