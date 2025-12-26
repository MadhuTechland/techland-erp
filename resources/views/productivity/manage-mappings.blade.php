@extends('layouts.admin')

@section('page-title')
    {{ __('GitHub Username Mappings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('productivity.admin-dashboard') }}">{{ __('Developer Activity') }}</a></li>
    <li class="breadcrumb-item">{{ __('Mappings') }}</li>
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

        .mapping-table {
            width: 100%;
        }

        .mapping-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 14px 16px;
            font-weight: 600;
        }

        .mapping-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .mapping-table tbody tr:hover {
            background: #f8f9fa;
        }

        .github-badge {
            background: #24292e;
            color: #fff;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .unmapped-item {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 6px 12px;
            border-radius: 8px;
            margin: 4px;
            font-size: 0.85rem;
        }

        .user-select {
            min-width: 200px;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <!-- Add New Mapping -->
        <div class="col-md-4">
            <div class="content-card">
                <h5><i class="ti ti-link"></i> {{ __('Add New Mapping') }}</h5>

                <form method="POST" action="{{ route('productivity.mappings.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">{{ __('GitHub Username') }}</label>
                        @if($unmappedUsernames->count() > 0)
                            <select name="github_username" class="form-select" required>
                                <option value="">{{ __('Select GitHub username') }}</option>
                                @foreach($unmappedUsernames as $username)
                                    <option value="{{ $username }}">{{ $username }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('Or enter manually below') }}</small>
                        @endif
                        <input type="text" name="github_username_manual" class="form-control mt-2"
                               placeholder="{{ __('Enter GitHub username manually') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('ERP User') }}</label>
                        <select name="user_id" class="form-select user-select" required>
                            <option value="">{{ __('Select user') }}</option>
                            @foreach($unmappedUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-plus"></i> {{ __('Create Mapping') }}
                    </button>
                </form>
            </div>

            <!-- Unmapped GitHub Usernames -->
            @if($unmappedUsernames->count() > 0)
                <div class="content-card">
                    <h5><i class="ti ti-alert-triangle"></i> {{ __('Unmapped GitHub Users') }}</h5>
                    <p class="text-muted small">{{ __('These GitHub usernames have commits but are not linked to ERP users.') }}</p>
                    <div>
                        @foreach($unmappedUsernames as $username)
                            <span class="unmapped-item">
                                <i class="ti ti-brand-github"></i> {{ $username }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Existing Mappings -->
        <div class="col-md-8">
            <div class="content-card">
                <h5><i class="ti ti-list"></i> {{ __('Existing Mappings') }}</h5>

                @if($mappings->count() > 0)
                    <table class="mapping-table">
                        <thead>
                            <tr>
                                <th>{{ __('GitHub Username') }}</th>
                                <th>{{ __('ERP User') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mappings as $mapping)
                                <tr>
                                    <td>
                                        <span class="github-badge">
                                            <i class="ti ti-brand-github"></i>
                                            {{ $mapping->github_username }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($mapping->user)
                                            <strong>{{ $mapping->user->name }}</strong>
                                            <br><small class="text-muted">{{ $mapping->user->email }}</small>
                                        @else
                                            <span class="text-danger">{{ __('User deleted') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $mapping->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('productivity.mappings.delete', $mapping->id) }}"
                                              onsubmit="return confirm('{{ __('Are you sure you want to delete this mapping?') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="{{ __('Delete') }}">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-center py-5">
                        <i class="ti ti-link-off" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">{{ __('No mappings created yet.') }}</p>
                        <p class="text-muted small">{{ __('Create mappings to link GitHub usernames to ERP users.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script-page')
    <script>
        $(document).ready(function() {
            // If manual username is entered, clear select and vice versa
            $('input[name="github_username_manual"]').on('input', function() {
                if ($(this).val()) {
                    $('select[name="github_username"]').val('');
                }
            });

            $('select[name="github_username"]').on('change', function() {
                if ($(this).val()) {
                    $('input[name="github_username_manual"]').val('');
                }
            });

            // On form submit, use manual input if provided
            $('form').on('submit', function(e) {
                var manualInput = $('input[name="github_username_manual"]').val();
                var selectInput = $('select[name="github_username"]').val();

                if (manualInput) {
                    // Create hidden input with the manual value
                    if (!selectInput) {
                        $('select[name="github_username"]').removeAttr('name');
                        $('input[name="github_username_manual"]').attr('name', 'github_username');
                    }
                }
            });
        });
    </script>
@endpush
