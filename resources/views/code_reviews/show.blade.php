@extends('layouts.admin')

@section('page-title')
    {{ __('Code Review Details') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('code-reviews.index') }}">{{ __('Code Reviews') }}</a></li>
    <li class="breadcrumb-item">{{ substr($review->commit_sha, 0, 7) }}</li>
@endsection

@push('css-page')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
<style>
    .review-header-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 25px;
        color: #fff;
        margin-bottom: 25px;
    }
    .review-header-card.critical { background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); }
    .review-header-card.warning { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
    .review-header-card.clean { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }

    .section-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    .section-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-body { padding: 20px; }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 15px;
        border-radius: 20px;
        background: rgba(255,255,255,0.2);
        font-size: 0.9rem;
        margin-right: 10px;
    }

    /* Quick Actions Panel - Redesigned */
    .actions-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .actions-table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 0.85rem;
        text-align: left;
    }
    .actions-table thead th:first-child {
        border-radius: 10px 0 0 0;
        width: 50px;
    }
    .actions-table thead th:nth-child(2) {
        width: auto;
    }
    .actions-table thead th:last-child {
        border-radius: 0 10px 0 0;
        text-align: center;
    }
    .actions-table tbody tr {
        transition: background 0.2s;
    }
    .actions-table tbody tr:hover {
        background: #f8f9fa;
    }
    .actions-table tbody tr:last-child td:first-child {
        border-radius: 0 0 0 10px;
    }
    .actions-table tbody tr:last-child td:last-child {
        border-radius: 0 0 10px 0;
    }
    .actions-table td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }
    .actions-table tbody tr:last-child td {
        border-bottom: none;
    }

    .issue-type-badge {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .issue-type-badge.critical { background: #fff0f0; color: #ff4b2b; }
    .issue-type-badge.warning { background: #fff8e6; color: #f7971e; }
    .issue-type-badge.suggestion { background: #e6fff0; color: #11998e; }

    .issue-text {
        font-size: 0.9rem;
        color: #333;
        line-height: 1.5;
    }
    .issue-number {
        font-size: 0.75rem;
        color: #999;
        margin-bottom: 3px;
    }

    .action-buttons-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        min-width: 200px;
    }
    .action-btn {
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        border: none;
        background: #f0f0f0;
        color: #666;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .action-btn.implemented { background: #e6fff0; color: #11998e; }
    .action-btn.implemented:hover, .action-btn.implemented.active { background: #38ef7d; color: #fff; }
    .action-btn.rejected { background: #fff0f0; color: #ff4b2b; }
    .action-btn.rejected:hover, .action-btn.rejected.active { background: #ff4b2b; color: #fff; }
    .action-btn.will_fix_later { background: #fff8e6; color: #c77d00; }
    .action-btn.will_fix_later:hover, .action-btn.will_fix_later.active { background: #f7971e; color: #fff; }
    .action-btn.not_applicable { background: #f0f0f0; color: #666; }
    .action-btn.not_applicable:hover, .action-btn.not_applicable.active { background: #999; color: #fff; }

    .action-btn.active {
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    /* Responsive for mobile */
    @media (max-width: 768px) {
        .action-buttons-grid {
            grid-template-columns: 1fr;
            min-width: 120px;
        }
        .actions-table td {
            padding: 10px;
        }
    }

    /* Markdown Content */
    .markdown-content {
        font-size: 0.95rem;
        line-height: 1.8;
        color: #333;
    }
    .markdown-content h2 {
        color: #667eea;
        font-size: 1.4rem;
        margin-top: 30px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #667eea;
    }
    .markdown-content h2:first-child { margin-top: 0; }
    .markdown-content h3 {
        font-size: 1.15rem;
        margin-top: 25px;
        margin-bottom: 12px;
        color: #444;
    }
    .markdown-content p { margin-bottom: 12px; }
    .markdown-content ul, .markdown-content ol {
        padding-left: 25px;
        margin-bottom: 15px;
    }
    .markdown-content li { margin-bottom: 8px; }
    .markdown-content pre {
        background: #1e1e1e;
        border-radius: 8px;
        padding: 15px;
        overflow-x: auto;
        margin: 15px 0;
    }
    .markdown-content code {
        font-family: 'Fira Code', monospace;
        font-size: 0.85em;
    }
    .markdown-content :not(pre) > code {
        background: #f0f0f0;
        padding: 2px 6px;
        border-radius: 4px;
        color: #e83e8c;
    }
    .markdown-content strong { color: #222; }
    .markdown-content blockquote {
        border-left: 4px solid #667eea;
        padding-left: 15px;
        margin: 15px 0;
        color: #666;
    }
</style>
@endpush

@section('content')
@php
    $headerClass = 'clean';
    if ($review->critical_count > 0) $headerClass = 'critical';
    elseif ($review->warning_count > 0) $headerClass = 'warning';
@endphp

<!-- Header Card -->
<div class="review-header-card {{ $headerClass }}">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h4 class="mb-2">{{ $review->repo_name }} / {{ $review->branch }}</h4>
            <p class="mb-2" style="opacity: 0.9;">
                <code style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px;">{{ substr($review->commit_sha, 0, 7) }}</code>
                <span class="ms-2">{{ $review->commit_message }}</span>
            </p>
            <small style="opacity: 0.8;">
                <i class="ti ti-user"></i> {{ $review->author_username }}
                @if($review->user)
                    ({{ $review->user->name }})
                @endif
                <span class="mx-2">|</span>
                <i class="ti ti-calendar"></i> {{ $review->created_at->format('M d, Y H:i') }}
            </small>
        </div>
        <div class="col-md-4 text-end">
            <div class="stat-pill">
                <i class="ti ti-file"></i> {{ $review->files_changed }} files
            </div>
            <div class="stat-pill">
                <span class="text-success">+{{ $review->lines_added }}</span>
                <span class="text-danger">-{{ $review->lines_deleted }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Stats Row -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="section-card">
            <div class="section-body text-center py-3">
                <h2 class="text-danger mb-1">{{ $review->critical_count }}</h2>
                <p class="text-muted mb-0">{{ __('Critical') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="section-card">
            <div class="section-body text-center py-3">
                <h2 class="text-warning mb-1">{{ $review->warning_count }}</h2>
                <p class="text-muted mb-0">{{ __('Warnings') }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="section-card">
            <div class="section-body text-center py-3">
                <h2 class="text-success mb-1">{{ $review->info_count }}</h2>
                <p class="text-muted mb-0">{{ __('Suggestions') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions Panel -->
@if($review->issues_count > 0)
<div class="section-card">
    <div class="section-header">
        <i class="ti ti-checkbox text-primary"></i> {{ __('Developer Actions') }}
        <span class="badge bg-primary ms-auto">{{ $review->issues_count }} issues to review</span>
    </div>
    <div class="section-body p-0">
        <table class="actions-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('Issue') }}</th>
                    <th>{{ __('Your Response') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $issueIndex = 0; @endphp
                @foreach($review->issues_found ?? [] as $issue)
                    @php
                        $type = $issue['type'] ?? 'suggestion';
                        $action = $review->getIssueAction($issueIndex);
                    @endphp
                    <tr data-index="{{ $issueIndex }}">
                        <td>
                            <div class="issue-type-badge {{ $type }}">
                                @if($type == 'critical')
                                    <i class="ti ti-alert-circle"></i>
                                @elseif($type == 'warning')
                                    <i class="ti ti-alert-triangle"></i>
                                @else
                                    <i class="ti ti-bulb"></i>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="issue-number">#{{ $issueIndex + 1 }} - {{ ucfirst($type) }}</div>
                            <div class="issue-text">{{ $issue['message'] ?? 'Issue description not available' }}</div>
                        </td>
                        <td>
                            <div class="action-buttons-grid">
                                <button type="button"
                                        class="action-btn implemented {{ $action && $action->action === 'implemented' ? 'active' : '' }}"
                                        onclick="takeAction({{ $review->id }}, {{ $issueIndex }}, 'implemented', this)">
                                    <i class="ti ti-check"></i> Fixed
                                </button>
                                <button type="button"
                                        class="action-btn will_fix_later {{ $action && $action->action === 'will_fix_later' ? 'active' : '' }}"
                                        onclick="takeAction({{ $review->id }}, {{ $issueIndex }}, 'will_fix_later', this)">
                                    <i class="ti ti-clock"></i> Later
                                </button>
                                <button type="button"
                                        class="action-btn rejected {{ $action && $action->action === 'rejected' ? 'active' : '' }}"
                                        onclick="takeAction({{ $review->id }}, {{ $issueIndex }}, 'rejected', this)">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                                <button type="button"
                                        class="action-btn not_applicable {{ $action && $action->action === 'not_applicable' ? 'active' : '' }}"
                                        onclick="takeAction({{ $review->id }}, {{ $issueIndex }}, 'not_applicable', this)">
                                    <i class="ti ti-minus"></i> N/A
                                </button>
                            </div>
                        </td>
                    </tr>
                    @php $issueIndex++; @endphp
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Full AI Review -->
<div class="section-card">
    <div class="section-header">
        <i class="ti ti-robot text-primary"></i> {{ __('Full AI Review') }}
    </div>
    <div class="section-body">
        <div class="markdown-content" id="review-content">
            {!! $formattedReview !!}
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script>
// Highlight code blocks
document.querySelectorAll('pre code').forEach((block) => {
    hljs.highlightElement(block);
});

function takeAction(reviewId, issueIndex, action, button) {
    const row = button.closest('tr');
    const buttonGrid = button.closest('.action-buttons-grid');

    // Disable all buttons in this row
    buttonGrid.querySelectorAll('.action-btn').forEach(btn => btn.disabled = true);

    fetch(`{{ url('code-reviews') }}/${reviewId}/action/${issueIndex}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ action: action })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            buttonGrid.querySelectorAll('.action-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.disabled = false;
            });
            button.classList.add('active');

            // Add a subtle highlight to show success
            row.style.background = '#f0fff4';
            setTimeout(() => { row.style.background = ''; }, 1000);

            if (typeof toastrs === 'function') {
                toastrs('Success', 'Response saved!', 'success');
            }
        } else {
            if (typeof toastrs === 'function') {
                toastrs('Error', data.error || 'Failed to save', 'error');
            }
            buttonGrid.querySelectorAll('.action-btn').forEach(btn => btn.disabled = false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        buttonGrid.querySelectorAll('.action-btn').forEach(btn => btn.disabled = false);
    });
}
</script>
@endpush
