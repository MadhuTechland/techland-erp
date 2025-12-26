@extends('layouts.admin')

@section('page-title')
    {{ __('Developer Activity') }}: {{ $erpUser?->name ?? $username }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('productivity.admin-dashboard') }}">{{ __('Developer Activity') }}</a></li>
    <li class="breadcrumb-item">{{ $erpUser?->name ?? $username }}</li>
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

        .daily-chart {
            height: 300px;
        }

        .filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .idle-day {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 4px 10px;
            border-radius: 8px;
            margin: 4px;
            font-size: 0.8rem;
        }

        .repo-stat {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .repo-stat .repo-name {
            font-weight: 600;
            color: #333;
        }

        .developer-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 20px;
            color: #fff;
            margin-bottom: 20px;
        }

        .developer-info .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" action="{{ route('productivity.view-developer', $username) }}">
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

    <!-- Developer Info -->
    <div class="developer-info">
        <div class="d-flex align-items-center">
            <div class="avatar me-3">
                <i class="ti ti-user"></i>
            </div>
            <div>
                <h4 class="mb-0">{{ $erpUser?->name ?? $username }}</h4>
                <small>
                    <i class="ti ti-brand-github"></i> @{{ $username }}
                    @if($erpUser)
                        | <i class="ti ti-mail"></i> {{ $erpUser->email }}
                    @endif
                </small>
            </div>
        </div>
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
                <h3>{{ $totalCommits }}</h3>
                <p>{{ __('Total Commits') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card orange">
                <i class="ti ti-calendar-check"></i>
                <h3>{{ $activeDays }}</h3>
                <p>{{ __('Active Days') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card blue">
                <i class="ti ti-trending-up"></i>
                <h3>{{ $averageScore }}</h3>
                <p>{{ __('Avg Daily Score') }}</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Daily Activity Chart -->
        <div class="col-md-8">
            <div class="content-card">
                <h5><i class="ti ti-chart-line"></i> {{ __('Daily Activity') }}</h5>
                <canvas id="dailyActivityChart" class="daily-chart"></canvas>
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
                            <small class="text-muted">{{ $commit->committed_at->format('M d, H:i') }}</small>
                        </div>
                        <div class="commit-message">{{ Str::limit($commit->commit_message, 150) }}</div>
                        <div class="commit-meta">
                            <span class="me-3"><i class="ti ti-file"></i> {{ $commit->files_changed }} {{ __('files') }}</span>
                            <span class="text-success me-2">+{{ $commit->lines_added }}</span>
                            <span class="text-danger">-{{ $commit->lines_deleted }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4">{{ __('No commits found') }}</p>
                @endforelse
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Repository Breakdown -->
            <div class="content-card">
                <h5><i class="ti ti-folders"></i> {{ __('Repositories') }}</h5>
                @forelse($repoBreakdown as $repo)
                    <div class="repo-stat">
                        <div class="repo-name">
                            <i class="ti ti-git-branch"></i> {{ $repo->repo_name }}
                        </div>
                        <small class="text-muted">
                            {{ $repo->commits }} {{ __('commits') }} |
                            {{ $repo->files_changed }} {{ __('files') }} |
                            <span class="text-success">+{{ $repo->lines_added }}</span>
                            <span class="text-danger">-{{ $repo->lines_deleted }}</span>
                        </small>
                    </div>
                @empty
                    <p class="text-muted text-center py-4">{{ __('No repositories') }}</p>
                @endforelse
            </div>

            <!-- Idle Days -->
            <div class="content-card">
                <h5><i class="ti ti-calendar-off"></i> {{ __('Idle Days (Weekdays)') }}</h5>
                @if($idleDays->count() > 0)
                    <div>
                        @foreach($idleDays->take(10) as $day)
                            <span class="idle-day">{{ \Carbon\Carbon::parse($day)->format('M d, D') }}</span>
                        @endforeach
                        @if($idleDays->count() > 10)
                            <span class="idle-day">+{{ $idleDays->count() - 10 }} {{ __('more') }}</span>
                        @endif
                    </div>
                @else
                    <p class="text-success text-center py-2">
                        <i class="ti ti-check"></i> {{ __('No idle weekdays!') }}
                    </p>
                @endif
            </div>

            <!-- Lines Summary -->
            <div class="content-card">
                <h5><i class="ti ti-code"></i> {{ __('Lines Changed') }}</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-success"><i class="ti ti-plus"></i> {{ __('Added') }}</span>
                    <strong class="text-success">{{ number_format($dailyScores->sum('lines_added')) }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-danger"><i class="ti ti-minus"></i> {{ __('Deleted') }}</span>
                    <strong class="text-danger">{{ number_format($dailyScores->sum('lines_deleted')) }}</strong>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            var ctx = document.getElementById('dailyActivityChart').getContext('2d');

            var dailyData = @json($dailyScores);
            var labels = dailyData.map(d => d.date);
            var scores = dailyData.map(d => d.score);
            var commits = dailyData.map(d => d.commits);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: '{{ __("Score") }}',
                            data: scores,
                            borderColor: 'rgba(102, 126, 234, 1)',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: true,
                            tension: 0.4,
                        },
                        {
                            label: '{{ __("Commits") }}',
                            data: commits,
                            borderColor: 'rgba(17, 153, 142, 1)',
                            backgroundColor: 'rgba(17, 153, 142, 0.1)',
                            fill: false,
                            tension: 0.4,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: '{{ __("Score") }}'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: '{{ __("Commits") }}'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        },
                    }
                }
            });
        });
    </script>
@endpush
