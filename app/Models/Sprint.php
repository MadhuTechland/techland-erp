<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Sprint extends Model
{
    protected $fillable = [
        'name',
        'goal',
        'project_id',
        'start_date',
        'end_date',
        'status',
        'order',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public const STATUS_PLANNING = 'planning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public static $statuses = [
        'planning' => 'Planning',
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public static $statusColors = [
        'planning' => 'secondary',
        'active' => 'primary',
        'completed' => 'success',
        'cancelled' => 'danger',
    ];

    // Relationships
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'sprint_id');
    }

    public function bugs()
    {
        return $this->hasMany(Bug::class, 'sprint_id');
    }

    public function workItems()
    {
        $workItemTypeIds = IssueType::where('is_container', false)->pluck('id')->toArray();

        return $this->hasMany(ProjectTask::class, 'sprint_id')
            ->where(function ($query) use ($workItemTypeIds) {
                $query->whereIn('issue_type_id', $workItemTypeIds)
                    ->orWhereNull('issue_type_id');
            });
    }

    public function burndownData()
    {
        return $this->hasMany(SprintBurndown::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePlanning($query)
    {
        return $query->where('status', self::STATUS_PLANNING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    // Computed Properties
    public function getTotalStoryPointsAttribute()
    {
        $taskPoints = $this->workItems()->sum('story_points') ?? 0;
        $bugPoints = $this->bugs()->sum('story_points') ?? 0;
        return $taskPoints + $bugPoints;
    }

    public function getCompletedStoryPointsAttribute()
    {
        $taskPoints = $this->workItems()->where('is_complete', 1)->sum('story_points') ?? 0;
        $bugPoints = $this->bugs()->whereNotNull('resolved_at')->sum('story_points') ?? 0;
        return $taskPoints + $bugPoints;
    }

    public function getRemainingStoryPointsAttribute()
    {
        return $this->total_story_points - $this->completed_story_points;
    }

    public function getTotalTasksAttribute()
    {
        return $this->workItems()->count() + $this->bugs()->count();
    }

    public function getCompletedTasksAttribute()
    {
        $completedTasks = $this->workItems()->where('is_complete', 1)->count();
        $resolvedBugs = $this->bugs()->whereNotNull('resolved_at')->count();
        return $completedTasks + $resolvedBugs;
    }

    public function getTotalBugsAttribute()
    {
        return $this->bugs()->count();
    }

    public function getResolvedBugsAttribute()
    {
        return $this->bugs()->whereNotNull('resolved_at')->count();
    }

    public function getProgressPercentageAttribute()
    {
        $total = $this->total_story_points;
        if ($total == 0) {
            $total = $this->total_tasks;
            $completed = $this->completed_tasks;
        } else {
            $completed = $this->completed_story_points;
        }

        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }

    public function getDaysRemainingAttribute()
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return 0;
        }
        return max(0, Carbon::now()->diffInDays($this->end_date, false));
    }

    public function getDurationAttribute()
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPlanning(): bool
    {
        return $this->status === self::STATUS_PLANNING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canStart(): bool
    {
        return $this->status === self::STATUS_PLANNING;
    }

    public function canComplete(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    // Calculate burndown chart data
    public function getBurndownChartData(): array
    {
        $totalPoints = $this->total_story_points;
        $duration = $this->duration;
        $idealBurndown = [];
        $actualBurndown = [];
        $dates = [];

        // Generate dates array
        $current = $this->start_date->copy();
        $endDate = $this->status === self::STATUS_ACTIVE ? Carbon::today() : $this->end_date;

        while ($current->lte($this->end_date)) {
            $dates[] = $current->format('M d');
            $current->addDay();
        }

        // Ideal burndown (linear)
        $dailyBurn = $duration > 1 ? $totalPoints / ($duration - 1) : $totalPoints;
        for ($i = 0; $i < count($dates); $i++) {
            $idealBurndown[] = round(max(0, $totalPoints - ($dailyBurn * $i)), 1);
        }

        // Get actual burndown from history
        $history = $this->burndownData()->orderBy('date')->get();
        $actualData = array_fill(0, count($dates), null);

        foreach ($history as $record) {
            $index = $this->start_date->diffInDays($record->date);
            if ($index >= 0 && $index < count($dates)) {
                $actualData[$index] = (float) $record->remaining_points;
            }
        }

        return [
            'dates' => $dates,
            'ideal' => $idealBurndown,
            'actual' => $actualData,
            'totalPoints' => $totalPoints,
            'completedPoints' => $this->completed_story_points,
            'remainingPoints' => $this->remaining_story_points,
        ];
    }

    // Record daily burndown snapshot
    public function recordBurndownSnapshot(): void
    {
        SprintBurndown::updateOrCreate(
            [
                'sprint_id' => $this->id,
                'date' => Carbon::today(),
            ],
            [
                'total_points' => $this->total_story_points,
                'completed_points' => $this->completed_story_points,
                'remaining_points' => $this->remaining_story_points,
                'total_tasks' => $this->total_tasks,
                'completed_tasks' => $this->completed_tasks,
            ]
        );
    }
}
