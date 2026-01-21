@extends('layouts.admin')

@section('page-title')
    {{ __('Task Reminder Settings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Task Reminders') }}</li>
@endsection

@section('action-btn')
    <div class="d-flex gap-2">
        <a href="{{ route('task-reminders.logs') }}" class="btn btn-sm btn-outline-primary">
            <i class="ti ti-list"></i> {{ __('View Logs') }}
        </a>
        <button type="button" class="btn btn-sm btn-primary" id="previewEligibleUsers">
            <i class="ti ti-users"></i> {{ __('Preview Eligible Users') }}
        </button>
    </div>
@endsection

@push('css-page')
<style>
    .settings-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 24px;
    }
    .settings-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .settings-card-header h5 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .settings-card-header h5 i {
        color: #667eea;
    }
    .settings-card-body {
        padding: 20px;
    }
    .template-preview {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        white-space: pre-wrap;
        font-family: inherit;
        font-size: 13px;
        max-height: 200px;
        overflow-y: auto;
    }
    .schedule-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 12px;
    }
    .schedule-item .schedule-type {
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 8px;
    }
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 20px;
        color: #fff;
        text-align: center;
    }
    .stat-card.secondary {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .stat-card.warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .stat-card h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .stat-card p {
        margin: 5px 0 0;
        opacity: 0.9;
        font-size: 13px;
    }
    .variable-badge {
        display: inline-block;
        background: #e9ecef;
        color: #495057;
        padding: 4px 10px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 12px;
        margin: 3px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .variable-badge:hover {
        background: #667eea;
        color: #fff;
    }
    .eligible-users-list {
        max-height: 400px;
        overflow-y: auto;
    }
    .user-item {
        display: flex;
        align-items: center;
        padding: 10px;
        border-bottom: 1px solid #f1f5f9;
    }
    .user-item:last-child {
        border-bottom: none;
    }
</style>
@endpush

@section('content')
<div class="row">
    <!-- Statistics -->
    <div class="col-md-3">
        <div class="stat-card">
            <h3>{{ $statistics['total_sent'] }}</h3>
            <p>{{ __('Reminders Sent (30 days)') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card secondary">
            <h3>{{ $statistics['response_rate'] }}%</h3>
            <p>{{ __('Response Rate') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <h3>{{ $statistics['no_task_reminders'] }}</h3>
            <p>{{ __('No Task Reminders') }}</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <h3>{{ $statistics['in_progress_reminders'] }}</h3>
            <p>{{ __('In Progress Reminders') }}</p>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Recipient Configuration -->
    <div class="col-md-6">
        <div class="settings-card">
            <div class="settings-card-header">
                <h5><i class="ti ti-users-minus"></i> {{ __('Exclude from Reminders') }}</h5>
            </div>
            <div class="settings-card-body">
                <form action="{{ route('task-reminders.save-recipients') }}" method="POST">
                    @csrf
                    <p class="text-muted small mb-3">
                        {{ __('Select departments, designations, or user types that should NOT receive task reminders. Everyone else will receive them.') }}
                    </p>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Exclude Departments') }}</label>
                        <select name="excluded_departments[]" class="form-control select2" multiple>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}"
                                    {{ $recipients->where('type', 'department')->where('type_id', $dept->id)->where('should_receive', false)->count() > 0 ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('e.g., HR, Management, Admin') }}</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Exclude Designations') }}</label>
                        <select name="excluded_designations[]" class="form-control select2" multiple>
                            @foreach($designations as $desig)
                                <option value="{{ $desig->id }}"
                                    {{ $recipients->where('type', 'designation')->where('type_id', $desig->id)->where('should_receive', false)->count() > 0 ? 'selected' : '' }}>
                                    {{ $desig->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('e.g., Manager, Director, CEO') }}</small>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Exclude User Types') }}</label>
                        <select name="excluded_user_types[]" class="form-control select2" multiple>
                            @foreach($userTypes as $type)
                                <option value="{{ $type }}"
                                    {{ $recipients->where('type', 'user_type')->where('type_name', $type)->where('should_receive', false)->count() > 0 ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('e.g., Company (Admin), Client') }}</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy"></i> {{ __('Save Recipient Settings') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Schedule Configuration -->
    <div class="col-md-6">
        <div class="settings-card">
            <div class="settings-card-header">
                <h5><i class="ti ti-clock"></i> {{ __('Reminder Schedules') }}</h5>
            </div>
            <div class="settings-card-body">
                @foreach($schedules as $schedule)
                    <div class="schedule-item">
                        <div class="schedule-type">
                            @if($schedule->type == 'no_task_assigned')
                                <i class="ti ti-alert-circle text-warning"></i> {{ __('No Task Assigned Reminder') }}
                            @else
                                <i class="ti ti-progress text-info"></i> {{ __('In Progress Task Reminder') }}
                            @endif
                        </div>
                        <form action="{{ route('task-reminders.save-schedule') }}" method="POST" class="row g-2 align-items-end">
                            @csrf
                            <input type="hidden" name="type" value="{{ $schedule->type }}">
                            <div class="col-md-4">
                                <label class="form-label small">{{ __('Time') }}</label>
                                <input type="time" name="scheduled_time" class="form-control" value="{{ \Carbon\Carbon::parse($schedule->scheduled_time)->format('H:i') }}">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="is_enabled" id="enabled_{{ $schedule->type }}" {{ $schedule->is_enabled ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enabled_{{ $schedule->type }}">{{ __('Enabled') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="include_weekends" id="weekends_{{ $schedule->type }}" {{ $schedule->include_weekends ? 'checked' : '' }}>
                                    <label class="form-check-label" for="weekends_{{ $schedule->type }}">{{ __('Weekends') }}</label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-primary w-100">
                                    <i class="ti ti-check"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                @endforeach

                <div class="alert alert-info mt-3 mb-0">
                    <i class="ti ti-info-circle"></i>
                    {{ __('Make sure to set up a cron job to run the reminder commands. See documentation for details.') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Templates -->
<div class="row mt-4">
    <div class="col-12">
        <div class="settings-card">
            <div class="settings-card-header">
                <h5><i class="ti ti-template"></i> {{ __('Message Templates') }}</h5>
                <div>
                    <span class="text-muted small">{{ __('Available Variables:') }}</span>
                    @foreach(\App\Models\TaskReminderTemplate::$availableVariables as $var => $desc)
                        <span class="variable-badge" title="{{ $desc }}" onclick="copyVariable('{{ $var }}')">{{ $var }}</span>
                    @endforeach
                </div>
            </div>
            <div class="settings-card-body">
                <div class="row">
                    @foreach($templates as $template)
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>
                                        @if($template->type == 'no_task_assigned')
                                            <i class="ti ti-alert-circle text-warning"></i>
                                        @else
                                            <i class="ti ti-progress text-info"></i>
                                        @endif
                                        {{ $template->name }}
                                    </span>
                                    <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $template->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="template-preview">{{ $template->message_template }}</div>
                                </div>
                                <div class="card-footer">
                                    <a href="#" class="btn btn-sm btn-outline-primary"
                                       data-url="{{ route('task-reminders.edit-template', $template->id) }}"
                                       data-ajax-popup="true"
                                       data-size="lg"
                                       data-bs-original-title="{{ __('Edit Template') }}">
                                        <i class="ti ti-pencil"></i> {{ __('Edit') }}
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-info test-reminder-btn"
                                            data-type="{{ $template->type }}">
                                        <i class="ti ti-send"></i> {{ __('Send Test') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Logs Preview -->
<div class="row mt-4">
    <div class="col-12">
        <div class="settings-card">
            <div class="settings-card-header">
                <h5><i class="ti ti-history"></i> {{ __('Recent Reminder Logs') }}</h5>
                <a href="{{ route('task-reminders.logs') }}" class="btn btn-sm btn-outline-primary">
                    {{ __('View All') }} <i class="ti ti-arrow-right"></i>
                </a>
            </div>
            <div class="settings-card-body">
                @if($recentLogs->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Response') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLogs->take(10) as $log)
                                    <tr>
                                        <td>{{ $log->reminder_date->format('M d, Y') }}</td>
                                        <td>{{ $log->user ? $log->user->name : 'Unknown' }}</td>
                                        <td>
                                            @if($log->type == 'no_task_assigned')
                                                <span class="badge bg-warning">{{ __('No Task') }}</span>
                                            @else
                                                <span class="badge bg-info">{{ __('In Progress') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($log->response_received)
                                                <span class="badge bg-success">{{ __('Received') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted text-center py-4">{{ __('No reminder logs yet.') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Eligible Users Modal -->
<div class="modal fade" id="eligibleUsersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-users"></i> {{ __('Eligible Users for Reminders') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="eligibleUsersContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">{{ __('Loading...') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Reminder Modal -->
<div class="modal fade" id="testReminderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-send"></i> {{ __('Send Test Reminder') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="testReminderType">
                <div class="form-group mb-3">
                    <label class="form-label">{{ __('Select User') }}</label>
                    <select id="testReminderUser" class="form-control">
                        <option value="">{{ __('Select a user...') }}</option>
                    </select>
                </div>
                <div id="testReminderResult" class="d-none">
                    <div class="alert alert-success">
                        <strong>{{ __('Message Sent!') }}</strong>
                    </div>
                    <label class="form-label">{{ __('Preview:') }}</label>
                    <div class="template-preview" id="testReminderPreview"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary" id="sendTestReminderBtn">
                    <i class="ti ti-send"></i> {{ __('Send Test') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
$(document).ready(function() {
    // Initialize Select2
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: '{{ __("Select...") }}',
            allowClear: true
        });
    }

    // Copy variable to clipboard
    window.copyVariable = function(variable) {
        navigator.clipboard.writeText(variable);
        show_toastr('success', '{{ __("Copied to clipboard!") }}', 'success');
    };

    // Preview eligible users
    $('#previewEligibleUsers').on('click', function() {
        var modal = new bootstrap.Modal(document.getElementById('eligibleUsersModal'));
        modal.show();

        $.ajax({
            url: '{{ route("task-reminders.eligible-users") }}',
            method: 'GET',
            success: function(response) {
                var html = '<div class="eligible-users-list">';
                if (response.users.length > 0) {
                    html += '<p class="text-muted mb-3">{{ __("These users will receive task reminders:") }} <strong>' + response.users.length + ' {{ __("users") }}</strong></p>';
                    response.users.forEach(function(user) {
                        html += '<div class="user-item">';
                        html += '<div class="flex-grow-1">';
                        html += '<strong>' + user.name + '</strong><br>';
                        html += '<small class="text-muted">' + user.email + ' | ' + user.department + ' | ' + user.designation + '</small>';
                        html += '</div></div>';
                    });
                } else {
                    html += '<p class="text-center text-muted py-4">{{ __("No eligible users found. Check your exclusion settings.") }}</p>';
                }
                html += '</div>';
                $('#eligibleUsersContent').html(html);
            },
            error: function() {
                $('#eligibleUsersContent').html('<p class="text-danger text-center">{{ __("Error loading users.") }}</p>');
            }
        });
    });

    // Test reminder
    var eligibleUsersCache = null;

    $('.test-reminder-btn').on('click', function() {
        var type = $(this).data('type');
        $('#testReminderType').val(type);
        $('#testReminderResult').addClass('d-none');

        var modal = new bootstrap.Modal(document.getElementById('testReminderModal'));
        modal.show();

        // Load users if not cached
        if (!eligibleUsersCache) {
            $.ajax({
                url: '{{ route("task-reminders.eligible-users") }}',
                method: 'GET',
                success: function(response) {
                    eligibleUsersCache = response.users;
                    populateUserSelect(eligibleUsersCache);
                }
            });
        } else {
            populateUserSelect(eligibleUsersCache);
        }
    });

    function populateUserSelect(users) {
        var select = $('#testReminderUser');
        select.empty().append('<option value="">{{ __("Select a user...") }}</option>');
        users.forEach(function(user) {
            select.append('<option value="' + user.id + '">' + user.name + ' (' + user.department + ')</option>');
        });
    }

    $('#sendTestReminderBtn').on('click', function() {
        var type = $('#testReminderType').val();
        var userId = $('#testReminderUser').val();

        if (!userId) {
            show_toastr('error', '{{ __("Please select a user.") }}', 'error');
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{ __("Sending...") }}');

        $.ajax({
            url: '{{ route("task-reminders.send-test") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                type: type,
                user_id: userId
            },
            success: function(response) {
                $('#testReminderPreview').text(response.preview);
                $('#testReminderResult').removeClass('d-none');
                show_toastr('success', response.message, 'success');
            },
            error: function(xhr) {
                var error = xhr.responseJSON ? xhr.responseJSON.error : '{{ __("Error sending test reminder.") }}';
                show_toastr('error', error, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ti ti-send"></i> {{ __("Send Test") }}');
            }
        });
    });
});
</script>
@endpush
