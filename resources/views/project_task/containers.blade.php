@extends('layouts.admin')
@section('page-title')
    {{ __('Epics & Stories') }} - {{ $project->project_name }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">{{ __('Project') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->id) }}">{{ ucwords($project->project_name) }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.tasks.index', $project->id) }}">{{ __('Tasks') }}</a></li>
    <li class="breadcrumb-item">{{ __('Epics & Stories') }}</li>
@endsection

@section('action-btn')
    <div class="d-flex gap-2">
        <a href="{{ route('projects.tasks.index', $project->id) }}" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="{{ __('Back to Tasks') }}">
            <i class="ti ti-arrow-left"></i> {{ __('Back') }}
        </a>
        @can('create project task')
            <a href="#" data-size="lg" data-url="{{ route('projects.tasks.create', $project->id) }}"
                data-ajax-popup="true" data-bs-toggle="tooltip"
                title="{{ __('Create Epic/Story') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i> {{ __('Create') }}
            </a>
        @endcan
    </div>
@endsection

@push('css-page')
<style>
    .container-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .container-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .container-card-header h5 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .container-card-header .badge {
        font-size: 12px;
        padding: 4px 10px;
        border-radius: 20px;
    }
    .container-table {
        width: 100%;
    }
    .container-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        font-weight: 600;
        font-size: 13px;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e9ecef;
    }
    .container-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .container-table tbody tr:hover {
        background: #f8fafc;
    }
    .container-table tbody tr:last-child td {
        border-bottom: none;
    }
    .issue-key {
        font-family: monospace;
        font-size: 12px;
        color: #6366f1;
        background: #eef2ff;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 600;
    }
    .container-name {
        font-weight: 500;
        color: #1e293b;
    }
    .container-name a {
        color: inherit;
        text-decoration: none;
    }
    .container-name a:hover {
        color: #6366f1;
    }
    .parent-badge {
        font-size: 11px;
        color: #64748b;
        background: #f1f5f9;
        padding: 3px 8px;
        border-radius: 4px;
    }
    .child-count {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: #64748b;
    }
    .child-count i {
        font-size: 14px;
    }
    .action-btns {
        display: flex;
        gap: 8px;
    }
    .action-btns .btn {
        padding: 6px 10px;
        font-size: 13px;
        border-radius: 6px;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }
    .empty-state i {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }
    .epic-color-dot {
        width: 12px;
        height: 12px;
        border-radius: 3px;
        display: inline-block;
        margin-right: 8px;
    }
    .stage-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .nav-tabs-custom {
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 20px;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        color: #64748b;
        font-weight: 500;
        padding: 12px 20px;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }
    .nav-tabs-custom .nav-link:hover {
        color: #6366f1;
        border-color: transparent;
    }
    .nav-tabs-custom .nav-link.active {
        color: #6366f1;
        border-bottom-color: #6366f1;
        background: transparent;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Tabs for Epics and Stories -->
        <ul class="nav nav-tabs nav-tabs-custom" id="containerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="epics-tab" data-bs-toggle="tab" data-bs-target="#epics-content" type="button" role="tab">
                    <i class="ti ti-bolt me-1"></i> {{ __('Epics') }}
                    <span class="badge bg-primary ms-1">{{ $epics->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stories-tab" data-bs-toggle="tab" data-bs-target="#stories-content" type="button" role="tab">
                    <i class="ti ti-book me-1"></i> {{ __('Stories') }}
                    <span class="badge bg-info ms-1">{{ $stories->count() }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="containerTabsContent">
            <!-- Epics Tab -->
            <div class="tab-pane fade show active" id="epics-content" role="tabpanel">
                <div class="container-card">
                    <div class="container-card-header">
                        <h5>
                            <i class="ti ti-bolt text-purple"></i>
                            {{ __('All Epics') }}
                        </h5>
                    </div>
                    @if($epics->count() > 0)
                        <table class="container-table">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">{{ __('Key') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th style="width: 120px;">{{ __('Stories') }}</th>
                                    <th style="width: 120px;">{{ __('Tasks') }}</th>
                                    <th style="width: 130px;">{{ __('Stage') }}</th>
                                    <th style="width: 140px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($epics as $epic)
                                    @php
                                        $storyCount = \App\Models\ProjectTask::where('parent_id', $epic->id)
                                            ->where('issue_type_id', $storyType ? $storyType->id : 0)
                                            ->count();
                                        $taskCount = \App\Models\ProjectTask::where('parent_id', $epic->id)
                                            ->where(function($q) use ($storyType) {
                                                $q->where('issue_type_id', '!=', $storyType ? $storyType->id : 0)
                                                  ->orWhereNull('issue_type_id');
                                            })
                                            ->count();
                                        // Also count tasks under stories of this epic
                                        $storyIds = \App\Models\ProjectTask::where('parent_id', $epic->id)
                                            ->where('issue_type_id', $storyType ? $storyType->id : 0)
                                            ->pluck('id');
                                        $taskCount += \App\Models\ProjectTask::whereIn('parent_id', $storyIds)->count();
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($epic->issue_key)
                                                <span class="issue-key">{{ $epic->issue_key }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="container-name">
                                                <span class="epic-color-dot" style="background: {{ $epic->issueType->color ?? '#6366f1' }};"></span>
                                                <a href="#" data-url="{{ route('projects.tasks.show', [$project->id, $epic->id]) }}" data-ajax-popup="true" data-size="lg" data-bs-original-title="{{ $epic->name }}">
                                                    {{ $epic->name }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="child-count">
                                                <i class="ti ti-book"></i> {{ $storyCount }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="child-count">
                                                <i class="ti ti-subtask"></i> {{ $taskCount }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($epic->stage)
                                                <span class="stage-badge" style="background: {{ $epic->stage->color ?? '#e9ecef' }}20; color: {{ $epic->stage->color ?? '#64748b' }};">
                                                    {{ $epic->stage->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                @can('view project task')
                                                    <a href="#" data-url="{{ route('projects.tasks.show', [$project->id, $epic->id]) }}" data-ajax-popup="true" data-size="lg" class="btn btn-sm btn-outline-info" title="{{ __('View') }}">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('edit project task')
                                                    <a href="#" data-url="{{ route('projects.tasks.edit', [$project->id, $epic->id]) }}" data-ajax-popup="true" data-size="lg" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                @endcan
                                                @can('delete project task')
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['projects.tasks.destroy', [$project->id, $epic->id]], 'class' => 'd-inline']) !!}
                                                    <button type="button" class="btn btn-sm btn-outline-danger bs-pass-para" title="{{ __('Delete') }}">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                    {!! Form::close() !!}
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="ti ti-bolt"></i>
                            <p>{{ __('No epics created yet') }}</p>
                            <p class="text-muted small">{{ __('Create an Epic to organize your Stories and Tasks') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stories Tab -->
            <div class="tab-pane fade" id="stories-content" role="tabpanel">
                <div class="container-card">
                    <div class="container-card-header">
                        <h5>
                            <i class="ti ti-book text-info"></i>
                            {{ __('All Stories') }}
                        </h5>
                    </div>
                    @if($stories->count() > 0)
                        <table class="container-table">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">{{ __('Key') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th style="width: 150px;">{{ __('Parent Epic') }}</th>
                                    <th style="width: 100px;">{{ __('Tasks') }}</th>
                                    <th style="width: 130px;">{{ __('Stage') }}</th>
                                    <th style="width: 140px;">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stories as $story)
                                    @php
                                        $taskCount = \App\Models\ProjectTask::where('parent_id', $story->id)->count();
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($story->issue_key)
                                                <span class="issue-key" style="color: #0ea5e9; background: #e0f2fe;">{{ $story->issue_key }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="container-name">
                                                <a href="#" data-url="{{ route('projects.tasks.show', [$project->id, $story->id]) }}" data-ajax-popup="true" data-size="lg" data-bs-original-title="{{ $story->name }}">
                                                    {{ $story->name }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            @if($story->parent)
                                                <span class="parent-badge">
                                                    <i class="ti ti-bolt me-1"></i>{{ Str::limit($story->parent->name, 20) }}
                                                </span>
                                            @else
                                                <span class="text-muted">{{ __('No Parent') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="child-count">
                                                <i class="ti ti-subtask"></i> {{ $taskCount }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($story->stage)
                                                <span class="stage-badge" style="background: {{ $story->stage->color ?? '#e9ecef' }}20; color: {{ $story->stage->color ?? '#64748b' }};">
                                                    {{ $story->stage->name }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="action-btns">
                                                @can('view project task')
                                                    <a href="#" data-url="{{ route('projects.tasks.show', [$project->id, $story->id]) }}" data-ajax-popup="true" data-size="lg" class="btn btn-sm btn-outline-info" title="{{ __('View') }}">
                                                        <i class="ti ti-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('edit project task')
                                                    <a href="#" data-url="{{ route('projects.tasks.edit', [$project->id, $story->id]) }}" data-ajax-popup="true" data-size="lg" class="btn btn-sm btn-outline-primary" title="{{ __('Edit') }}">
                                                        <i class="ti ti-pencil"></i>
                                                    </a>
                                                @endcan
                                                @can('delete project task')
                                                    {!! Form::open(['method' => 'DELETE', 'route' => ['projects.tasks.destroy', [$project->id, $story->id]], 'class' => 'd-inline']) !!}
                                                    <button type="button" class="btn btn-sm btn-outline-danger bs-pass-para" title="{{ __('Delete') }}">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                    {!! Form::close() !!}
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="empty-state">
                            <i class="ti ti-book"></i>
                            <p>{{ __('No stories created yet') }}</p>
                            <p class="text-muted small">{{ __('Create a Story to organize your Tasks') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
$(document).ready(function() {
    // Delete confirmation
    $(document).on('click', '.bs-pass-para', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger'
            },
            buttonsStyling: false
        });
        swalWithBootstrapButtons.fire({
            title: '{{ __("Are you sure?") }}',
            text: '{{ __("This will also affect all child items. This action cannot be undone.") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, delete it!") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
