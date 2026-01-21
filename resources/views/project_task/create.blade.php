{{ Form::open(['route' => ['projects.tasks.store',$project_id],'id' => 'create_task' , 'class'=>'needs-validation', 'novalidate']) }}
<div class="modal-body">
    {{-- start for ai module--}}
    @php
        $settings = \App\Models\Utility::settings();
    @endphp
    @if($settings['ai_chatgpt_enable'] == 'on')
        <div class="text-end">
            <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['project task']) }}"
               data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{__('Generate with AI')}}</span>
            </a>
        </div>
    @endif
    {{-- end for ai module--}}
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('name', __('Task name'),['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('name', null, ['class' => 'form-control','required'=>'required','placeholder'=>__('Enter Task name')]) }}
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {{ Form::label('issue_type_id', __('Issue Type'),['class' => 'form-label']) }}<x-required></x-required>
                <select class="form-control select" name="issue_type_id" id="issue_type_id" required>
                    <option value="">{{__('Select Issue Type')}}</option>
                    @foreach($issue_types as $type)
                        <option value="{{ $type->id }}" data-icon="{{ $type->icon }}" data-color="{{ $type->color }}">
                            {{ __($type->name) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-6" id="parent_task_container" style="display: none;">
            <div class="form-group">
                {{ Form::label('parent_id', __('Parent Epic/Story'),['class' => 'form-label']) }}
                <select class="form-control searchable-select" name="parent_id" id="parent_id">
                    <option value="">{{__('None (Top Level)')}}</option>
                    @foreach($parent_tasks as $parent)
                        <option value="{{ $parent->id }}" data-type="{{ $parent->issue_type_id }}">
                            {{ $parent->issue_key }} - {{ $parent->name }}
                            @if($parent->issueType)
                                ({{ $parent->issueType->name }})
                            @endif
                        </option>
                    @endforeach
                </select>
                <div class="text-xs mt-1 text-muted">
                    {{ __('Select parent Epic or Story for this item') }}
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {{ Form::label('milestone_id', __('Milestone'),['class' => 'form-label']) }}
                <select class="form-control searchable-select" name="milestone_id" id="milestone_id">
                    <option value="0" class="text-muted">{{__('Select Milestone')}}</option>
                    @foreach($project->milestones as $m_val)
                        <option value="{{ $m_val->id }}">{{ $m_val->title }}</option>
                    @endforeach
                </select>
                <div class="text-xs mt-1">
                    {{ __('Create milestone here.') }} <a href="{{ route('projects.show', $project_id) }}"><b>{{ __('Create milestone') }}</b></a>
                </div>
            </div>
        </div>
        <div class="col-6">
            {{ Form::label('stage_id', __('Stage'),['class'=>'form-label']) }}<x-required></x-required>
            {{ Form::select('stage_id', $stages,null, array('class' => 'form-control select','required'=>'required')) }}
            <div class="text-xs mt-1">
                {{ __('Create task stage.') }} <a href="{{ route('project-task-stages.index') }}"><b>{{ __('Create task stage') }}</b></a>
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('description', __('Description'),['class' => 'form-label']) }}
                <small class="form-text text-muted mb-2 mt-0">{{__('This textarea will autosize while you type')}}</small>
                {{ Form::textarea('description', null, ['class' => 'form-control','rows'=>'1','data-toggle' => 'autosize','placeholder'=>__('Enter Description')]) }}
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {{ Form::label('estimated_hrs', __('Estimated Hours'),['class' => 'form-label']) }}<x-required></x-required>
                <small class="form-text text-muted mb-2 mt-0">{{__('allocated total ').$hrs['allocated'].__(' hrs in other tasks')}}</small>
                {{ Form::number('estimated_hrs', null, ['class' => 'form-control','required' => 'required','min'=>'0','maxlength' => '8','placeholder'=>__('Enter Estimated Hours')]) }}
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {{ Form::label('priority', __('Priority'),['class' => 'form-label']) }}
                <small class="form-text text-muted mb-2 mt-0">{{__('Set Priority of your task')}}</small>
                <select class="form-control select" name="priority" id="priority" required>
                    @foreach(\App\Models\ProjectTask::$priority as $key => $val)
                        <option value="{{ $key }}">{{ __($val) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {{ Form::label('start_date', __('Start Date'),['class' => 'form-label']) }}
                {{ Form::date('start_date', null, ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                {{ Form::label('end_date', __('End Date'),['class' => 'form-label']) }}
                {{ Form::date('end_date', null, ['class' => 'form-control']) }}
            </div>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">{{__('Task members')}}</label>
        <small class="form-text text-muted mb-2 mt-0">{{__('Click to select team members for this task')}}</small>
    </div>
    <style>
        .member-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .member-card {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
            position: relative;
        }
        .member-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        .member-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f3ff 0%, #e8ecff 100%);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .member-card.selected::after {
            content: '\eb28';
            font-family: 'tabler-icons';
            position: absolute;
            top: 8px;
            right: 8px;
            width: 22px;
            height: 22px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }
        .member-card .member-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .member-card.selected .member-avatar {
            border-color: #667eea;
        }
        .member-card .member-info {
            flex: 1;
            min-width: 0;
        }
        .member-card .member-name {
            font-weight: 600;
            font-size: 14px;
            color: #2d3748;
            margin: 0 0 2px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .member-card .member-email {
            font-size: 12px;
            color: #718096;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .selected-count {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 10px;
        }
        .no-members-msg {
            text-align: center;
            padding: 30px;
            color: #718096;
            font-style: italic;
        }
    </style>
    <div class="member-selection-grid">
        @php
            $uniqueUsers = $project->users->unique('id');
        @endphp
        @forelse($uniqueUsers as $user)
            <div class="member-card" data-user-id="{{ $user->id }}">
                <img class="member-avatar"
                     src="{{ $user->avatar ? asset('/storage/uploads/avatar/'.$user->avatar) : asset('/storage/uploads/avatar/avatar.png') }}"
                     alt="{{ $user->name }}">
                <div class="member-info">
                    <p class="member-name" title="{{ $user->name }}">{{ $user->name }}</p>
                    <p class="member-email" title="{{ $user->email }}">{{ $user->email }}</p>
                </div>
            </div>
        @empty
            <div class="no-members-msg col-12">
                <i class="ti ti-users-minus" style="font-size: 32px; opacity: 0.5;"></i>
                <p class="mt-2 mb-0">{{__('No team members assigned to this project yet.')}}</p>
            </div>
        @endforelse
    </div>
    {{ Form::hidden('assign_to', null, ['id' => 'assign_to_input']) }}
    @if(isset($settings['google_calendar_enable']) && $settings['google_calendar_enable'] == 'on')
        <div class="form-group col-md-6">
            {{Form::label('synchronize_type',__('Synchronize in Google Calendar ?'),array('class'=>'form-label')) }}
            <div class="form-switch">
                <input type="checkbox" class="form-check-input mt-2" name="synchronize_type" id="switch-shadow" value="google_calender">
                <label class="form-check-label" for="switch-shadow"></label>
            </div>
        </div>
    @endif
</div>
<div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Create')}}" class="btn btn-primary">
</div>
{{Form::close()}}

<script>
$(document).ready(function() {
    // Initialize Select2 for searchable dropdowns
    if ($.fn.select2) {
        $('.searchable-select').select2({
            dropdownParent: $('.modal-body'),
            placeholder: '{{ __("Search...") }}',
            allowClear: true,
            width: '100%'
        });
    }

    // Member selection handling
    var selectedMembers = [];

    function updateAssignToInput() {
        $('#assign_to_input').val(selectedMembers.join(','));
    }

    // Click handler for member cards
    $(document).on('click', '.member-card', function() {
        var userId = $(this).data('user-id').toString();
        var index = selectedMembers.indexOf(userId);

        if (index > -1) {
            // Already selected - remove
            selectedMembers.splice(index, 1);
            $(this).removeClass('selected');
        } else {
            // Not selected - add
            selectedMembers.push(userId);
            $(this).addClass('selected');
        }

        updateAssignToInput();
    });

    // Check URL parameters for parent and type (coming from "Add Sub-task" button)
    const urlParams = new URLSearchParams(window.location.search);
    const parentId = urlParams.get('parent');
    const issueType = urlParams.get('type');

    if (parentId && issueType === 'subtask') {
        // Select Sub-task issue type
        $('#issue_type_id option').each(function() {
            if ($(this).text().trim() === 'Sub-task') {
                $(this).prop('selected', true);
            }
        });
        // Show and select parent
        $('#parent_task_container').show();
        $('#parent_id').val(parentId);
        // Trigger change to filter options
        $('#issue_type_id').trigger('change');
    }

    // Show/hide parent selector based on issue type
    $('#issue_type_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var selectedText = selectedOption.text().trim();

        // Show parent selector for Story, Task, Bug, Sub-task (not for Epic)
        if(selectedText === 'Story' || selectedText === 'Task' || selectedText === 'Bug' || selectedText === 'Sub-task') {
            $('#parent_task_container').show();

            // Filter parent options based on hierarchy rules
            if(selectedText === 'Story') {
                // Stories can be under Epics
                $('#parent_id option').each(function() {
                    var optionText = $(this).text();
                    if(optionText.includes('(Epic)') || optionText === 'None (Top Level)') {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else if(selectedText === 'Sub-task') {
                // Sub-tasks can be under Stories, Epics, or Tasks
                $('#parent_id option').each(function() {
                    var optionText = $(this).text();
                    if(optionText.includes('(Story)') || optionText.includes('(Epic)') || optionText.includes('(Task)') || optionText === 'None (Top Level)') {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            } else if(selectedText === 'Task' || selectedText === 'Bug') {
                // Tasks/Bugs can be under Stories or Epics (not under other tasks)
                $('#parent_id option').each(function() {
                    var optionText = $(this).text();
                    if(optionText.includes('(Story)') || optionText.includes('(Epic)') || optionText === 'None (Top Level)') {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        } else {
            // Epic or nothing selected - hide parent selector
            $('#parent_task_container').hide();
            $('#parent_id').val('');
        }
    });
});
</script>
