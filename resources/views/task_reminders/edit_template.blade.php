{{ Form::model($template, ['route' => ['task-reminders.update-template', $template->id], 'method' => 'POST']) }}
<div class="modal-body">
    <div class="form-group mb-3">
        {{ Form::label('name', __('Template Name'), ['class' => 'form-label']) }}
        {{ Form::text('name', null, ['class' => 'form-control', 'required' => true]) }}
    </div>

    <div class="form-group mb-3">
        {{ Form::label('message_template', __('Message Template'), ['class' => 'form-label']) }}
        {{ Form::textarea('message_template', null, ['class' => 'form-control', 'rows' => 8, 'required' => true, 'id' => 'templateTextarea']) }}
        <small class="text-muted">{{ __('Use variables like {Name}, {TaskCount}, {TaskNames}, etc.') }}</small>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Available Variables') }}</label>
        <div>
            @foreach(\App\Models\TaskReminderTemplate::$availableVariables as $var => $desc)
                <span class="badge bg-light text-dark me-1 mb-1" style="cursor: pointer;" onclick="insertVariable('{{ $var }}')" title="{{ $desc }}">
                    {{ $var }}
                </span>
            @endforeach
        </div>
    </div>

    <div class="form-group mb-3">
        <div class="form-check">
            {{ Form::checkbox('is_active', 1, null, ['class' => 'form-check-input', 'id' => 'is_active']) }}
            {{ Form::label('is_active', __('Active'), ['class' => 'form-check-label']) }}
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Preview') }}</label>
        <div id="templatePreview" class="p-3 bg-light rounded" style="white-space: pre-wrap; min-height: 100px;"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="refreshPreview">
            <i class="ti ti-refresh"></i> {{ __('Refresh Preview') }}
        </button>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('Save Template') }}</button>
</div>
{{ Form::close() }}

<script>
function insertVariable(variable) {
    var textarea = document.getElementById('templateTextarea');
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = textarea.value;
    textarea.value = text.substring(0, start) + variable + text.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start + variable.length, start + variable.length);
    updatePreview();
}

function updatePreview() {
    var template = $('#templateTextarea').val();

    // Sample replacements for preview
    var preview = template
        .replace(/\{Name\}/g, 'John Doe')
        .replace(/\{FirstName\}/g, 'John')
        .replace(/\{Date\}/g, new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }))
        .replace(/\{Day\}/g, new Date().toLocaleDateString('en-US', { weekday: 'long' }))
        .replace(/\{TaskCount\}/g, '3')
        .replace(/\{TaskNames\}/g, '• Fix login bug\n• Update dashboard UI\n• Write unit tests')
        .replace(/\{Time\}/g, new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }));

    $('#templatePreview').text(preview);
}

$(document).ready(function() {
    updatePreview();

    $('#templateTextarea').on('input', function() {
        updatePreview();
    });

    $('#refreshPreview').on('click', function() {
        updatePreview();
    });
});
</script>
