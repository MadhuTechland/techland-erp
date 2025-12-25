<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'status',
        'description',
    ];

    public function tasks()
    {
        return $this->hasMany('App\Models\ProjectTask', 'milestone_id', 'id');
    }

    /**
     * Get all work items in this milestone (excludes containers like Epic/Story)
     * These are the actual tasks with estimated hours
     */
    public function workItems()
    {
        $workItemTypeIds = IssueType::where('is_container', false)->pluck('id')->toArray();

        return $this->hasMany('App\Models\ProjectTask', 'milestone_id', 'id')
            ->where(function($query) use ($workItemTypeIds) {
                $query->whereIn('issue_type_id', $workItemTypeIds)
                      ->orWhereNull('issue_type_id');
            });
    }

    /**
     * Get container items (Epic/Story) in this milestone
     */
    public function containers()
    {
        $containerTypeIds = IssueType::where('is_container', true)->pluck('id')->toArray();

        return $this->hasMany('App\Models\ProjectTask', 'milestone_id', 'id')
            ->whereIn('issue_type_id', $containerTypeIds);
    }

    /**
     * Get total estimated hours for this milestone
     * Only counts work items (Task, Bug, Sub-task), not containers
     */
    public function getTotalEstimatedHrs(): float
    {
        return floatval($this->workItems()->sum('estimated_hrs') ?? 0);
    }

    /**
     * Get progress for this milestone based on work items
     */
    public function getWorkItemProgress(): array
    {
        $workItems = $this->workItems()->get();
        $total = $workItems->count();
        $completed = $workItems->where('is_complete', 1)->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
        ];
    }

    /**
     * Get tasks grouped by parent (for hierarchical display)
     * Returns top-level items (containers and orphan work items)
     */
    public function getHierarchicalTasks()
    {
        // Get all tasks in this milestone that have no parent or parent is not in this milestone
        return $this->tasks()
            ->where(function($query) {
                $query->whereNull('parent_id')
                      ->orWhereHas('parent', function($q) {
                          $q->where('milestone_id', '!=', $this->id);
                      });
            })
            ->with(['issueType', 'children.issueType'])
            ->orderBy('order')
            ->get();
    }
}
