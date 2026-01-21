@extends('layouts.admin')

@section('page-title')
    {{ __('Task Reminder Logs') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('task-reminders.index') }}">{{ __('Task Reminders') }}</a></li>
    <li class="breadcrumb-item">{{ __('Logs') }}</li>
@endsection

@section('action-btn')
    <div class="d-flex gap-2">
        <a href="{{ route('task-reminders.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-arrow-left"></i> {{ __('Back to Settings') }}
        </a>
    </div>
@endsection

@push('css-page')
<style>
    .stat-mini {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
    }
    .stat-mini h4 {
        font-size: 24px;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
    }
    .stat-mini p {
        font-size: 12px;
        color: #718096;
        margin: 5px 0 0;
    }
    .message-preview {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
    }
    .response-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
</style>
@endpush

@section('content')
<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-mini">
            <h4>{{ $statistics['total_sent'] }}</h4>
            <p>{{ __('Total Sent') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <h4>{{ $statistics['responses_received'] }}</h4>
            <p>{{ __('Responses Received') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <h4>{{ $statistics['response_rate'] }}%</h4>
            <p>{{ __('Response Rate') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-mini">
            <h4>{{ $statistics['no_task_reminders'] }} / {{ $statistics['in_progress_reminders'] }}</h4>
            <p>{{ __('No Task / In Progress') }}</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('task-reminders.logs') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('Type') }}</label>
                <select name="type" class="form-select">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="no_task_assigned" {{ request('type') == 'no_task_assigned' ? 'selected' : '' }}>{{ __('No Task Assigned') }}</option>
                    <option value="in_progress_reminder" {{ request('type') == 'in_progress_reminder' ? 'selected' : '' }}>{{ __('In Progress') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('Response Status') }}</label>
                <select name="response_status" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    <option value="received" {{ request('response_status') == 'received' ? 'selected' : '' }}>{{ __('Received') }}</option>
                    <option value="pending" {{ request('response_status') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
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
            <div class="col-md-2">
                <a href="{{ route('task-reminders.logs') }}" class="btn btn-outline-secondary w-100">
                    <i class="ti ti-x"></i> {{ __('Clear') }}
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body">
        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Time') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Tasks') }}</th>
                            <th>{{ __('Message') }}</th>
                            <th>{{ __('Response') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>{{ $log->reminder_date->format('M d, Y') }}</td>
                                <td>{{ $log->created_at->format('g:i A') }}</td>
                                <td>
                                    @if($log->user)
                                        <strong>{{ $log->user->name }}</strong>
                                        <br><small class="text-muted">{{ $log->user->email }}</small>
                                    @else
                                        <span class="text-muted">{{ __('Unknown User') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->type == 'no_task_assigned')
                                        <span class="badge bg-warning">{{ __('No Task') }}</span>
                                    @else
                                        <span class="badge bg-info">{{ __('In Progress') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->task_count > 0)
                                        <span class="badge bg-secondary">{{ $log->task_count }} {{ __('tasks') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="message-preview" data-bs-toggle="tooltip" title="{{ $log->message_sent }}">
                                        {{ Str::limit($log->message_sent, 50) }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->response_received)
                                        <span class="response-badge text-success">
                                            <i class="ti ti-check"></i> {{ __('Received') }}
                                        </span>
                                        @if($log->response_at)
                                            <br><small class="text-muted">{{ $log->response_at->diffForHumans() }}</small>
                                        @endif
                                    @else
                                        <span class="response-badge text-muted">
                                            <i class="ti ti-clock"></i> {{ __('Pending') }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @if($log->response_received && $log->response_message)
                                <tr class="table-light">
                                    <td colspan="7" class="ps-5">
                                        <small class="text-muted">{{ __('Response:') }}</small>
                                        <p class="mb-0">{{ $log->response_message }}</p>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $logs->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="ti ti-history" style="font-size: 48px; color: #dee2e6;"></i>
                <p class="text-muted mt-3">{{ __('No reminder logs found for the selected filters.') }}</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('script-page')
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush
