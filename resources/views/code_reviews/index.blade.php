@extends('layouts.admin')

@section('page-title')
    {{ __('AI Code Reviews') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Code Reviews') }}</li>
@endsection

@push('css-page')
<style>
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 20px;
        color: #fff;
        margin-bottom: 20px;
        box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }
    .stat-card.critical { background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); }
    .stat-card.warning { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
    .stat-card.clean { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .stat-card h3 { font-size: 2rem; font-weight: 700; margin-bottom: 0; }
    .stat-card p { opacity: 0.9; margin: 0; font-size: 0.85rem; }
    .stat-card i { font-size: 2.5rem; opacity: 0.3; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }

    .review-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 15px;
        border-left: 4px solid #667eea;
        transition: transform 0.2s;
    }
    .review-card:hover { transform: translateY(-2px); }
    .review-card.critical { border-left-color: #ff4b2b; }
    .review-card.warning { border-left-color: #ffd200; }
    .review-card.clean { border-left-color: #38ef7d; }

    .review-header { padding: 15px 20px; border-bottom: 1px solid #eee; }
    .review-body { padding: 15px 20px; }

    .severity-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .severity-badge.critical { background: #ff4b2b; color: #fff; }
    .severity-badge.warning { background: #ffd200; color: #333; }
    .severity-badge.clean { background: #38ef7d; color: #fff; }

    .issue-count { display: inline-flex; align-items: center; gap: 5px; margin-right: 10px; font-size: 0.85rem; }
    .issue-count .critical { color: #ff4b2b; }
    .issue-count .warning { color: #f7971e; }
    .issue-count .info { color: #38ef7d; }

    .filter-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); }
</style>
@endpush

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3">
        <div class="stat-card">
            <i class="ti ti-code"></i>
            <h3>{{ $stats['total'] }}</h3>
            <p>{{ __('Total Reviews') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card critical">
            <i class="ti ti-alert-circle"></i>
            <h3>{{ $stats['with_critical'] }}</h3>
            <p>{{ __('With Critical Issues') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <i class="ti ti-alert-triangle"></i>
            <h3>{{ $stats['with_warnings'] }}</h3>
            <p>{{ __('With Warnings') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card clean">
            <i class="ti ti-circle-check"></i>
            <h3>{{ $stats['clean'] }}</h3>
            <p>{{ __('Clean Commits') }}</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <form method="GET" action="{{ route('code-reviews.index') }}">
        <div class="row align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('Repository') }}</label>
                <select name="repo" class="form-control">
                    <option value="">{{ __('All Repos') }}</option>
                    @foreach($repos as $repo)
                        <option value="{{ $repo }}" {{ request('repo') == $repo ? 'selected' : '' }}>{{ $repo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('Author') }}</label>
                <select name="author" class="form-control">
                    <option value="">{{ __('All Authors') }}</option>
                    @foreach($authors as $author)
                        <option value="{{ $author }}" {{ request('author') == $author ? 'selected' : '' }}>{{ $author }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('Severity') }}</label>
                <select name="severity" class="form-control">
                    <option value="">{{ __('All') }}</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>{{ __('Critical') }}</option>
                    <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>{{ __('Warnings') }}</option>
                    <option value="clean" {{ request('severity') == 'clean' ? 'selected' : '' }}>{{ __('Clean') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('Start Date') }}</label>
                <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('End Date') }}</label>
                <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-filter"></i> {{ __('Filter') }}
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Reviews List -->
<div class="row">
    <div class="col-12">
        @forelse($reviews as $review)
            @php
                $cardClass = 'clean';
                if ($review->critical_count > 0) $cardClass = 'critical';
                elseif ($review->warning_count > 0) $cardClass = 'warning';
            @endphp
            <div class="review-card {{ $cardClass }}">
                <div class="review-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $review->repo_name }}</strong>
                        <span class="text-muted mx-2">/</span>
                        <span class="text-muted">{{ $review->branch }}</span>
                        <span class="severity-badge {{ $cardClass }} ms-2">
                            @if($review->critical_count > 0)
                                {{ __('Critical') }}
                            @elseif($review->warning_count > 0)
                                {{ __('Warning') }}
                            @else
                                {{ __('Clean') }}
                            @endif
                        </span>
                    </div>
                    <div class="text-muted">
                        {{ $review->created_at->format('M d, Y H:i') }}
                    </div>
                </div>
                <div class="review-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="mb-2">
                                <code>{{ substr($review->commit_sha, 0, 7) }}</code>
                                <span class="ms-2">{{ Str::limit($review->commit_message, 80) }}</span>
                            </p>
                            <small class="text-muted">
                                <i class="ti ti-user"></i> {{ $review->author_username }}
                                @if($review->user)
                                    ({{ $review->user->name }})
                                @endif
                                <span class="mx-2">|</span>
                                <i class="ti ti-file"></i> {{ $review->files_changed }} files
                                <span class="text-success">+{{ $review->lines_added }}</span>
                                <span class="text-danger">-{{ $review->lines_deleted }}</span>
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="issue-count">
                                @if($review->critical_count > 0)
                                    <span class="critical"><i class="ti ti-alert-circle"></i> {{ $review->critical_count }}</span>
                                @endif
                                @if($review->warning_count > 0)
                                    <span class="warning"><i class="ti ti-alert-triangle"></i> {{ $review->warning_count }}</span>
                                @endif
                                @if($review->info_count > 0)
                                    <span class="info"><i class="ti ti-bulb"></i> {{ $review->info_count }}</span>
                                @endif
                            </div>
                            <a href="{{ route('code-reviews.show', $review->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-eye"></i> {{ __('View Details') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <i class="ti ti-code-off" style="font-size: 4rem; color: #ccc;"></i>
                <p class="text-muted mt-3">{{ __('No code reviews found.') }}</p>
            </div>
        @endforelse

        <div class="mt-4">
            {{ $reviews->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
