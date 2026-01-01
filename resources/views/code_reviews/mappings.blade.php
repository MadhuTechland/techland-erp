@extends('layouts.admin')

@section('page-title')
    {{ __('GitHub User Mappings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('code-reviews.index') }}">{{ __('Code Reviews') }}</a></li>
    <li class="breadcrumb-item">{{ __('User Mappings') }}</li>
@endsection

@push('css-page')
<style>
    .mapping-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    .mapping-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        font-weight: 600;
    }
    .mapping-body { padding: 20px; }

    .user-mapping-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 15px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    .user-mapping-item:last-child { margin-bottom: 0; }

    .github-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #24292e;
        color: #fff;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin-right: 8px;
        margin-bottom: 5px;
    }

    .unmapped-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #f0f0f0;
        color: #666;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin: 3px;
    }

    .add-mapping-form {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 20px;
        color: #fff;
    }
    .add-mapping-form .form-label { color: rgba(255,255,255,0.9); }
    .add-mapping-form .form-control { background: rgba(255,255,255,0.95); }
</style>
@endpush

@section('content')
<div class="row">
    <!-- Add New Mapping -->
    <div class="col-md-5">
        <div class="add-mapping-form">
            <h5 class="mb-4"><i class="ti ti-link"></i> {{ __('Link GitHub Account to User') }}</h5>
            <form method="POST" action="{{ route('code-reviews.mappings.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('ERP User') }}</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">{{ __('Select User') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('GitHub Username') }}</label>
                    <input type="text" name="github_username" class="form-control" placeholder="e.g., johndoe" required
                           list="unmapped-usernames">
                    <datalist id="unmapped-usernames">
                        @foreach($unmappedUsernames as $username)
                            <option value="{{ $username }}">
                        @endforeach
                    </datalist>
                    <small class="text-light opacity-75">{{ __('Type or select from unmapped usernames') }}</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('GitHub Email (Optional)') }}</label>
                    <input type="email" name="github_email" class="form-control" placeholder="e.g., john@example.com">
                    <small class="text-light opacity-75">{{ __('Used for Google Chat mentions') }}</small>
                </div>
                <button type="submit" class="btn btn-light w-100">
                    <i class="ti ti-plus"></i> {{ __('Add Mapping') }}
                </button>
            </form>
        </div>

        <!-- Unmapped Usernames -->
        @if($unmappedUsernames->count() > 0)
        <div class="mapping-card mt-4">
            <div class="mapping-header">
                <i class="ti ti-alert-triangle text-warning"></i> {{ __('Unmapped GitHub Usernames') }}
                <span class="badge bg-warning text-dark">{{ $unmappedUsernames->count() }}</span>
            </div>
            <div class="mapping-body">
                <p class="text-muted mb-3">{{ __('These GitHub users have code reviews but are not linked to ERP users:') }}</p>
                <div>
                    @foreach($unmappedUsernames as $username)
                        <span class="unmapped-badge">
                            <i class="ti ti-brand-github"></i> {{ $username }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Current Mappings -->
    <div class="col-md-7">
        <div class="mapping-card">
            <div class="mapping-header">
                <i class="ti ti-users"></i> {{ __('Current Mappings') }}
            </div>
            <div class="mapping-body">
                @forelse($mappings as $userId => $userMappings)
                    @php $user = $userMappings->first()->user; @endphp
                    <div class="user-mapping-item">
                        <div>
                            <strong>{{ $user->name }}</strong>
                            <span class="text-muted">({{ $user->email }})</span>
                            <div class="mt-2">
                                @foreach($userMappings as $mapping)
                                    <span class="github-badge">
                                        <i class="ti ti-brand-github"></i>
                                        {{ $mapping->github_username }}
                                        <a href="{{ route('code-reviews.mappings.delete', $mapping->id) }}"
                                           onclick="return confirm('Are you sure you want to remove this mapping?')"
                                           class="text-light ms-2" title="Remove">
                                            <i class="ti ti-x"></i>
                                        </a>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="showAddMoreModal({{ $user->id }}, '{{ $user->name }}')">
                                <i class="ti ti-plus"></i> {{ __('Add More') }}
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <i class="ti ti-link-off" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-2">{{ __('No mappings configured yet.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Add More Modal -->
<div class="modal fade" id="addMoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Add GitHub Account') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('code-reviews.mappings.store') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="modal-user-id">
                    <p>{{ __('Adding GitHub account for:') }} <strong id="modal-user-name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">{{ __('GitHub Username') }}</label>
                        <input type="text" name="github_username" class="form-control" required
                               list="unmapped-usernames">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('GitHub Email (Optional)') }}</label>
                        <input type="email" name="github_email" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Add') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
function showAddMoreModal(userId, userName) {
    document.getElementById('modal-user-id').value = userId;
    document.getElementById('modal-user-name').textContent = userName;
    new bootstrap.Modal(document.getElementById('addMoreModal')).show();
}
</script>
@endpush
