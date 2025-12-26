@extends('layouts.admin')

@section('page-title')
    {{ __('Developer Activity Dashboard') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Developer Activity') }}</li>
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
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .stat-card p {
            opacity: 0.9;
            margin: 0;
            font-size: 0.9rem;
        }

        .stat-card i {
            font-size: 3rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
        }

        .developer-table {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .developer-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 16px;
            font-weight: 600;
        }

        .developer-table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }

        .developer-table tbody tr:hover {
            background: #f8f9fa;
        }

        .score-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }

        .activity-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .activity-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .activity-badge.idle {
            background: #fff3cd;
            color: #856404;
        }

        .filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .chart-container {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
    </style>
@endpush

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('productivity.mappings') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Manage GitHub Mappings') }}">
            <i class="ti ti-link"></i> {{ __('Mappings') }}
        </a>
    </div>
@endsection

@section('content')
    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" action="{{ route('productivity.admin-dashboard') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Start Date') }}</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('End Date') }}</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Repository') }}</label>
                    <select name="repo" class="form-select">
                        <option value="">{{ __('All Repositories') }}</option>
                        @foreach($repositories as $repo)
                            <option value="{{ $repo }}" {{ $selectedRepo == $repo ? 'selected' : '' }}>{{ $repo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-filter"></i> {{ __('Filter') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Stats Overview -->
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card position-relative">
                <i class="ti ti-git-commit"></i>
                <h3>{{ number_format($overallStats['total_commits']) }}</h3>
                <p>{{ __('Total Commits') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card green position-relative">
                <i class="ti ti-users"></i>
                <h3>{{ $overallStats['total_developers'] }}</h3>
                <p>{{ __('Active Developers') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card orange position-relative">
                <i class="ti ti-folders"></i>
                <h3>{{ $overallStats['total_repos'] }}</h3>
                <p>{{ __('Repositories') }}</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card blue position-relative">
                <i class="ti ti-code-plus"></i>
                <h3>{{ number_format($overallStats['total_lines_added']) }}</h3>
                <p>{{ __('Lines Added') }}</p>
            </div>
        </div>
    </div>

    <!-- Activity Chart -->
    <div class="chart-container">
        <h5 class="mb-3">{{ __('Daily Commit Activity') }}</h5>
        <canvas id="activityChart" height="80"></canvas>
    </div>

    <!-- Developers Table -->
    <div class="developer-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>{{ __('Developer') }}</th>
                    <th>{{ __('Score') }}</th>
                    <th>{{ __('Commits') }}</th>
                    <th>{{ __('Files Changed') }}</th>
                    <th>{{ __('Lines +/-') }}</th>
                    <th>{{ __('Active Days') }}</th>
                    <th>{{ __('Idle Days') }}</th>
                    <th>{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($developersSummary as $dev)
                    <tr>
                        <td>
                            <strong>{{ $dev['erp_user_name'] }}</strong>
                            <br><small class="text-muted">@{{ $dev['github_username'] }}</small>
                        </td>
                        <td>
                            <span class="score-badge">{{ $dev['total_score'] }}</span>
                        </td>
                        <td>{{ $dev['total_commits'] }}</td>
                        <td>{{ $dev['total_files_changed'] }}</td>
                        <td>
                            <span class="text-success">+{{ number_format($dev['total_lines_added']) }}</span>
                            <span class="text-danger">-{{ number_format($dev['total_lines_deleted']) }}</span>
                        </td>
                        <td>
                            <span class="activity-badge active">{{ $dev['active_days'] }} {{ __('days') }}</span>
                        </td>
                        <td>
                            @if($dev['idle_days'] > 0)
                                <span class="activity-badge idle">{{ $dev['idle_days'] }} {{ __('days') }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('productivity.view-developer', $dev['github_username']) }}?start_date={{ $startDate }}&end_date={{ $endDate }}"
                               class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="{{ __('View Details') }}">
                                <i class="ti ti-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="ti ti-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="text-muted mt-2">{{ __('No activity data found for the selected period.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('script-page')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            var ctx = document.getElementById('activityChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode($dailyActivity['labels']) !!},
                    datasets: [{
                        label: '{{ __("Commits") }}',
                        data: {!! json_encode($dailyActivity['values']) !!},
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
