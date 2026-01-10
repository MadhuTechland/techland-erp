@extends('layouts.admin')

@section('page-title')
    {{ __('Team Setup - BRD Parser') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('brd.index') }}">{{ __('BRD Parser') }}</a></li>
    <li class="breadcrumb-item">{{ __('Team Setup') }}</li>
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

    .team-member-card {
        background: #f9fafb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
        border: 1px solid #e5e7eb;
    }

    .team-member-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }

    .skill-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .skill-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #6366f1;
        color: white;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 500;
    }

    .skill-tag .remove-skill {
        cursor: pointer;
        opacity: 0.8;
    }

    .skill-tag .remove-skill:hover {
        opacity: 1;
    }

    .skill-input-wrapper {
        position: relative;
    }

    .skill-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
        z-index: 100;
        display: none;
    }

    .skill-suggestions.show {
        display: block;
    }

    .skill-suggestion-item {
        padding: 10px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
    }

    .skill-suggestion-item:hover {
        background: #f9fafb;
    }

    .skill-suggestion-item:last-child {
        border-bottom: none;
    }

    .skill-category {
        font-size: 11px;
        color: #6b7280;
        text-transform: uppercase;
    }

    .add-member-btn {
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        color: #6b7280;
    }

    .add-member-btn:hover {
        border-color: #6366f1;
        color: #6366f1;
        background: rgba(99, 102, 241, 0.05);
    }

    .experience-options {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .experience-option {
        padding: 8px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        cursor: pointer;
        font-size: 13px;
        transition: all 0.2s;
    }

    .experience-option:hover,
    .experience-option.selected {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.1);
        color: #6366f1;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="wizard-container">
            <div class="wizard-header">
                <h1 class="h4 mb-1">Configure Your Team</h1>
                <p class="mb-0 opacity-75">Add team members and their skills for intelligent task assignment</p>
            </div>

            <div class="wizard-steps">
                <div class="wizard-step completed">
                    <div class="step-number"><i class="ti ti-check"></i></div>
                    <div class="step-label">Upload BRD</div>
                </div>
                <div class="wizard-step active">
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
                <form action="{{ route('brd.team.save', $brd->id) }}" method="POST" id="teamForm">
                    @csrf

                    <div id="teamMembers">
                        <!-- Team member cards will be added here -->
                    </div>

                    <div class="add-member-btn" id="addMemberBtn">
                        <i class="ti ti-plus me-2"></i> Add Team Member
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('brd.index') }}" class="btn btn-light btn-lg">
                            <i class="ti ti-arrow-left me-2"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            Continue to Milestones <i class="ti ti-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Team Member Template -->
<template id="memberTemplate">
    <div class="team-member-card" data-index="INDEX">
        <div class="team-member-header">
            <h6 class="mb-0">Team Member</h6>
            <button type="button" class="btn btn-sm btn-light-danger remove-member">
                <i class="ti ti-trash"></i>
            </button>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Select Employee <span class="text-danger">*</span></label>
                <select name="team[INDEX][user_id]" class="form-select employee-select" required>
                    <option value="">Choose employee...</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->email }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Role/Responsibility</label>
                <input type="text" name="team[INDEX][role]" class="form-control" placeholder="e.g., Backend Lead, Mobile Developer">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Skills <span class="text-danger">*</span></label>
            <div class="skill-input-wrapper">
                <input type="text" class="form-control skill-input" placeholder="Type to search skills...">
                <div class="skill-suggestions"></div>
            </div>
            <div class="skill-tags" data-index="INDEX"></div>
            <input type="hidden" name="team[INDEX][skills][]" class="skills-hidden">
        </div>

        <div class="mb-0">
            <label class="form-label">Experience Level <span class="text-danger">*</span></label>
            <div class="experience-options">
                <div class="experience-option" data-value="Junior (0-2 years)">Junior (0-2 years)</div>
                <div class="experience-option" data-value="Mid-level (2-5 years)">Mid-level (2-5 years)</div>
                <div class="experience-option" data-value="Senior (5-8 years)">Senior (5-8 years)</div>
                <div class="experience-option" data-value="Lead (8+ years)">Lead (8+ years)</div>
            </div>
            <input type="hidden" name="team[INDEX][experience]" class="experience-hidden" required>
        </div>
    </div>
</template>
@endsection

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const teamMembers = document.getElementById('teamMembers');
    const addMemberBtn = document.getElementById('addMemberBtn');
    const memberTemplate = document.getElementById('memberTemplate');
    let memberIndex = 0;

    const allSkills = @json($allSkills);

    // Add first member automatically
    addMember();

    addMemberBtn.addEventListener('click', addMember);

    function addMember() {
        const html = memberTemplate.innerHTML.replace(/INDEX/g, memberIndex);
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const card = wrapper.firstElementChild;

        teamMembers.appendChild(card);

        // Setup event listeners
        setupMemberCard(card, memberIndex);

        memberIndex++;
    }

    function setupMemberCard(card, index) {
        // Remove button
        card.querySelector('.remove-member').addEventListener('click', function() {
            if (teamMembers.children.length > 1) {
                card.remove();
            } else {
                alert('At least one team member is required');
            }
        });

        // Skill input
        const skillInput = card.querySelector('.skill-input');
        const suggestions = card.querySelector('.skill-suggestions');
        const skillTags = card.querySelector('.skill-tags');
        const selectedSkills = [];

        skillInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            if (query.length < 1) {
                suggestions.classList.remove('show');
                return;
            }

            const filtered = allSkills.filter(s =>
                s.name.toLowerCase().includes(query) &&
                !selectedSkills.includes(s.name)
            ).slice(0, 8);

            if (filtered.length === 0) {
                suggestions.innerHTML = `<div class="skill-suggestion-item" data-skill="${this.value}" data-category="Custom">
                    <div>${this.value}</div>
                    <div class="skill-category">Add custom skill</div>
                </div>`;
            } else {
                suggestions.innerHTML = filtered.map(s =>
                    `<div class="skill-suggestion-item" data-skill="${s.name}" data-category="${s.category}">
                        <div>${s.name}</div>
                        <div class="skill-category">${s.category}</div>
                    </div>`
                ).join('');
            }

            suggestions.classList.add('show');
        });

        skillInput.addEventListener('blur', function() {
            setTimeout(() => suggestions.classList.remove('show'), 200);
        });

        suggestions.addEventListener('click', function(e) {
            const item = e.target.closest('.skill-suggestion-item');
            if (item) {
                const skill = item.dataset.skill;
                addSkill(skill, skillTags, selectedSkills, index);
                skillInput.value = '';
                suggestions.classList.remove('show');
            }
        });

        skillInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim()) {
                    addSkill(this.value.trim(), skillTags, selectedSkills, index);
                    this.value = '';
                    suggestions.classList.remove('show');
                }
            }
        });

        // Experience options
        const expOptions = card.querySelectorAll('.experience-option');
        const expHidden = card.querySelector('.experience-hidden');

        expOptions.forEach(opt => {
            opt.addEventListener('click', function() {
                expOptions.forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                expHidden.value = this.dataset.value;
            });
        });
    }

    function addSkill(skill, container, selectedSkills, index) {
        if (selectedSkills.includes(skill)) return;

        selectedSkills.push(skill);

        const tag = document.createElement('span');
        tag.className = 'skill-tag';
        tag.innerHTML = `
            ${skill}
            <span class="remove-skill"><i class="ti ti-x"></i></span>
            <input type="hidden" name="team[${index}][skills][]" value="${skill}">
        `;

        tag.querySelector('.remove-skill').addEventListener('click', function() {
            const idx = selectedSkills.indexOf(skill);
            if (idx > -1) selectedSkills.splice(idx, 1);
            tag.remove();
        });

        container.appendChild(tag);
    }

    // Load existing team data if any
    @if($brd->team_data)
    const existingTeam = @json($brd->team_data);
    if (existingTeam.length > 0) {
        // Remove the first empty member we added
        teamMembers.innerHTML = '';
        memberIndex = 0;

        existingTeam.forEach((member, idx) => {
            addMember();
            const card = teamMembers.lastElementChild;

            // Set employee
            card.querySelector('.employee-select').value = member.user_id;

            // Set role
            card.querySelector('input[name*="[role]"]').value = member.role || '';

            // Set skills
            const skillTags = card.querySelector('.skill-tags');
            const selectedSkills = [];
            (member.skills || []).forEach(skill => {
                addSkill(skill, skillTags, selectedSkills, idx);
            });

            // Set experience
            const expHidden = card.querySelector('.experience-hidden');
            expHidden.value = member.experience || '';
            const expOptions = card.querySelectorAll('.experience-option');
            expOptions.forEach(opt => {
                if (opt.dataset.value === member.experience) {
                    opt.classList.add('selected');
                }
            });
        });
    }
    @endif
});
</script>
@endpush
