@extends('layouts.admin')
@section('page-title')
    {{ ucwords($project->project_name) . __("'s Tasks") }}
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/summernote/summernote-bs4.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dragula.min.css') }}" id="main-style-link">
    <style>
        /* ===== JIRA-STYLE COMPACT KANBAN ===== */

        /* Board container */
        .kanban-wrapper {
            padding: 0;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            align-items: flex-start;
        }

        .kanban-wrapper > .col {
            flex: 0 0 260px;
            max-width: 260px;
            min-width: 260px;
            padding: 0 4px;
        }

        /* Column styling */
        .crm-sales-card {
            background: #f4f5f7;
            border: none;
            border-radius: 3px;
            box-shadow: none;
        }

        .crm-sales-card .card-header {
            background: transparent;
            border-bottom: none;
            padding: 8px 8px 4px;
            min-height: auto;
        }

        .crm-sales-card .card-header h4 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #5e6c84;
            letter-spacing: 0.04em;
            margin: 0;
        }

        /* Task count badge */
        .count {
            background: transparent;
            padding: 0;
            font-size: 11px;
            font-weight: 500;
            color: #5e6c84;
        }

        /* Kanban box / task container */
        .kanban-box {
            min-height: 40px;
            padding: 0 4px 4px;
            max-height: calc(100vh - 280px);
            overflow-y: auto;
        }

        .kanban-box::-webkit-scrollbar {
            width: 6px;
        }

        .kanban-box::-webkit-scrollbar-track {
            background: transparent;
        }

        .kanban-box::-webkit-scrollbar-thumb {
            background: #c1c7d0;
            border-radius: 3px;
        }

        /* ===== TASK CARDS - JIRA STYLE ===== */
        .sales-item {
            background: #fff;
            border-radius: 3px;
            margin-bottom: 6px;
            box-shadow: 0 1px 0 rgba(9,30,66,.13);
            border: none;
            cursor: pointer;
            transition: background 85ms ease-in, box-shadow 85ms ease-in;
        }

        .sales-item:hover {
            background: #f4f5f7;
            box-shadow: 0 1px 0 rgba(9,30,66,.25);
            transform: none;
        }

        /* Dragging states */
        .gu-mirror {
            cursor: grabbing !important;
            opacity: 1;
            transform: rotate(2deg);
            box-shadow: 0 8px 16px -4px rgba(9,30,66,.25), 0 0 0 1px rgba(9,30,66,.08) !important;
        }

        .gu-transit {
            opacity: 0.4;
        }

        .gu-over {
            background: #e4f0f6 !important;
        }

        /* Card sections - ultra compact */
        .sales-item-top {
            padding: 6px 8px 4px;
            border-bottom: none !important;
        }

        .sales-item-top h5 {
            font-size: 13px;
            font-weight: 400;
            line-height: 1.3;
            margin: 0 0 4px 0;
            color: #172b4d;
        }

        .sales-item-top h5 a {
            color: #172b4d;
            text-decoration: none;
        }

        .sales-item-top h5 a:hover {
            color: #0052cc;
            text-decoration: underline;
        }

        /* Badge container */
        .badge-wrp {
            margin-top: 4px;
        }

        .badge-wrp .badge {
            font-size: 10px;
            font-weight: 500;
            padding: 2px 4px;
            border-radius: 3px;
            text-transform: none;
            letter-spacing: 0;
        }

        /* Issue key badge */
        .badge-wrp .badge.bg-secondary {
            background: #dfe1e6 !important;
            color: #42526e !important;
            box-shadow: none;
            font-size: 10px;
            font-weight: 500;
        }

        /* Center section - minimal */
        .sales-item-center {
            padding: 4px 8px;
            border-bottom: none !important;
        }

        .sales-item-center ul {
            margin: 0;
            padding: 0;
            gap: 4px !important;
        }

        .sales-item-center li,
        .sales-item-bottom li {
            font-size: 10px;
            padding: 1px 4px;
            border-radius: 2px;
            background: transparent;
            border: none;
            color: #5e6c84;
        }

        .sales-item-center li i,
        .sales-item-bottom li i {
            font-size: 12px;
            color: #6b778c;
        }

        /* Date styling */
        .sales-item-center span[data-bs-toggle="tooltip"] {
            font-size: 10px;
            padding: 1px 4px;
            background: transparent;
            border: none;
            color: #5e6c84;
        }

        .sales-item-center .text-danger {
            color: #de350b !important;
            background: #ffebe6;
            border-radius: 2px;
            padding: 1px 4px;
            font-weight: 500;
        }

        /* Bottom section */
        .sales-item-bottom {
            padding: 4px 8px 6px;
            background: transparent;
        }

        .sales-item-bottom ul {
            gap: 4px !important;
        }

        /* User avatars - smaller */
        .user-group img {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            margin-left: -6px;
            border: 1.5px solid #fff;
        }

        .user-group img:first-child {
            margin-left: 0;
        }

        /* Menu button */
        .btn-group.card-option {
            opacity: 0;
            transition: opacity 85ms;
        }

        .sales-item:hover .btn-group.card-option {
            opacity: 1;
        }

        .btn-group.card-option button {
            padding: 2px 4px;
            font-size: 14px;
            color: #6b778c;
        }

        .btn-group.card-option button:hover {
            color: #172b4d;
            transform: none;
        }

        /* Priority badges - Jira style */
        .badge.bg-light-danger {
            background: #ffebe6 !important;
            color: #de350b !important;
            border: none;
        }

        .badge.bg-light-warning {
            background: #fff3cd !important;
            color: #974f0c !important;
            border: none;
        }

        .badge.bg-light-primary {
            background: #deebff !important;
            color: #0747a6 !important;
            border: none;
        }

        .badge.bg-light-info {
            background: #e6fcff !important;
            color: #008da6 !important;
            border: none;
        }

        /* Issue type badges - Jira colors */
        .badge.bg-purple {
            background: #6554c0 !important;
            box-shadow: none;
        }

        .badge.bg-success {
            background: #36b37e !important;
            box-shadow: none;
        }

        .badge.bg-primary {
            background: #0065ff !important;
            box-shadow: none;
        }

        .badge.bg-danger {
            background: #ff5630 !important;
            box-shadow: none;
        }

        .badge.bg-info {
            background: #00b8d9 !important;
            box-shadow: none;
        }

        /* Parent indicator */
        .sales-item-top .ti-arrow-badge-up {
            color: #6b778c;
            font-size: 12px;
        }

        /* Filter card - compact */
        .card {
            background: #fff;
            border: 1px solid #dfe1e6;
            box-shadow: none;
            border-radius: 3px;
        }

        .card .card-body {
            padding: 10px 12px;
        }

        .card .form-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #5e6c84;
            margin-bottom: 4px;
        }

        .card .form-control {
            font-size: 13px;
            padding: 6px 10px;
            border-radius: 3px;
            border: 1px solid #dfe1e6;
        }

        .card .form-control:focus {
            border-color: #4c9aff;
            box-shadow: 0 0 0 1px #4c9aff;
        }

        /* Dropdown menu */
        .dropdown-menu {
            font-size: 13px;
            border-radius: 3px;
            box-shadow: 0 4px 8px -2px rgba(9,30,66,.25), 0 0 1px rgba(9,30,66,.31);
            border: none;
        }

        .dropdown-item {
            padding: 6px 12px;
            font-size: 13px;
        }

        .dropdown-item i {
            font-size: 14px;
            margin-right: 6px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .kanban-wrapper > .col {
                flex: 0 0 240px;
                min-width: 240px;
            }
        }
    </style>
@endpush
@push('script-page')
    <script src="{{ asset('css/summernote/summernote-bs4.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/dragula.min.js') }}"></script>
    <script>
        ! function(a) {
            "use strict";
            var t = function() {
                this.$body = a("body")
            };
            t.prototype.init = function() {
                a('[data-plugin="dragula"]').each(function() {
                    var t = a(this).data("containers"),
                        n = [];
                    if (t)
                        for (var i = 0; i < t.length; i++) n.push(a("#" + t[i])[0]);
                    else n = [a(this)[0]];
                    var r = a(this).data("handleclass");
                    r ? dragula(n, {
                        moves: function(a, t, n) {
                            return n.classList.contains(r)
                        }
                    }) : dragula(n).on('drop', function(el, target, source, sibling) {
                        var sort = [];
                        $("#" + target.id + " > div").each(function() {
                            sort[$(this).index()] = $(this).attr('id');
                        });

                        var id = el.id;
                        var old_stage = $("#" + source.id).data('status');
                        var new_stage = $("#" + target.id).data('status');
                        var project_id = '{{ $project->id }}';

                        $("#" + source.id).parent().find('.count').text($("#" + source.id + " > div")
                            .length);
                        $("#" + target.id).parent().find('.count').text($("#" + target.id + " > div")
                            .length);
                        $.ajax({
                            url: '{{ route('tasks.update.order', [$project->id]) }}',
                            type: 'PATCH',
                            data: {
                                id: id,
                                sort: sort,
                                new_stage: new_stage,
                                old_stage: old_stage,
                                project_id: project_id,
                                "_token": "{{ csrf_token() }}"
                            },
                            success: function(data) {
                                show_toastr('success', "Task Moved Successfully.",'success');
                            },
                            error: function(data) {
                                data = data.responseJSON;
                                console.log(data);

                                show_toastr('error', "something went wrong. ", 'error');
                            }
                        });
                    });
                })
            }, a.Dragula = new t, a.Dragula.Constructor = t
        }(window.jQuery),
        function(a) {
            "use strict";
            a.Dragula.init()
        }(window.jQuery);

        $(document).ready(function() {
            /*Set assign_to Value*/
            $(document).on('click', '.add_usr', function() {
                var ids = [];
                $(this).toggleClass('selected');
                var crr_id = $(this).attr('data-id');
                $('#usr_txt_' + crr_id).html($('#usr_txt_' + crr_id).html() == 'Add' ?
                    '{{ __('Added') }}' : '{{ __('Add') }}');
                if ($('#usr_icon_' + crr_id).hasClass('fa-plus')) {
                    $('#usr_icon_' + crr_id).removeClass('fa-plus');
                    $('#usr_icon_' + crr_id).addClass('fa-check');
                } else {
                    $('#usr_icon_' + crr_id).removeClass('fa-check');
                    $('#usr_icon_' + crr_id).addClass('fa-plus');
                }
                $('.selected').each(function() {
                    ids.push($(this).attr('data-id'));
                });
                $('input[name="assign_to"]').val(ids);
            });

            $(document).on("click", ".del_task", function() {
                var id = $(this);
                $.ajax({
                    url: $(this).attr('data-url'),
                    type: 'DELETE',
                    dataType: 'JSON',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        $('#' + data.task_id).remove();
                        show_toastr('{{ __('success') }}',
                            '{{ __('Task Deleted Successfully!') }}');
                    },
                });
            });

            /*For Task Comment*/
            $(document).on('click', '#comment_submit', function(e) {
                var curr = $(this);

                var comment = $.trim($("#form-comment textarea[name='comment']").val());
                if (comment != '') {
                    $.ajax({
                        url: $("#form-comment").data('action'),
                        data: {
                            comment: comment,
                            "_token": "{{ csrf_token() }}"
                        },
                        type: 'POST',
                        success: function(data) {
                            data = JSON.parse(data);
                            var html = "<div class='list-group-item px-0 mb-1'>" +
                                "                    <div class='row align-items-center'>" +
                                "                        <div class='col-auto'>" +
                                "                            <a href='#' class='avatar avatar-sm  ms-2'>" +
                                "                                <img src=" + data.default_img +
                                " alt='' class='avatar-sm rounded border-2 border border-primary ml-3'>" +
                                "                            </a>" +
                                "                        </div>" +
                                "                        <div class='col ml-n2'>" +
                                "                            <p class='d-block h6 text-sm font-weight-light mb-0 text-break'>" +
                                data.comment + "</p>" +
                                "                            <small class='d-block'>" + data
                                .current_time + "</small>" +
                                "                           </div>" +
                                "                        <div class='col-auto'><div class='action-btn me-4'><a href='#' class='mx-3 btn btn-sm  align-items-center delete-comment bg-danger' data-url='" +
                                data.deleteUrl +
                                "'><i class='ti ti-trash text-white'></i></a></div></div>" +
                                "                    </div>" +
                                "                </div>";

                            $("#comments").prepend(html);
                            $("#form-comment textarea[name='comment']").val('');
                            load_task(curr.closest('.task-id').attr('id'));
                            show_toastr('{{ __('success') }}',
                                '{{ __('Comment Added Successfully!') }}');
                        },
                        error: function(data) {
                            show_toastr('error', '{{ __('Some Thing Is Wrong!') }}');
                        }
                    });
                } else {
                    show_toastr('error', '{{ __('Please write comment!') }}');
                }
            });
            $(document).on("click", ".delete-comment", function() {
                var btn = $(this);

                $.ajax({
                    url: $(this).attr('data-url'),
                    type: 'DELETE',
                    dataType: 'JSON',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        load_task(btn.closest('.task-id').attr('id'));
                        show_toastr('{{ __('success') }}',
                            '{{ __('Comment Deleted Successfully!') }}');
                        btn.closest('.list-group-item').remove();
                    },
                    error: function(data) {
                        data = data.responseJSON;
                        if (data.message) {
                            show_toastr('error', data.message);
                        } else {
                            show_toastr('error', '{{ __('Some Thing Is Wrong!') }}');
                        }
                    }
                });
            });

            /*For Task Checklist*/
            $(document).on('click', '#checklist_submit', function() {
                var name = $("#form-checklist input[name=name]").val();
                if (name != '') {
                    $.ajax({
                        url: $("#form-checklist").data('action'),
                        data: {
                            name: name,
                            "_token": "{{ csrf_token() }}"
                        },
                        type: 'POST',
                        success: function(data) {
                            data = JSON.parse(data);
                            load_task($('.task-id').attr('id'));
                            show_toastr('{{ __('success') }}',
                                '{{ __('Checklist Added Successfully!') }}');
                            var html =
                                '<div class="card border shadow-none checklist-member">' +
                                '                    <div class="px-3 py-2 row align-items-center">' +
                                '                        <div class="col">' +
                                '                            <div class="form-check form-check-inline">' +
                                '                                <input type="checkbox" class="form-check-input" id="check-item-' +
                                data.id + '" value="' + data.id + '" data-url="' + data
                                .updateUrl + '">' +
                                '                                <label class="form-check-label h6 text-sm" for="check-item-' +
                                data.id + '">' + data.name + '</label>' +
                                '                            </div>' +
                                '                        </div>' +
                                '                        <div class="col-auto"> <div class="action-btn ms-2">' +
                                '                            <a href="#" class="mx-3 btn btn-sm bg-danger align-items-center delete-checklist" role="button" data-url="' +
                                data.deleteUrl + '">' +
                                '                                <i class="ti ti-trash text-white"></i>' +
                                '                            </a>' +
                                '                        </div></div>' +
                                '                    </div>' +
                                '                </div>'

                            $("#checklist").append(html);
                            $("#form-checklist input[name=name]").val('');
                            $("#form-checklist").collapse('toggle');
                        },
                        error: function(data) {
                            data = data.responseJSON;
                            show_toastr('error', data.message);
                        }
                    });
                } else {
                    show_toastr('error', '{{ __('Please write checklist name!') }}');
                }
            });
            $(document).on("change", "#checklist input[type=checkbox]", function() {
                $.ajax({
                    url: $(this).attr('data-url'),
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        load_task($('.task-id').attr('id'));
                        show_toastr('{{ __('Success') }}',
                            '{{ __('Checklist Updated Successfully!') }}', 'success');
                    },
                    error: function(data) {
                        data = data.responseJSON;
                        if (data.message) {
                            show_toastr('error', data.message);
                        } else {
                            show_toastr('error', '{{ __('Some Thing Is Wrong!') }}');
                        }
                    }
                });
            });
            $(document).on("click", ".delete-checklist", function() {
                var btn = $(this);
                $.ajax({
                    url: $(this).attr('data-url'),
                    type: 'DELETE',
                    dataType: 'JSON',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        load_task($('.task-id').attr('id'));
                        show_toastr('{{ __('success') }}',
                            '{{ __('Checklist Deleted Successfully!') }}');
                        btn.closest('.checklist-member').remove();
                    },
                    error: function(data) {
                        data = data.responseJSON;
                        if (data.message) {
                            show_toastr('error', data.message);
                        } else {
                            show_toastr('error', '{{ __('Some Thing Is Wrong!') }}');
                        }
                    }
                });
            });

            /*For Task Attachment*/
            $(document).on('click', '#file_attachment_submit', function() {
                var file_data = $("#task_attachment").prop("files")[0];
                if (file_data != '' && file_data != undefined) {
                    var formData = new FormData();
                    formData.append('file', file_data);
                    formData.append('_token', "{{ csrf_token() }}");
                    $.ajax({
                        url: $("#file_attachment_submit").data('action'),
                        type: 'POST',
                        data: formData,
                        cache: false,
                        processData: false,
                        contentType: false,
                        success: function(data) {
                            $('#task_attachment').val('');
                            $('.attachment_text').html('{{ __('Choose a file…') }}');
                            data = JSON.parse(data);
                            load_task(data.task_id);
                            show_toastr('{{ __('success') }}',
                                '{{ __('File Added Successfully!') }}');

                            var delLink = '';
                            if (data.deleteUrl.length > 0) {
                                delLink =
                                    ' <div class="action-btn "><a href="#" class=" delete-comment-file mx-3 btn btn-sm  align-items-center  bg-danger" role="button" data-url="' +
                                    data.deleteUrl + '">' +
                                    '                                        <i class="ti ti-trash text-white"></i>' +
                                    '                                    </a></div>';
                            }

                            var html = '<div class="card mb-3 border shadow-none task-file">' +
                                '                    <div class="px-3 py-3">' +
                                '                        <div class="row align-items-center">' +
                                '                            <div class="col ml-n2">' +
                                '                                <h6 class="text-sm mb-0">' +
                                '                                    <a href="#">' + data.name +
                                '</a>' +
                                '                                </h6>' +
                                '                                <p class="card-text small text-muted">' +
                                data.file_size + '</p>' +
                                '                           </div>' +
                                '                            <div class="col-auto"> <div class="action-btn me-2">' +
                                '                                <a href="{{ asset(Storage::url('uploads/tasks')) }}/' +
                                data.file + '" download class="mx-3 btn btn-sm  align-items-center  bg-secondary" role="button">' +
                                '                                    <i class="ti ti-download text-white"></i>' +
                                '                                </a>' +
                                '                            </div>' +
                                delLink +
                                '</div>                        </div>' +
                                '                    </div>' +
                                '                </div>';

                            $("#comments-file").prepend(html);
                        },
                        error: function(data) {
                            data = data.responseJSON;
                            if (data.message) {
                                show_toastr('error', data.errors.file[0]);
                                $('#file-error').text(data.errors.file[0]).show();
                            } else {
                                show_toastr('error', '{{ __('Some Thing Is Wrong!') }}');
                            }
                        }
                    });
                } else {
                    show_toastr('error', '{{ __('Please select file!') }}');
                }
            });
            $(document).on("click", ".delete-comment-file", function() {
                var btn = $(this);
                $.ajax({
                    url: $(this).attr('data-url'),
                    type: 'DELETE',
                    dataType: 'JSON',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        load_task(btn.closest('.task-id').attr('id'));
                        show_toastr('{{ __('success') }}',
                            '{{ __('File Deleted Successfully!') }}');
                        btn.closest('.task-file').remove();
                    },
                    error: function(data) {
                        data = data.responseJSON;
                        if (data.message) {
                            show_toastr('error', data.message);
                        } else {
                            show_toastr('error', '{{ __('Some Thing Is Wrong!') }}');
                        }
                    }
                });
            });

            /*For Favorite*/
            $(document).on('click', '#add_favourite', function() {
                $.ajax({
                    url: $(this).attr('data-url'),
                    type: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        if (data.fav == 1) {
                            $('#add_favourite').addClass('action-favorite');
                        } else if (data.fav == 0) {
                            $('#add_favourite').removeClass('action-favorite');
                        }
                    }
                });
            });

            /*For Complete*/
            $(document).on('change', '#complete_task', function() {
                $.ajax({
                    url: $(this).attr('data-url'),
                    type: 'POST',
                    data: {
                        "_token": "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        if (data.com == 1) {
                            $("#complete_task").prop("checked", true);
                        } else if (data.com == 0) {
                            $("#complete_task").prop("checked", false);
                        }
                        $('#' + data.task).insertBefore($('#task-list-' + data.stage +
                            ' .empty-container'));
                        load_task(data.task);
                    }
                });
            });

            /*Progress Move*/
            $(document).on('change', '#task_progress', function() {
                var progress = $(this).val();
                $('#t_percentage').html(progress);
                $.ajax({
                    url: $(this).attr('data-url'),
                    data: {
                        progress: progress,
                        "_token": "{{ csrf_token() }}"
                    },
                    type: 'POST',
                    success: function(data) {
                        load_task(data.task_id);
                    }
                });
            });
        });

        function load_task(id) {
            $.ajax({
                url: "{{ route('projects.tasks.get', '_task_id') }}".replace('_task_id', id),
                dataType: 'html',
                data: {
                    "_token": "{{ csrf_token() }}"
                },
                success: function(data) {
                    $('#' + id).html('');
                    $('#' + id).html(data);
                }
            });
        }

        // Issue type filter
        $(document).on('change', '#issue_type_filter', function() {
            var selectedType = $(this).val();

            $('.sales-item').each(function() {
                if(selectedType === '') {
                    $(this).show();
                } else {
                    var taskIssueType = $(this).find('[data-issue-type-id]').attr('data-issue-type-id');
                    if(taskIssueType == selectedType) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                }
            });

            // Update counts
            $('.kanban-box').each(function() {
                var visibleCount = $(this).find('.sales-item:visible').length;
                $(this).closest('.crm-sales-card').find('.count').text(visibleCount);
            });
        });
    </script>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.index') }}">{{ __('Project') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('projects.show', $project->id) }}">
            {{ ucwords($project->project_name) }}</a></li>
    <li class="breadcrumb-item">{{ __('Task') }}</li>
@endsection
@section('action-btn')
    <div class="d-flex">
        @can('create project task')
            <a href="#" data-size="lg" data-url="{{ route('projects.tasks.create', $project->id) }}"
                data-ajax-popup="true" data-bs-toggle="tooltip"
                title="{{ __('Create Task') }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row mb-2">
        <div class="col-sm-12">
            <div class="card" style="margin-bottom: 8px;">
                <div class="card-body py-2 px-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <select class="form-control form-control-sm" id="issue_type_filter" style="width: 160px; font-size: 12px;">
                                <option value="">{{__('All Types')}}</option>
                                @foreach(\App\Models\IssueType::where('is_active', true)->orderBy('order')->get() as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="row kanban-wrapper horizontal-scroll-cards" data-containers='{{ json_encode($stageClass) }}'
                data-plugin="dragula">
                @foreach ($stages as $stage)
                    @php($tasks = $stage->tasks)
                    <div class="col">
                        <div class="crm-sales-card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h4>{{ $stage->name }}</h4>
                                <span class="count">{{ count($tasks) }}</span>
                            </div>
                            <div class="sales-item-wrp kanban-box" id="task-list-{{ $stage->id }}"
                                data-status="{{ $stage->id }}">
                                @foreach ($tasks as $taskDetail)
                                    <div class="sales-item draggable-item" id="{{ $taskDetail->id }}">
                                        <div class="sales-item-top">
                                            <div class="d-flex align-items-start justify-content-between">
                                                <h5 class="flex-1">
                                                    @if($taskDetail->parent)
                                                        <span class="me-1" data-bs-toggle="tooltip" title="{{ __('Parent: ') . $taskDetail->parent->issue_key }}">
                                                            <i class="ti ti-arrow-badge-up"></i>
                                                        </span>
                                                    @endif
                                                    <a href="#" class="dashboard-link"
                                                        data-url="{{ route('projects.tasks.show', [$project->id, $taskDetail->id]) }}"
                                                        data-ajax-popup="true" data-size="lg"
                                                        data-bs-original-title="{{ $taskDetail->name }}">{{ $taskDetail->name }}</a>
                                                </h5>
                                                <div class="btn-group card-option">
                                                    <button type="button" class="btn p-0 border-0"
                                                        data-bs-toggle="dropdown" aria-haspopup="true"
                                                        aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        @can('view project task')
                                                            <a href="#!" data-size="md"
                                                                data-url="{{ route('projects.tasks.show', [$project->id, $taskDetail->id]) }}"
                                                                data-ajax-popup="true" class="dropdown-item"
                                                                data-bs-original-title="{{ __('View') }}">
                                                                <i class="ti ti-eye"></i>{{ __('View') }}
                                                            </a>
                                                        @endcan
                                                        @can('edit project task')
                                                            <a href="#!" data-size="lg"
                                                                data-url="{{ route('projects.tasks.edit', [$project->id, $taskDetail->id]) }}"
                                                                data-ajax-popup="true" class="dropdown-item"
                                                                data-bs-original-title="{{ __('Edit') }}">
                                                                <i class="ti ti-pencil"></i>{{ __('Edit') }}
                                                            </a>
                                                        @endcan
                                                        @can('delete project task')
                                                            {!! Form::open(['method' => 'DELETE', 'route' => ['projects.tasks.destroy', [$project->id, $taskDetail->id]]]) !!}
                                                            <a href="#!" class="dropdown-item bs-pass-para">
                                                                <i class="ti ti-trash"></i>{{ __('Delete') }}
                                                            </a>
                                                            {!! Form::close() !!}
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="badge-wrp d-flex flex-wrap align-items-center gap-1 mt-1">
                                                @if($taskDetail->issue_key)
                                                    <span class="badge bg-secondary">{{ $taskDetail->issue_key }}</span>
                                                @endif
                                                @if($taskDetail->issueType)
                                                    <span class="badge bg-{{ $taskDetail->issueType->color }}" data-issue-type-id="{{ $taskDetail->issueType->id }}">
                                                        <i class="{{ $taskDetail->issueType->icon }}"></i> {{ $taskDetail->issueType->name }}
                                                    </span>
                                                @endif
                                                <span class="badge bg-light-{{ \App\Models\ProjectTask::$priority_color[$taskDetail->priority] }}">
                                                    {{ __(\App\Models\ProjectTask::$priority[$taskDetail->priority]) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="sales-item-center d-flex align-items-center justify-content-between">
                                            <ul class="d-flex flex-wrap align-items-center gap-1 p-0 m-0">
                                                @if($taskDetail->children && $taskDetail->children->count() > 0)
                                                    <li class="d-inline-flex align-items-center gap-1" data-bs-toggle="tooltip" title="{{ __('Sub-tasks') }}">
                                                        <i class="ti ti-subtask"></i>{{ $taskDetail->children->where('is_complete', 1)->count() }}/{{ $taskDetail->children->count() }}
                                                    </li>
                                                @endif
                                                @if(count($taskDetail->taskFiles) > 0)
                                                    <li class="d-inline-flex align-items-center gap-1" data-bs-toggle="tooltip" title="{{ __('Files') }}">
                                                        <i class="ti ti-file"></i>{{ count($taskDetail->taskFiles) }}
                                                    </li>
                                                @endif
                                                @if (str_replace('%', '', $taskDetail->taskProgress($taskDetail)['percentage']) > 0)
                                                    <li class="d-inline-flex align-items-center gap-1" data-bs-toggle="tooltip" title="{{ __('Progress') }}">
                                                        {{ $taskDetail->taskProgress($taskDetail)['percentage'] }}
                                                    </li>
                                                @endif
                                            </ul>
                                            @if (!empty($taskDetail->end_date) && $taskDetail->end_date != '0000-00-00')
                                                <span data-bs-toggle="tooltip" title="{{ __('Due') }}"
                                                    @if (strtotime($taskDetail->end_date) < time()) class="text-danger" @endif>{{ \Carbon\Carbon::parse($taskDetail->end_date)->format('M d') }}</span>
                                            @endif
                                        </div>
                                        <div class="sales-item-bottom d-flex align-items-center justify-content-between">
                                            <ul class="d-flex flex-wrap align-items-center gap-1 p-0 m-0">
                                                @if(count($taskDetail->comments) > 0)
                                                    <li class="d-inline-flex align-items-center gap-1" data-bs-toggle="tooltip" title="{{ __('Comments') }}">
                                                        <i class="ti ti-message"></i>{{ count($taskDetail->comments) }}
                                                    </li>
                                                @endif
                                                @php($checklistCount = $taskDetail->countTaskChecklist())
                                                @if($checklistCount != '0/0')
                                                    <li class="d-inline-flex align-items-center gap-1" data-bs-toggle="tooltip" title="{{ __('Checklist') }}">
                                                        <i class="ti ti-list"></i>{{ $checklistCount }}
                                                    </li>
                                                @endif
                                            </ul>
                                            <div class="user-group">
                                                @foreach ($taskDetail->users() as $user)
                                                    <img @if ($user->avatar) src="{{ asset('/storage/uploads/avatar/' . $user->avatar) }}" @else src="{{ asset('/storage/uploads/avatar/avatar.png') }}" @endif
                                                        alt="image" data-bs-toggle="tooltip"
                                                        title="{{ !empty($user) ? $user->name : '' }}">
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
