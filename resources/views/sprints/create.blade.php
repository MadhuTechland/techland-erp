{{ Form::open(['route' => ['sprints.store', $project->id], 'method' => 'post', 'class' => 'needs-validation', 'novalidate']) }}
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('name', __('Sprint Name'), ['class' => 'form-label']) }} <span class="text-danger">*</span>
                {{ Form::text('name', $suggestedName ?? '', ['class' => 'form-control', 'required' => 'required', 'placeholder' => __('e.g., Sprint 1')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('start_date', __('Start Date'), ['class' => 'form-label']) }} <span class="text-danger">*</span>
                {{ Form::date('start_date', $suggestedStart->format('Y-m-d') ?? now()->format('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('end_date', __('End Date'), ['class' => 'form-label']) }} <span class="text-danger">*</span>
                {{ Form::date('end_date', $suggestedEnd->format('Y-m-d') ?? now()->addDays(13)->format('Y-m-d'), ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('goal', __('Sprint Goal'), ['class' => 'form-label']) }}
                {{ Form::textarea('goal', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('What is the main objective of this sprint?')]) }}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    {{ Form::submit(__('Create Sprint'), ['class' => 'btn btn-primary']) }}
</div>
{{ Form::close() }}
