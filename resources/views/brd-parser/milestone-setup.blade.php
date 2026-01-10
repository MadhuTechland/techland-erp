@extends('layouts.admin')

@section('page-title')
    {{ __('Milestones - BRD Parser') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('brd.index') }}">{{ __('BRD Parser') }}</a></li>
    <li class="breadcrumb-item">{{ __('Milestones') }}</li>
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

    .wizard-body {
        padding: 40px;
    }

    .milestone-card {
        background: #f9fafb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        border: 1px solid #e5e7eb;
        position: relative;
    }

    .milestone-number {
        position: absolute;
        top: -12px;
        left: 20px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 13px;
    }

    .milestone-header {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 16px;
    }

    .add-milestone-btn {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        color: #6b7280;
    }

    .add-milestone-btn:hover {
        border-color: #6366f1;
        color: #6366f1;
        background: rgba(99, 102, 241, 0.05);
    }

    .team-summary {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .team-member-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: white;
        border-radius: 20px;
        margin: 4px;
        font-size: 13px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .team-member-chip .avatar {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #6366f1;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="wizard-container">
            <div class="wizard-header">
                <h1 class="h4 mb-1">Define Milestones</h1>
                <p class="mb-0 opacity-75">Set up project phases and deadlines for AI to distribute tasks</p>
            </div>

            <div class="wizard-steps">
                <div class="wizard-step completed">
                    <div class="step-number"><i class="ti ti-check"></i></div>
                    <div class="step-label">Upload BRD</div>
                </div>
                <div class="wizard-step completed">
                    <div class="step-number"><i class="ti ti-check"></i></div>
                    <div class="step-label">Team Setup</div>
                </div>
                <div class="wizard-step active">
                    <div class="step-number">3</div>
                    <div class="step-label">Milestones</div>
                </div>
                <div class="wizard-step">
                    <div class="step-number">4</div>
                    <div class="step-label">Generate</div>
                </div>
            </div>

            <div class="wizard-body">
                <!-- Team Summary -->
                <div class="team-summary">
                    <h6 class="mb-3"><i class="ti ti-users me-2"></i>Team Members ({{ count($brd->team_data ?? []) }})</h6>
                    <div>
                        @foreach($brd->team_data ?? [] as $member)
                        <span class="team-member-chip">
                            <span class="avatar">{{ substr($member['name'] ?? 'U', 0, 1) }}</span>
                            {{ $member['name'] ?? 'Unknown' }}
                            <small class="text-muted">{{ $member['role'] ?? '' }}</small>
                        </span>
                        @endforeach
                    </div>
                </div>

                <form action="{{ route('brd.milestones.save', $brd->id) }}" method="POST" id="milestoneForm">
                    @csrf

                    <div id="milestones">
                        <!-- Milestone cards will be added here -->
                    </div>

                    <div class="add-milestone-btn" id="addMilestoneBtn">
                        <i class="ti ti-plus me-2"></i> Add Milestone
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('brd.team', $brd->id) }}" class="btn btn-light btn-lg">
                            <i class="ti ti-arrow-left me-2"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            Generate Backlog <i class="ti ti-sparkles ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Milestone Template -->
<template id="milestoneTemplate">
    <div class="milestone-card" data-index="INDEX">
        <div class="milestone-number">INDEX_PLUS</div>
        <div class="milestone-header">
            <button type="button" class="btn btn-sm btn-light-danger remove-milestone">
                <i class="ti ti-trash"></i>
            </button>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Milestone Name <span class="text-danger">*</span></label>
                <input type="text" name="milestones[INDEX][name]" class="form-control" required
                       placeholder="e.g., Phase 1 - Core Features">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Deadline <span class="text-danger">*</span></label>
                <input type="date" name="milestones[INDEX][deadline]" class="form-control" required
                       min="{{ date('Y-m-d') }}">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="milestones[INDEX][description]" class="form-control" rows="2"
                          placeholder="Brief description of this milestone's goals"></textarea>
            </div>
        </div>
    </div>
</template>
@endsection

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const milestones = document.getElementById('milestones');
    const addMilestoneBtn = document.getElementById('addMilestoneBtn');
    const milestoneTemplate = document.getElementById('milestoneTemplate');
    let milestoneIndex = 0;

    // Add first milestone automatically
    addMilestone();

    addMilestoneBtn.addEventListener('click', addMilestone);

    function addMilestone() {
        const html = milestoneTemplate.innerHTML
            .replace(/INDEX/g, milestoneIndex)
            .replace(/INDEX_PLUS/g, milestoneIndex + 1);

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const card = wrapper.firstElementChild;

        milestones.appendChild(card);

        // Setup remove button
        card.querySelector('.remove-milestone').addEventListener('click', function() {
            if (milestones.children.length > 1) {
                card.remove();
                updateMilestoneNumbers();
            } else {
                alert('At least one milestone is required');
            }
        });

        // Set default deadline (2 weeks from now for first, +2 weeks for each subsequent)
        const deadlineInput = card.querySelector('input[type="date"]');
        const defaultDate = new Date();
        defaultDate.setDate(defaultDate.getDate() + (14 * (milestoneIndex + 1)));
        deadlineInput.value = defaultDate.toISOString().split('T')[0];

        milestoneIndex++;
    }

    function updateMilestoneNumbers() {
        const cards = milestones.querySelectorAll('.milestone-card');
        cards.forEach((card, idx) => {
            card.querySelector('.milestone-number').textContent = idx + 1;
        });
    }

    // Load existing milestone data if any
    @if($brd->milestone_data)
    const existingMilestones = @json($brd->milestone_data);
    if (existingMilestones.length > 0) {
        milestones.innerHTML = '';
        milestoneIndex = 0;

        existingMilestones.forEach((milestone, idx) => {
            addMilestone();
            const card = milestones.lastElementChild;

            card.querySelector('input[name*="[name]"]').value = milestone.name || '';
            card.querySelector('input[name*="[deadline]"]').value = milestone.deadline || '';
            card.querySelector('textarea[name*="[description]"]').value = milestone.description || '';
        });
    }
    @endif
});
</script>
@endpush
