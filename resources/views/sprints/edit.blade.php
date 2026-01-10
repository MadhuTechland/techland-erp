{{ Form::open(['route' => ['sprints.update', $project->id, $sprint->id], 'method' => 'put', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('name', __('Sprint Name'), ['class' => 'form-label']) }} <span class="text-danger">*</span>
                {{ Form::text('name', $sprint->name, ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }} <span class="text-danger">*</span>
                {{ Form::date('start_date', $sprint->start_date->format('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }} <span class="text-danger">*</span>
                {{ Form::date('end_date', $sprint->end_date->format('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('goal', __('Sprint Goal'), ['class' => 'form-label']) }}
                {{ Form::textarea('goal', $sprint->goal, ['class' => 'form-control', 'rows' => 3]) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                <label class="form-label">{{ __('Status') }}</label>
                <p class="form-control-plaintext">
                    <span class="badge bg-{{ \App\Models\Sprint::$statusColors[$sprint->status] }}">
                        {{ \App\Models\Sprint::$statuses[$sprint->status] }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    @if($sprint->status == 'planning')
        <button type="button" class="btn btn-danger me-auto" onclick="deleteSprint({{ $sprint->id }})">
            <i class="ti ti-trash"></i> {{ __('Delete') }}
        </button>
    @endif
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    {{ Form::submit(__('Update Sprint'), ['class' => 'btn btn-primary']) }}
</div>
{{ Form::close() }}

<script>
function deleteSprint(sprintId) {
    if (confirm('{{ __("Are you sure you want to delete this sprint? Tasks will be moved back to backlog.") }}')) {
        $.ajax({
            url: '/projects/{{ $project->id }}/sprints/' + sprintId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.error || 'Failed to delete sprint');
            }
        });
    }
}
</script>
