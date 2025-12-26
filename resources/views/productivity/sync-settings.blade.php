@extends('layouts.admin')

@section('page-title')
    {{ __('GitHub Sync Settings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('productivity.admin-dashboard') }}">{{ __('Developer Activity') }}</a></li>
    <li class="breadcrumb-item">{{ __('Sync Settings') }}</li>
@endsection

@push('css-page')
    <style>
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

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            text-align: center;
            margin-bottom: 15px;
        }

        .stat-box.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-box.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stat-box h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .stat-box p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.success {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.danger {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.warning {
            background: #fff3cd;
            color: #856404;
        }

        .repo-item {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .repo-item .repo-name {
            font-weight: 500;
        }

        .config-code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        .config-code .key {
            color: #68d391;
        }

        .config-code .value {
            color: #fbd38d;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <!-- Stats Overview -->
        <div class="col-md-4">
            <div class="stat-box">
                <h3>{{ number_format($totalCommits) }}</h3>
                <p>{{ __('Total Commits Synced') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box green">
                <h3>{{ $uniqueRepos }}</h3>
                <p>{{ __('Repositories Tracked') }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-box orange">
                <h3>{{ $lastCommit ? $lastCommit->created_at->diffForHumans() : __('Never') }}</h3>
                <p>{{ __('Last Sync') }}</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Connection Status -->
        <div class="col-md-6">
            <div class="content-card">
                <h5><i class="ti ti-plug"></i> {{ __('GitHub API Connection') }}</h5>

                @if($isConfigured)
                    @if($connectionTest && $connectionTest['success'])
                        <div class="d-flex align-items-center mb-3">
                            <span class="status-badge success me-3">
                                <i class="ti ti-check"></i> {{ __('Connected') }}
                            </span>
                            <span>{{ $connectionTest['message'] }}</span>
                        </div>

                        @if($rateLimit)
                            <div class="mt-3">
                                <small class="text-muted">
                                    {{ __('API Rate Limit') }}:
                                    {{ $rateLimit['rate']['remaining'] ?? 0 }} / {{ $rateLimit['rate']['limit'] ?? 5000 }}
                                    {{ __('requests remaining') }}
                                </small>
                            </div>
                        @endif
                    @else
                        <div class="d-flex align-items-center">
                            <span class="status-badge danger me-3">
                                <i class="ti ti-x"></i> {{ __('Connection Failed') }}
                            </span>
                            <span>{{ $connectionTest['message'] ?? __('Unable to connect') }}</span>
                        </div>
                    @endif
                @else
                    <div class="d-flex align-items-center">
                        <span class="status-badge warning me-3">
                            <i class="ti ti-alert-triangle"></i> {{ __('Not Configured') }}
                        </span>
                    </div>
                    <p class="text-muted mt-3">
                        {{ __('Please configure your GitHub API token in the .env file.') }}
                    </p>
                @endif
            </div>

            <!-- Configuration Help -->
            <div class="content-card">
                <h5><i class="ti ti-settings"></i> {{ __('Configuration') }}</h5>
                <p class="text-muted">{{ __('Add these settings to your .env file:') }}</p>

                <div class="config-code">
                    <div><span class="key">GITHUB_API_TOKEN</span>=<span class="value">your_github_token_here</span></div>
                    <div><span class="key">GITHUB_REPOSITORIES</span>=<span class="value">owner/repo1,owner/repo2</span></div>
                </div>

                <p class="text-muted mt-3 small">
                    <i class="ti ti-info-circle"></i>
                    {{ __('Generate a GitHub token at') }}
                    <a href="https://github.com/settings/tokens" target="_blank">github.com/settings/tokens</a>
                    {{ __('with "repo" scope.') }}
                </p>
            </div>
        </div>

        <!-- Sync Controls -->
        <div class="col-md-6">
            <div class="content-card">
                <h5><i class="ti ti-refresh"></i> {{ __('Sync Commits') }}</h5>

                @if($isConfigured && $connectionTest && $connectionTest['success'])
                    <form method="POST" action="{{ route('productivity.sync-commits') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">{{ __('Time Range') }}</label>
                            <select name="days" class="form-select">
                                <option value="7">{{ __('Last 7 days') }}</option>
                                <option value="14">{{ __('Last 14 days') }}</option>
                                <option value="30" selected>{{ __('Last 30 days') }}</option>
                                <option value="60">{{ __('Last 60 days') }}</option>
                                <option value="90">{{ __('Last 90 days') }}</option>
                                <option value="180">{{ __('Last 6 months') }}</option>
                                <option value="365">{{ __('Last year') }}</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Repository') }}</label>
                            <select name="repo" class="form-select">
                                <option value="">{{ __('All Configured Repositories') }}</option>
                                @foreach($repositories as $repo)
                                    <option value="{{ $repo }}">{{ $repo }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-download"></i> {{ __('Sync Now') }}
                        </button>
                    </form>

                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            <i class="ti ti-info-circle"></i>
                            {{ __('Syncing will fetch commit history from GitHub. This may take a few minutes for large repositories.') }}
                        </small>
                    </div>
                @else
                    <div class="alert alert-warning mb-0">
                        <i class="ti ti-alert-triangle"></i>
                        {{ __('Please configure and verify your GitHub API connection first.') }}
                    </div>
                @endif
            </div>

            <!-- Configured Repositories -->
            <div class="content-card">
                <h5><i class="ti ti-folders"></i> {{ __('Configured Repositories') }}</h5>

                @if(count($repositories) > 0)
                    @foreach($repositories as $repo)
                        <div class="repo-item">
                            <span class="repo-name">
                                <i class="ti ti-brand-github"></i> {{ $repo }}
                            </span>
                            <a href="https://github.com/{{ $repo }}" target="_blank" class="btn btn-sm btn-light">
                                <i class="ti ti-external-link"></i>
                            </a>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted text-center py-3">
                        <i class="ti ti-folder-off"></i><br>
                        {{ __('No repositories configured.') }}<br>
                        <small>{{ __('Add GITHUB_REPOSITORIES to your .env file.') }}</small>
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Command Line Alternative -->
    <div class="content-card">
        <h5><i class="ti ti-terminal"></i> {{ __('Command Line Sync') }}</h5>
        <p class="text-muted">{{ __('You can also sync commits using the artisan command:') }}</p>

        <div class="config-code">
            <div># {{ __('Sync all repositories (last 30 days)') }}</div>
            <div>php artisan github:sync-commits</div>
            <br>
            <div># {{ __('Sync specific repository') }}</div>
            <div>php artisan github:sync-commits --repo=owner/repo</div>
            <br>
            <div># {{ __('Sync last 90 days') }}</div>
            <div>php artisan github:sync-commits --days=90</div>
            <br>
            <div># {{ __('Sync specific date range') }}</div>
            <div>php artisan github:sync-commits --since=2024-01-01 --until=2024-12-31</div>
        </div>
    </div>
@endsection
