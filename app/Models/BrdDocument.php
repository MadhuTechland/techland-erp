<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrdDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'project_name',
        'project_description',
        'file_path',
        'original_name',
        'extracted_text',
        'team_data',
        'milestone_data',
        'parsed_data',
        'status',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'team_data' => 'array',
        'milestone_data' => 'array',
        'parsed_data' => 'array',
    ];

    const STATUS_UPLOADED = 'uploaded';
    const STATUS_TEAM_SETUP = 'team_setup';
    const STATUS_MILESTONES_SETUP = 'milestones_setup';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PARSED = 'parsed';
    const STATUS_GENERATED = 'generated';
    const STATUS_FAILED = 'failed';

    /**
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if can proceed to team setup
     */
    public function canSetupTeam()
    {
        return in_array($this->status, [self::STATUS_UPLOADED, self::STATUS_TEAM_SETUP]);
    }

    /**
     * Check if can proceed to milestone setup
     */
    public function canSetupMilestones()
    {
        return in_array($this->status, [self::STATUS_TEAM_SETUP, self::STATUS_MILESTONES_SETUP]);
    }

    /**
     * Check if can generate backlog
     */
    public function canGenerate()
    {
        return in_array($this->status, [self::STATUS_MILESTONES_SETUP, self::STATUS_PARSED, self::STATUS_FAILED]);
    }

    /**
     * Check if generation is complete
     */
    public function isGenerated()
    {
        return $this->status === self::STATUS_GENERATED;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        $labels = [
            self::STATUS_UPLOADED => 'Document Uploaded',
            self::STATUS_TEAM_SETUP => 'Team Configured',
            self::STATUS_MILESTONES_SETUP => 'Milestones Set',
            self::STATUS_PROCESSING => 'AI Processing...',
            self::STATUS_PARSED => 'Backlog Ready',
            self::STATUS_GENERATED => 'Tasks Created',
            self::STATUS_FAILED => 'Failed',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get current wizard step
     */
    public function getCurrentStep()
    {
        switch ($this->status) {
            case self::STATUS_UPLOADED:
                return 2; // Team setup
            case self::STATUS_TEAM_SETUP:
                return 3; // Milestones
            case self::STATUS_MILESTONES_SETUP:
            case self::STATUS_PROCESSING:
            case self::STATUS_PARSED:
            case self::STATUS_FAILED:
                return 4; // Review
            case self::STATUS_GENERATED:
                return 5; // Complete
            default:
                return 1;
        }
    }

    /**
     * Format team data for AI prompt
     */
    public function getTeamPromptString()
    {
        if (empty($this->team_data)) {
            return "No team members specified.";
        }

        $lines = [];
        foreach ($this->team_data as $member) {
            $skills = is_array($member['skills'] ?? []) ? implode(', ', $member['skills']) : ($member['skills'] ?? '');
            $name = $member['name'] ?? 'Unknown';
            $exp = !empty($member['experience']) ? " ({$member['experience']})" : '';
            $role = !empty($member['role']) ? " - {$member['role']}" : '';
            $lines[] = "- {$name}: {$skills}{$exp}{$role}";
        }

        return implode("\n", $lines);
    }

    /**
     * Format milestone data for AI prompt
     */
    public function getMilestonePromptString()
    {
        if (empty($this->milestone_data)) {
            return "No milestones specified.";
        }

        $lines = [];
        foreach ($this->milestone_data as $milestone) {
            $name = $milestone['name'] ?? 'Unnamed';
            $deadline = !empty($milestone['deadline']) ? " (Due: {$milestone['deadline']})" : '';
            $desc = !empty($milestone['description']) ? " - {$milestone['description']}" : '';
            $lines[] = "- {$name}{$deadline}{$desc}";
        }

        return implode("\n", $lines);
    }

    /**
     * Get summary stats from parsed data
     */
    public function getBacklogStats()
    {
        if (empty($this->parsed_data)) {
            return [
                'epics' => 0,
                'stories' => 0,
                'tasks' => 0,
                'total_hours' => 0,
            ];
        }

        $stats = [
            'epics' => 0,
            'stories' => 0,
            'tasks' => 0,
            'total_hours' => 0,
        ];

        $epics = $this->parsed_data['epics'] ?? [];
        $stats['epics'] = count($epics);

        foreach ($epics as $epic) {
            $stories = $epic['stories'] ?? [];
            $stats['stories'] += count($stories);

            foreach ($stories as $story) {
                $stats['total_hours'] += $story['estimated_hrs'] ?? 0;
                $tasks = $story['tasks'] ?? [];
                $stats['tasks'] += count($tasks);
            }
        }

        return $stats;
    }
}
