<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bug extends Model
{
    protected $fillable = [
        'bug_id',
        'project_id',
        'sprint_id',
        'milestone_id',
        'title',
        'priority',
        'severity',
        'story_points',
        'backlog_order',
        'start_date',
        'due_date',
        'resolved_at',
        'resolution_time_hours',
        'resolution_type',
        'resolved_by',
        'related_task_id',
        'description',
        'status',
        'assign_to',
        'created_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'story_points' => 'decimal:1',
    ];

    public static $priority = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    public static $severity = [
        'trivial' => 'Trivial',
        'minor' => 'Minor',
        'major' => 'Major',
        'critical' => 'Critical',
    ];

    public static $severityColors = [
        'trivial' => 'secondary',
        'minor' => 'info',
        'major' => 'warning',
        'critical' => 'danger',
    ];

    public static $resolutionTypes = [
        'fixed' => 'Fixed',
        'wont_fix' => "Won't Fix",
        'duplicate' => 'Duplicate',
        'cannot_reproduce' => 'Cannot Reproduce',
        'by_design' => 'By Design',
    ];

    public function bug_status()
    {
        return $this->hasOne('App\Models\BugStatus', 'id', 'status');
    }

    public function assignTo()
    {
        return $this->hasOne('App\Models\User', 'id', 'assign_to');
    }

    public function createdBy()
    {
        return $this->hasOne('App\Models\User', 'id', 'created_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo('App\Models\User', 'resolved_by');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\BugComment', 'bug_id', 'id')->orderBy('id', 'DESC');
    }

    public function bugFiles()
    {
        return $this->hasMany('App\Models\BugFile', 'bug_id', 'id')->orderBy('id', 'DESC');
    }

    public function project()
    {
        return $this->hasOne('App\Models\Project', 'id', 'project_id');
    }

    public function sprint()
    {
        return $this->belongsTo('App\Models\Sprint', 'sprint_id');
    }

    public function milestone()
    {
        return $this->belongsTo('App\Models\Milestone', 'milestone_id');
    }

    public function relatedTask()
    {
        return $this->belongsTo('App\Models\ProjectTask', 'related_task_id');
    }

    public function users()
    {
        return User::whereIn('id', explode(',', $this->assign_to))->get();
    }

    // Scopes
    public function scopeInBacklog($query)
    {
        return $query->whereNull('sprint_id');
    }

    public function scopeInSprint($query, $sprintId)
    {
        return $query->where('sprint_id', $sprintId);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    // Performance tracking methods
    public function markResolved($resolutionType, $resolvedBy = null)
    {
        $this->resolved_at = now();
        $this->resolution_type = $resolutionType;
        $this->resolved_by = $resolvedBy ?? auth()->id();

        // Calculate resolution time in hours
        if ($this->created_at) {
            $this->resolution_time_hours = $this->created_at->diffInHours($this->resolved_at);
        }

        $this->save();
    }

    public function getSeverityBadgeAttribute()
    {
        $color = self::$severityColors[$this->severity] ?? 'secondary';
        $label = self::$severity[$this->severity] ?? $this->severity;
        return "<span class=\"badge bg-{$color}\">{$label}</span>";
    }

    public function getIsResolvedAttribute()
    {
        return !is_null($this->resolved_at);
    }
}
