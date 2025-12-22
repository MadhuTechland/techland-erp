@extends('layouts.admin')
@section('page-title')
    {{ ucwords($project->project_name) . __("'s Tasks") }}
@endsection

@push('css-page')
    <link rel="stylesheet" href="{{ asset('css/summernote/summernote-bs4.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/dragula.min.css') }}" id="main-style-link">
    <style>
        /* ===== ELEGANT KANBAN BOARD ===== */

        /* Board container */
        .kanban-wrapper {
            padding: 0;
            display: flex;
            gap: 16px;
            overflow-x: auto;
            align-items: flex-start;
            padding-bottom: 20px;
        }

        .kanban-wrapper > .col {
            flex: 0 0 300px;
            max-width: 300px;
            min-width: 300px;
            padding: 0;
        }

        /* Column styling with gradient headers */
        .crm-sales-card {
            background: #f8fafc;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .crm-sales-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            padding: 16px 20px;
            min-height: auto;
        }

        /* Different gradient colors for each column */
        .kanban-wrapper > .col:nth-child(1) .crm-sales-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .kanban-wrapper > .col:nth-child(2) .crm-sales-card .card-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .kanban-wrapper > .col:nth-child(3) .crm-sales-card .card-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .kanban-wrapper > .col:nth-child(4) .crm-sales-card .card-header {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .kanban-wrapper > .col:nth-child(5) .crm-sales-card .card-header {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .kanban-wrapper > .col:nth-child(6) .crm-sales-card .card-header {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        .kanban-wrapper > .col:nth-child(7) .crm-sales-card .card-header {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
        .kanban-wrapper > .col:nth-child(8) .crm-sales-card .card-header {
            background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
        }

        .crm-sales-card .card-header h4 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: #fff;
            letter-spacing: 0.5px;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Task count badge */
        .count {
            background: rgba(255,255,255,0.25);
            padding: 4px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            border-radius: 20px;
            backdrop-filter: blur(4px);
        }

        /* Kanban box / task container */
        .kanban-box {
            min-height: 100px;
            padding: 12px;
            max-height: calc(100vh - 320px);
            overflow-y: auto;
            background: #f8fafc;
        }

        .kanban-box::-webkit-scrollbar {
            width: 6px;
        }

        .kanban-box::-webkit-scrollbar-track {
            background: transparent;
        }

        .kanban-box::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .kanban-box::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ===== TASK CARDS - ELEGANT STYLE ===== */
        .sales-item {
            background: #fff;
            border-radius: 12px;
            margin-bottom: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s ease;
            overflow: hidden;
        }

        .sales-item::before {
            content: '';
            display: block;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        /* Priority-based top border colors */
        .sales-item[data-priority="0"]::before,
        .sales-item:has(.bg-light-info)::before {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
        }
        .sales-item[data-priority="1"]::before,
        .sales-item:has(.bg-light-warning)::before {
            background: linear-gradient(90deg, #ffc107 0%, #fd7e14 100%);
        }
        .sales-item[data-priority="2"]::before,
        .sales-item:has(.bg-light-danger)::before {
            background: linear-gradient(90deg, #dc3545 0%, #e83e8c 100%);
        }

        .sales-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }

        /* Dragging states */
        .gu-mirror {
            cursor: grabbing !important;
            opacity: 1;
            transform: rotate(3deg);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2) !important;
        }

        .gu-transit {
            opacity: 0.3;
        }

        .gu-over {
            background: #f0f9ff !important;
            border: 2px dashed #3b82f6 !important;
        }

        /* Card sections */
        .sales-item-top {
            padding: 12px 14px 8px;
            border-bottom: none !important;
        }

        .sales-item-top h5 {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
            margin: 0 0 8px 0;
            color: #1e293b;
        }

        .sales-item-top h5 a {
            color: #1e293b;
            text-decoration: none;
            transition: color 0.15s;
        }

        .sales-item-top h5 a:hover {
            color: #6366f1;
        }

        /* Badge container */
        .badge-wrp {
            margin-top: 6px;
        }

        .badge-wrp .badge {
            font-size: 10px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 6px;
            text-transform: none;
            letter-spacing: 0;
        }

        /* Issue key badge */
        .badge-wrp .badge.bg-secondary {
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%) !important;
            color: #475569 !important;
            box-shadow: none;
            font-size: 10px;
            font-weight: 600;
        }

        /* Center section */
        .sales-item-center {
            padding: 8px 14px;
            border-bottom: none !important;
        }

        .sales-item-center ul {
            margin: 0;
            padding: 0;
            gap: 8px !important;
        }

        .sales-item-center li,
        .sales-item-bottom li {
            font-size: 11px;
            padding: 3px 6px;
            border-radius: 6px;
            background: #f1f5f9;
            border: none;
            color: #64748b;
        }

        .sales-item-center li i,
        .sales-item-bottom li i {
            font-size: 12px;
            color: #64748b;
        }

        /* Date styling */
        .sales-item-center span[data-bs-toggle="tooltip"] {
            font-size: 11px;
            padding: 3px 8px;
            background: #f1f5f9;
            border: none;
            color: #64748b;
            border-radius: 6px;
        }

        .sales-item-center .text-danger {
            color: #fff !important;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 6px;
            padding: 3px 8px;
            font-weight: 600;
        }

        /* Bottom section */
        .sales-item-bottom {
            padding: 8px 14px 12px;
            background: #fafbfc;
            border-top: 1px solid #f1f5f9;
        }

        .sales-item-bottom ul {
            gap: 8px !important;
        }

        /* User avatars */
        .user-group img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            margin-left: -8px;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .user-group img:first-child {
            margin-left: 0;
        }

        /* Menu button */
        .btn-group.card-option {
            opacity: 0;
            transition: opacity 0.15s;
        }

        .sales-item:hover .btn-group.card-option {
            opacity: 1;
        }

        .btn-group.card-option button {
            padding: 4px 6px;
            font-size: 16px;
            color: #94a3b8;
            background: #f1f5f9;
            border-radius: 6px;
        }

        .btn-group.card-option button:hover {
            color: #475569;
            background: #e2e8f0;
            transform: none;
        }

        /* Priority badges - Elegant style */
        .badge.bg-light-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
            color: #dc2626 !important;
            border: none;
        }

        .badge.bg-light-warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
            color: #d97706 !important;
            border: none;
        }

        .badge.bg-light-primary {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
            color: #2563eb !important;
            border: none;
        }

        .badge.bg-light-info {
            background: linear-gradient(135deg, #cffafe 0%, #a5f3fc 100%) !important;
            color: #0891b2 !important;
            border: none;
        }

        /* Issue type badges - Vibrant colors */
        .badge.bg-purple {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
            box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3);
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }

        .badge.bg-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }

        .badge.bg-info {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important;
            box-shadow: 0 2px 4px rgba(6, 182, 212, 0.3);
        }

        .badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: #fff !important;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }

        /* Parent indicator */
        .sales-item-top .ti-arrow-badge-up {
            color: #8b5cf6;
            font-size: 14px;
        }

        /* Filter card - Elegant */
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border-radius: 12px;
        }

        .card .card-body {
            padding: 12px 16px;
        }

        .card .form-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 6px;
        }

        .card .form-control {
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.15s;
        }

        .card .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        /* Dropdown menu */
        .dropdown-menu {
            font-size: 13px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            padding: 8px;
        }

        .dropdown-item {
            padding: 8px 12px;
            font-size: 13px;
            border-radius: 8px;
            transition: all 0.15s;
        }

        .dropdown-item:hover {
            background: #f1f5f9;
        }

        .dropdown-item i {
            font-size: 15px;
            margin-right: 8px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .kanban-wrapper > .col {
                flex: 0 0 280px;
                min-width: 280px;
            }
        }

        /* Empty state */
        .kanban-box:empty::after {
            content: 'No tasks';
            display: block;
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
            font-size: 13px;
            font-style: italic;
        }

        /* ===== FILTER BAR ===== */
        .filter-bar {
            background: #fff;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: none;
        }

        .filter-bar .form-control,
        .filter-bar .form-select {
            font-size: 13px;
            padding: 8px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            height: 40px;
            transition: all 0.2s;
        }

        .filter-bar .form-control:focus,
        .filter-bar .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .filter-bar label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        /* Search Input */
        .search-box {
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }

        .search-box input {
            padding-left: 42px;
            background: #f9fafb;
        }

        .search-box input:focus {
            background: #fff;
        }

        /* Quick Filters */
        .quick-filters {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .quick-filter-btn {
            font-size: 12px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 20px;
            border: 2px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-filter-btn:hover {
            border-color: #667eea;
            color: #667eea;
            background: #f5f3ff;
        }

        .quick-filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .filter-bar {
                padding: 12px;
            }

            .quick-filters {
                gap: 6px;
            }

            .quick-filter-btn {
                padding: 6px 10px;
                font-size: 11px;
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

        // ===== FILTER FUNCTIONALITY =====
        function filterTasks() {
            var search = $('#task_search').val().toLowerCase();
            var issueType = $('#issue_type_filter').val();
            var user = $('#filter_user').val();
            var priority = $('#filter_priority').val();
            var quickFilter = $('.quick-filter-btn.active').data('filter');
            var today = new Date().toISOString().split('T')[0];
            var currentUserId = '{{ Auth::user()->id }}';

            $('.sales-item').each(function() {
                var $card = $(this);
                var taskTitle = $card.find('.sales-item-top h5 a').text().toLowerCase();
                var taskIssueType = $card.data('issue-type') ? $card.data('issue-type').toString() : '';
                var taskUserIds = $card.data('user-ids') ? $card.data('user-ids').toString() : '';
                var taskPriority = $card.data('priority') ? $card.data('priority').toString() : '';
                var taskEndDate = $card.data('end-date') ? $card.data('end-date').toString() : '';
                var isOverdue = taskEndDate && taskEndDate !== '0000-00-00' && taskEndDate < today;

                var show = true;

                // Search filter
                if (search && taskTitle.indexOf(search) === -1) {
                    show = false;
                }

                // Issue Type filter
                if (issueType && taskIssueType !== issueType) {
                    show = false;
                }

                // User filter
                if (user && taskUserIds.indexOf(user) === -1) {
                    show = false;
                }

                // Priority filter
                if (priority && taskPriority !== priority) {
                    show = false;
                }

                // Quick filters
                if (quickFilter === 'my_tasks') {
                    if (taskUserIds.indexOf(currentUserId) === -1) {
                        show = false;
                    }
                } else if (quickFilter === 'overdue' && !isOverdue) {
                    show = false;
                }

                $card.toggle(show);
            });

            // Update counts
            updateTaskCounts();
        }

        // Update task counts in column headers
        function updateTaskCounts() {
            $('.kanban-box').each(function() {
                var visibleCount = $(this).find('.sales-item:visible').length;
                $(this).closest('.crm-sales-card').find('.count').text(visibleCount);
            });
        }

        // Event listeners for filters
        $('#task_search').on('keyup', filterTasks);
        $('#issue_type_filter, #filter_user, #filter_priority').on('change', filterTasks);

        // Quick filter buttons
        $('.quick-filter-btn').on('click', function() {
            $('.quick-filter-btn').removeClass('active');
            $(this).addClass('active');
            filterTasks();
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
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <div class="search-box">
                    <i class="ti ti-search"></i>
                    <input type="text" class="form-control" id="task_search" placeholder="{{__('Search tasks...')}}">
                </div>
            </div>
            <div class="col-md-2">
                <label>{{__('Issue Type')}}</label>
                <select class="form-select" id="issue_type_filter">
                    <option value="">{{__('All Types')}}</option>
                    @foreach(\App\Models\IssueType::where('is_active', true)->orderBy('order')->get() as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>{{__('Assignee')}}</label>
                <select class="form-select" id="filter_user">
                    <option value="">{{__('All Users')}}</option>
                    @foreach($project->users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label>{{__('Priority')}}</label>
                <select class="form-select" id="filter_priority">
                    <option value="">{{__('All Priorities')}}</option>
                    @foreach(\App\Models\ProjectTask::$priority as $key => $val)
                        <option value="{{ $key }}">{{ __($val) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <div class="quick-filters">
                    <button class="quick-filter-btn active" data-filter="all">{{__('All')}}</button>
                    <button class="quick-filter-btn" data-filter="my_tasks">{{__('My Tasks')}}</button>
                    <button class="quick-filter-btn" data-filter="overdue">{{__('Overdue')}}</button>
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
                                    <div class="sales-item draggable-item" id="{{ $taskDetail->id }}" data-priority="{{ $taskDetail->priority }}" data-user-ids="{{ $taskDetail->assign_to }}" data-issue-type="{{ $taskDetail->issue_type_id }}" data-end-date="{{ $taskDetail->end_date }}">
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
