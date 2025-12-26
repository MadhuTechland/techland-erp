@extends('layouts.admin')

@section('page-title')
    {{__('Tasks')}}
@endsection

@push('css-page')
    <style>
        .filter-bar-list {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .filter-bar-list label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .filter-bar-list .form-select {
            font-size: 13px;
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            height: 40px;
        }
        .filter-bar-list .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }
    </style>
@endpush

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">{{__('Dashboard')}}</a></li>
    <li class="breadcrumb-item"><a href="{{route('projects.index')}}">{{__('Project')}}</a></li>
    <li class="breadcrumb-item">{{__('Task')}}</li>
@endsection

@section('action-btn')
    <div class="float-end">

        <a href="#" class="btn bg-primary text-white btn-sm me-1" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-bs-toggle="tooltip" title="{{ __('Filter') }}" data-original-title="{{ __('Filter') }}">
            <span class="btn-inner-icon"><i class="ti ti-filter"></i></span>
        </a>
        <div class="dropdown-menu dropdown-menu-right dropdown-steady" id="task_sort">
            <a class="dropdown-item active" href="#" data-val="created_at-desc">
                <i class="ti ti-sort-amount-down"></i>{{__('Newest')}}
            </a>
            <a class="dropdown-item" href="#" data-val="created_at-asc">
                <i class="ti ti-sort-amount-up"></i>{{__('Oldest')}}
            </a>
            <a class="dropdown-item" href="#" data-val="name-asc">
                <i class="ti ti-sort-alpha-down"></i>{{__('From A-Z')}}
            </a>
            <a class="dropdown-item" href="#" data-val="name-desc">
                <i class="ti ti-sort-alpha-up"></i>{{__('From Z-A')}}
            </a>
        </div>


        <a href="#" class="btn btn-primary-subtle text-white btn-sm me-1" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="btn-inner--icon">{{__('Status')}}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right task-filter-actions dropdown-steady" id="task_status">
            <a class="dropdown-item filter-action filter-show-all pl-4" href="#">{{__('Show All')}}</a>
            <hr class="my-0">
            <a class="dropdown-item filter-action pl-4 active" href="#" data-val="see_my_tasks">{{ __('See My Tasks') }}</a>
            <hr class="my-0">
            @foreach(\App\Models\ProjectTask::$priority as $key => $val)
                <a class="dropdown-item filter-action pl-4" href="#" data-val="{{ $key }}">{{__($val)}}</a>
            @endforeach
            <hr class="my-0">
            <a class="dropdown-item filter-action filter-other pl-4" href="#" data-val="due_today">{{ __('Due Today') }}</a>
            <a class="dropdown-item filter-action filter-other pl-4" href="#" data-val="over_due">{{ __('Over Due') }}</a>
            <a class="dropdown-item filter-action filter-other pl-4" href="#" data-val="starred">{{ __('Starred') }}</a>
        </div>

        @if($view == 'grid')
            <a href="{{ route('taskBoard.view', 'list') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="{{__('List View')}}">
                <span class="btn-inner--text"><i class="ti ti-list"></i>{{__('List View')}}</span>
            </a>
        @else
            <a href="{{ route('taskBoard.view', 'grid') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="{{__('Grid View')}}">
                <span class="btn-inner--text"><i class="ti ti-table"></i></span>
            </a>
        @endif

    </div>

@endsection


@section('content')
    <!-- Filter Bar -->
    <div class="filter-bar-list">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label>{{__('Project')}}</label>
                <select class="form-select" id="filter_project">
                    <option value="">{{__('All Projects')}}</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>{{__('Epic')}}</label>
                <select class="form-select" id="filter_epic">
                    <option value="">{{__('All Epics')}}</option>
                    @foreach($epics as $epic)
                        <option value="{{ $epic->id }}" data-project="{{ $epic->project_id }}">{{ $epic->issue_key ? $epic->issue_key . ' - ' : '' }}{{ Str::limit($epic->name, 30) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>{{__('User Story')}}</label>
                <select class="form-select" id="filter_story">
                    <option value="">{{__('All Stories')}}</option>
                    @foreach($stories as $story)
                        <option value="{{ $story->id }}" data-project="{{ $story->project_id }}" data-epic="{{ $story->parent_id }}">{{ $story->issue_key ? $story->issue_key . ' - ' : '' }}{{ Str::limit($story->name, 30) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" id="task_keyword" placeholder="{{__('Search by task name...')}}">
            </div>
        </div>
    </div>

    <div class="row min-750" id="taskboard_view"></div>
@endsection

@push('script-page')
    <script>
        // ready
        $(function () {
            var sort = 'created_at-desc';
            var status = '';
            ajaxFilterTaskView('created_at-desc', '', ['see_my_tasks']);

            // when change status
            $(".task-filter-actions").on('click', '.filter-action', function (e) {
                if ($(this).hasClass('filter-show-all')) {
                    $('.filter-action').removeClass('active');
                    $(this).addClass('active');
                } else {

                    $('.filter-show-all').removeClass('active');
                    if ($(this).hasClass('filter-other')) {
                        $('.filter-other').removeClass('active');
                    }
                    if ($(this).hasClass('active')) {
                        $(this).removeClass('active');
                        $(this).blur();
                    } else {
                        $(this).addClass('active');
                    }
                }

                var filterArray = [];
                var url = $(this).parents('.task-filter-actions').attr('data-url');
                $('div.task-filter-actions').find('.active').each(function () {
                    filterArray.push($(this).attr('data-val'));
                });
                status = filterArray;
                ajaxFilterTaskView(sort, $('#task_keyword').val(), status);
            });

            // when change sorting order
            $('#task_sort').on('click', 'a', function () {
                sort = $(this).attr('data-val');
                ajaxFilterTaskView(sort, $('#task_keyword').val(), status);
                $('#task_sort a').removeClass('active');
                $(this).addClass('active');
            });

            // when searching by task name
            $(document).on('keyup', '#task_keyword', function () {
                ajaxFilterTaskView(sort, $(this).val(), status);
            });

            // Filter by project - also filter epic/story dropdowns
            $('#filter_project').on('change', function() {
                var selectedProject = $(this).val();

                // Filter epics dropdown
                $('#filter_epic option').each(function() {
                    if ($(this).val() === '') {
                        $(this).show();
                    } else if (selectedProject === '' || $(this).data('project').toString() === selectedProject) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                $('#filter_epic').val('');

                // Filter stories dropdown
                $('#filter_story option').each(function() {
                    if ($(this).val() === '') {
                        $(this).show();
                    } else if (selectedProject === '' || $(this).data('project').toString() === selectedProject) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                $('#filter_story').val('');

                ajaxFilterTaskView(sort, $('#task_keyword').val(), status);
            });

            // Filter by epic - also filter story dropdown
            $('#filter_epic').on('change', function() {
                var selectedEpic = $(this).val();

                // Filter stories dropdown based on selected epic
                $('#filter_story option').each(function() {
                    if ($(this).val() === '') {
                        $(this).show();
                    } else if (selectedEpic === '') {
                        $(this).show();
                    } else if ($(this).data('epic') && $(this).data('epic').toString() === selectedEpic) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                $('#filter_story').val('');

                ajaxFilterTaskView(sort, $('#task_keyword').val(), status);
            });

            // Filter by story
            $('#filter_story').on('change', function() {
                ajaxFilterTaskView(sort, $('#task_keyword').val(), status);
            });
        });

        // For Filter
        function ajaxFilterTaskView(task_sort, keyword = '', status = '') {
            var mainEle = $('#taskboard_view');
            var view = '{{$view}}';
            var projectId = $('#filter_project').val();
            var epicId = $('#filter_epic').val();
            var storyId = $('#filter_story').val();

            var data = {
                view: view,
                sort: task_sort,
                keyword: keyword,
                status: status,
                project_id: projectId,
                epic_id: epicId,
                story_id: storyId,
            }

            $.ajax({
                url: '{{ route('project.taskboard.view') }}',
                data: data,
                success: function (data) {
                    mainEle.html(data.html);
                }
            });
        }
    </script>
@endpush
