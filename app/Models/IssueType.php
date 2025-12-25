<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'icon',
        'color',
        'description',
        'is_active',
        'is_subtask',
        'is_container',
        'order',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_subtask' => 'boolean',
        'is_container' => 'boolean',
    ];

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'issue_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotSubtask($query)
    {
        return $query->where('is_subtask', false);
    }

    /**
     * Scope to get only container types (Epic, Story)
     * Container types aggregate time from children instead of having their own time
     */
    public function scopeContainers($query)
    {
        return $query->where('is_container', true);
    }

    /**
     * Scope to get only work item types (Task, Bug, Sub-task)
     * Work items have actual estimated hours that count toward project total
     */
    public function scopeWorkItems($query)
    {
        return $query->where('is_container', false);
    }

    /**
     * Check if this issue type is a container (Epic/Story)
     */
    public function isContainer(): bool
    {
        return $this->is_container;
    }

    /**
     * Check if this issue type is a work item (Task/Bug/Sub-task)
     */
    public function isWorkItem(): bool
    {
        return !$this->is_container;
    }
}
