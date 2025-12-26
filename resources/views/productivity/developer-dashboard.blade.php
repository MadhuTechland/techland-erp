@extends('layouts.admin')

@section('page-title')
    {{ __('My Activity Dashboard') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('My Activity') }}</li>
@endsection

@push('css-page')
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 24px;
            color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .stat-card.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 0 10px 40px rgba(17, 153, 142, 0.3);
        }

        .stat-card.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 10px 40px rgba(245, 87, 108, 0.3);
        }

        .stat-card.blue {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 10px 40px rgba(79, 172, 254, 0.3);
        }

        .stat-card h3 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .stat-card p {
            opacity: 0.9;
            margin: 0;
            font-size: 0.85rem;
        }

        .stat-card i {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .content-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 24px;
            margin-bottom: 20px;
        }

        .content-card h5 {
            margin-bottom: 20px;
            font-weight: 600;
            color: #333;
        }

        .timeline-item {
            padding: 16px;
            border-left: 3px solid #667eea;
            margin-left: 10px;
            margin-bottom: 16px;
            background: #f8f9fa;
            border-radius: 0 12px 12px 0;
        }

        .timeline-item .commit-sha {
            font-family: monospace;
            font-size: 0.8rem;
            color: #667eea;
        }

        .timeline-item .commit-message {
            font-weight: 500;
            margin: 8px 0;
            word-break: break-word;
        }

        .timeline-item .commit-meta {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .repo-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 8px 16px;
            border-radius: 12px;
            margin-bottom: 12px;
            display: inline-block;
        }

        .daily-score-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
        }

        .daily-score-item:last-child {
            border-bottom: none;
        }

        .daily-score-item .date {
            font-weight: 500;
        }

        .daily-score-item .score {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .daily-score-item.idle .score {
            background: #e9ecef;
            color: #6c757d;
        }

        .filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .no-mapping-alert {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
        }

        .no-mapping-alert i {
            font-size: 4rem;
            color: #856404;
            margin-bottom: 20px;
        }
    </style>
@endpush

@section('content')
    @if(isset($noMapping) && $noMapping)
        <!-- No GitHub Mapping -->
        <div class="no-mapping-alert">
            <i class="ti ti-unlink"></i>
            <h4>{{ __('GitHub Account Not Linked') }}</h4>
            <p class="text-muted mb-0">
                {{ __('Your account is not linked to a GitHub username. Please contact your administrator to set up the mapping.') }}
            </p>
        </div>
    @else
        <!-- Filters -->
        <div class="filter-card">
            <form method="GET" action="{{ route('productivity.my-activity') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Start Date') }}</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('End Date') }}</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-filter"></i> {{ __('Filter') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- GitHub Username -->
        <div class="mb-3">
            <span class="badge bg-secondary">
                <i class="ti ti-brand-github"></i> {{ $githubUsername }}
            </span>
        </div>

        <!-- Stats Overview -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="ti ti-chart-bar"></i>
                    <h3>{{ $totalScore }}</h3>
                    <p>{{ __('Total Score') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card green">
                    <i class="ti ti-git-commit"></i>
                    <h3>{{ $dailyScores->sum('commits') }}</h3>
                    <p>{{ __('Total Commits') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <i class="ti ti-file-code"></i>
                    <h3>{{ $dailyScores->sum('files_changed') }}</h3>
                    <p>{{ __('Files Changed') }}</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card blue">
                    <i class="ti ti-code-plus"></i>
                    <h3>{{ number_format($dailyScores->sum('lines_added')) }}</h3>
                    <p>{{ __('Lines Added') }}</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Daily Activity -->
            <div class="col-md-8">
                <div class="content-card">
                    <h5><i class="ti ti-calendar"></i> {{ __('Daily Activity') }}</h5>
                    @forelse($dailyScores->reverse()->take(14) as $day)
                        <div class="daily-score-item {{ $day['commits'] == 0 ? 'idle' : '' }}">
                            <div>
                                <span class="date">{{ \Carbon\Carbon::parse($day['date'])->format('D, M d') }}</span>
                                @if($day['commits'] > 0)
                                    <br>
                                    <small class="text-muted">
                                        {{ $day['commits'] }} {{ __('commits') }},
                                        {{ $day['files_changed'] }} {{ __('files') }},
                                        <span class="text-success">+{{ $day['lines_added'] }}</span>
                                        <span class="text-danger">-{{ $day['lines_deleted'] }}</span>
                                    </small>
                                @else
                                    <br><small class="text-muted">{{ __('No commits') }}</small>
                                @endif
                            </div>
                            <span class="score">{{ $day['score'] }}</span>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">{{ __('No activity data') }}</p>
                    @endforelse
                </div>
            </div>

            <!-- Repository Breakdown -->
            <div class="col-md-4">
                <div class="content-card">
                    <h5><i class="ti ti-folders"></i> {{ __('Repositories') }}</h5>
                    @forelse($repoBreakdown as $repo)
                        <div class="mb-3">
                            <div class="repo-badge">
                                <i class="ti ti-git-branch"></i> {{ $repo->repo_name }}
                            </div>
                            <div class="ps-2">
                                <small>
                                    {{ $repo->commits }} {{ __('commits') }} |
                                    {{ $repo->files_changed }} {{ __('files') }} |
                                    <span class="text-success">+{{ $repo->lines_added }}</span>
                                </small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4">{{ __('No repositories') }}</p>
                    @endforelse
                </div>

                <!-- Summary Stats -->
                <div class="content-card">
                    <h5><i class="ti ti-chart-pie"></i> {{ __('This Week') }}</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Score') }}</span>
                        <strong>{{ $weeklySummary['total_score'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Commits') }}</span>
                        <strong>{{ $weeklySummary['total_commits'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Active Days') }}</span>
                        <strong class="text-success">{{ $weeklySummary['active_days'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>{{ __('Idle Days') }}</span>
                        <strong class="text-warning">{{ $weeklySummary['idle_days'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Commits -->
        <div class="content-card">
            <h5><i class="ti ti-history"></i> {{ __('Recent Commits') }}</h5>
            @forelse($recentCommits as $commit)
                <div class="timeline-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="commit-sha">{{ substr($commit->commit_sha, 0, 7) }}</span>
                            <span class="ms-2 badge bg-light text-dark">{{ $commit->repo_name }}</span>
                        </div>
                        <small class="text-muted">{{ $commit->committed_at->diffForHumans() }}</small>
                    </div>
                    <div class="commit-message">{{ Str::limit($commit->commit_message, 100) }}</div>
                    <div class="commit-meta">
                        <span class="me-3"><i class="ti ti-file"></i> {{ $commit->files_changed }} {{ __('files') }}</span>
                        <span class="text-success me-2">+{{ $commit->lines_added }}</span>
                        <span class="text-danger">-{{ $commit->lines_deleted }}</span>
                    </div>
                </div>
            @empty
                <p class="text-muted text-center py-4">{{ __('No recent commits') }}</p>
            @endforelse
        </div>
    @endif
@endsection
