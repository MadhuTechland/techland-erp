<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTeamSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'skills',
        'experience',
        'role',
        'created_by',
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    /**
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user (team member)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get skills as comma-separated string
     */
    public function getSkillsStringAttribute()
    {
        return is_array($this->skills) ? implode(', ', $this->skills) : '';
    }

    /**
     * Format team member info for AI prompt
     */
    public function formatForAiPrompt()
    {
        $skills = is_array($this->skills) ? implode(', ', $this->skills) : $this->skills;
        $name = $this->user ? $this->user->name : 'Unknown';
        $role = $this->role ? " - {$this->role}" : '';
        $exp = $this->experience ? " ({$this->experience})" : '';

        return "- {$name}: {$skills}{$exp}{$role}";
    }
}
