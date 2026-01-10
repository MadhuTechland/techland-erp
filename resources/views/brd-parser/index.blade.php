@extends('layouts.admin')

@section('page-title')
    {{ __('BRD Parser - AI Backlog Generator') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('BRD Parser') }}</li>
@endsection

@push('css-page')
<style>
    .wizard-container {
        background: var(--bs-card-bg, #fff);
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .wizard-header {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        padding: 30px;
        color: white;
    }

    .wizard-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .wizard-subtitle {
        opacity: 0.9;
        font-size: 14px;
    }

    .wizard-steps {
        display: flex;
        justify-content: center;
        padding: 20px;
        background: rgba(0, 0, 0, 0.02);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .wizard-step {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 24px;
        position: relative;
    }

    .wizard-step:not(:last-child)::after {
        content: '';
        position: absolute;
        right: -20px;
        width: 40px;
        height: 2px;
        background: #e5e7eb;
        top: 50%;
    }

    .step-number {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        color: #6b7280;
    }

    .wizard-step.active .step-number {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
    }

    .wizard-step.completed .step-number {
        background: #22c55e;
        color: white;
    }

    .step-label {
        font-weight: 500;
        color: #6b7280;
    }

    .wizard-step.active .step-label {
        color: #1f2937;
        font-weight: 600;
    }

    .wizard-body {
        padding: 40px;
    }

    .upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 16px;
        padding: 60px 40px;
        text-align: center;
        background: #fafafa;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-zone:hover,
    .upload-zone.dragover {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.05);
    }

    .upload-zone.has-file {
        border-color: #22c55e;
        background: rgba(34, 197, 94, 0.05);
    }

    .upload-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: white;
        font-size: 32px;
    }

    .upload-text {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
    }

    .upload-hint {
        color: #6b7280;
        font-size: 14px;
    }

    .file-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: white;
        border-radius: 12px;
        margin-top: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .file-icon {
        width: 48px;
        height: 48px;
        background: #ef4444;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 20px;
    }

    .file-details {
        flex: 1;
    }

    .file-name {
        font-weight: 600;
        color: #1f2937;
    }

    .file-size {
        font-size: 12px;
        color: #6b7280;
    }

    .form-section {
        margin-top: 30px;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 16px;
        color: #1f2937;
    }

    .recent-docs {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    .recent-doc-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        background: #f9fafb;
        border-radius: 12px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }

    .recent-doc-item:hover {
        background: #f3f4f6;
    }

    .doc-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .doc-status.uploaded { background: #dbeafe; color: #1d4ed8; }
    .doc-status.team_setup { background: #fef3c7; color: #b45309; }
    .doc-status.milestones_setup { background: #ddd6fe; color: #7c3aed; }
    .doc-status.processing { background: #fce7f3; color: #be185d; }
    .doc-status.parsed { background: #d1fae5; color: #059669; }
    .doc-status.generated { background: #22c55e; color: white; }
    .doc-status.failed { background: #fee2e2; color: #dc2626; }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="wizard-container">
            <div class="wizard-header">
                <h1 class="wizard-title">AI-Powered BRD Parser</h1>
                <p class="wizard-subtitle">Upload your Business Requirements Document and let AI generate your product backlog automatically</p>
            </div>

            <div class="wizard-steps">
                <div class="wizard-step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Upload BRD</div>
                </div>
                <div class="wizard-step">
                    <div class="step-number">2</div>
                    <div class="step-label">Team Setup</div>
                </div>
                <div class="wizard-step">
                    <div class="step-number">3</div>
                    <div class="step-label">Milestones</div>
                </div>
                <div class="wizard-step">
                    <div class="step-number">4</div>
                    <div class="step-label">Generate</div>
                </div>
            </div>

            <div class="wizard-body">
                <form action="{{ route('brd.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf

                    <div class="upload-zone" id="uploadZone">
                        <input type="file" name="brd_file" id="brdFile" accept=".pdf" style="display: none;">
                        <div class="upload-icon">
                            <i class="ti ti-file-upload"></i>
                        </div>
                        <div class="upload-text">Drop your BRD document here</div>
                        <div class="upload-hint">or click to browse (PDF only, max 10MB)</div>

                        <div class="file-info" id="fileInfo" style="display: none;">
                            <div class="file-icon">
                                <i class="ti ti-file-type-pdf"></i>
                            </div>
                            <div class="file-details">
                                <div class="file-name" id="fileName"></div>
                                <div class="file-size" id="fileSize"></div>
                            </div>
                            <button type="button" class="btn btn-sm btn-light-danger" id="removeFile">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Alternative: Paste BRD Text -->
                    <div class="form-section">
                        <div class="form-section-title d-flex align-items-center gap-2">
                            <span>Or Paste BRD Content</span>
                            <small class="text-muted">(if PDF extraction fails)</small>
                        </div>
                        <textarea name="brd_text" id="brdText" class="form-control" rows="6"
                                  placeholder="Paste your BRD document text here as an alternative to uploading PDF..."></textarea>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">Project Information</div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Project Name <span class="text-danger">*</span></label>
                                    <input type="text" name="project_name" class="form-control" required placeholder="Enter project name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Link to Existing Project</label>
                                    <select name="existing_project_id" class="form-select">
                                        <option value="">Create New Project</option>
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Project Description</label>
                                    <textarea name="project_description" class="form-control" rows="3" placeholder="Brief description of the project"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                            Continue to Team Setup <i class="ti ti-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>

                @if($brdDocuments->count() > 0)
                <div class="recent-docs">
                    <h5 class="mb-3">Recent Documents</h5>
                    @foreach($brdDocuments as $doc)
                    <div class="recent-doc-item">
                        <div class="file-icon">
                            <i class="ti ti-file-type-pdf"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $doc->project_name ?? $doc->original_name }}</div>
                            <div class="text-muted small">{{ $doc->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="doc-status {{ $doc->status }}">{{ $doc->status_label }}</span>
                        @if($doc->status !== 'generated')
                        <a href="{{ route('brd.' . ($doc->getCurrentStep() == 2 ? 'team' : ($doc->getCurrentStep() == 3 ? 'milestones' : 'review')), $doc->id) }}" class="btn btn-sm btn-primary">
                            Continue
                        </a>
                        @else
                        <a href="{{ route('projects.show', $doc->project_id) }}" class="btn btn-sm btn-success">
                            View Project
                        </a>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('brdFile');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const removeFile = document.getElementById('removeFile');
    const submitBtn = document.getElementById('submitBtn');
    const brdText = document.getElementById('brdText');

    // Check if can submit (file OR text)
    function checkCanSubmit() {
        const hasFile = fileInput.files && fileInput.files.length > 0;
        const hasText = brdText.value.trim().length > 50;
        submitBtn.disabled = !(hasFile || hasText);
    }

    // Text input change
    brdText.addEventListener('input', checkCanSubmit);

    // Click to upload
    uploadZone.addEventListener('click', function(e) {
        if (e.target.closest('#removeFile')) return;
        fileInput.click();
    });

    // Drag and drop
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadZone.classList.add('dragover');
    });

    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
    });

    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });

    // File input change
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFile(this.files[0]);
        }
    });

    // Remove file
    removeFile.addEventListener('click', function(e) {
        e.stopPropagation();
        fileInput.value = '';
        fileInfo.style.display = 'none';
        uploadZone.classList.remove('has-file');
        checkCanSubmit();
    });

    function handleFile(file) {
        if (file.type !== 'application/pdf') {
            alert('Please upload a PDF file');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            alert('File size must be less than 10MB');
            return;
        }

        // Create a new FileList-like object
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.style.display = 'flex';
        uploadZone.classList.add('has-file');
        checkCanSubmit();
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }
});
</script>
@endpush
