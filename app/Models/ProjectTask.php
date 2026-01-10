<?php

namespace App\Models;

use App\Models\User;
use App\Models\Utility;
use App\Models\TaskFile;
use App\Models\ActivityLog;
use App\Models\TaskComment;
use App\Models\TaskChecklist;
use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    protected $fillable = [
        'name',
        'description',
        'estimated_hrs',
        'start_date',
        'end_date',
        'priority',
        'priority_color',
        'assign_to',
        'project_id',
        'milestone_id',
        'sprint_id',
        'stage_id',
        'order',
        'backlog_order',
        'created_by',
        'is_favourite',
        'is_complete',
        'marked_at',
        'completed_at',
        'progress',
        'issue_type_id',
        'issue_key',
        'parent_id',
        'story_points',
    ];

    public static $priority = [
        'critical' => 'Critical',
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low',
    ];

    public static $priority_color = [
        'critical' => 'danger',
        'high' => 'warning',
        'medium' => 'primary',
        'low' => 'info',
    ];

    public function milestone()
    {
        return $this->hasOne('App\Models\Milestone','id', 'milestone_id');
    }

    public function users()
    {
        return User::whereIn('id', explode(',', $this->assign_to))->get();
    }

    private static $user = NULL;
    private static $data = NULL;

    public static function getusers()
    {
        $data = [];
        if (self::$user == null) {
            $user = User::get();
            self::$user = $user;
            foreach (self::$user as $user) {
                $data[$user->id]['id'] = $user->id;
                $data[$user->id]['name'] = $user->name;
                $data[$user->id]['avatar'] = $user->avatar;

            }
            self::$data = $data;
        }
        return self::$data;
    }


    public function project()
    {
        return $this->hasOne('App\Models\Project', 'id', 'project_id');
    }

    public function stage()
    {
        return $this->hasOne('App\Models\TaskStage', 'id', 'stage_id');
    }

    public function taskProgress($project)
    {
        $percentage = 0;

        $total_checklist     = $this->checklist->count();
        $completed_checklist = $project->checklist->where('status', '=', '1')->count();

        if($total_checklist > 0)
        {
            $percentage = intval(($completed_checklist / $total_checklist) * 100);
        }

        $color = Utility::getProgressColor($percentage);

        return [
            'color' => $color,
            'percentage' => $percentage . '%',
        ];
    }
    public function task_user(){
        return $this->hasOne('App\Models\User','id','assign_to');
    }
    public function checklist()
    {
        return $this->hasMany('App\Models\TaskChecklist', 'task_id', 'id')->orderBy('id', 'DESC');
    }

    public function taskFiles()
    {
        return $this->hasMany('App\Models\TaskFile', 'task_id', 'id')->orderBy('id', 'DESC');
    }

    public function comments()
    {
        return $this->hasMany('App\Models\TaskComment', 'task_id', 'id')->orderBy('id', 'DESC');
    }

    public function countTaskChecklist()
    {
        return $this->checklist->where('status', '=', 1)->count() . '/' . $this->checklist->count();
    }

    public static function deleteTask($task_ids)
    {
        $status = false;

        foreach($task_ids as $key => $task_id)
        {
            $task = ProjectTask::find($task_id);

            if($task)
            {
                // Delete Attachments
                $taskattachments = TaskFile::where('task_id', '=', $task->id);
                $attachmentfiles = $taskattachments->pluck('file')->toArray();
                Utility::checkFileExistsnDelete($attachmentfiles);
                $taskattachments->delete();

                // Delete Timesheets
                $task->timesheets()->delete();

                // Delete Checklists
                TaskChecklist::where('task_id', '=', $task->id)->delete();

                // Delete Comments
                TaskComment::where('task_id', '=', $task->id)->delete();

                // Delete Task
                $status = $task->delete();
            }
        }

        return true;
    }

    public function activity_log()
    {
        if (\Auth::user()->type == 'company') {
            return ActivityLog::where('project_id', '=', $this->project_id)->where('task_id', '=', $this->id)->get();
        } else {
            return ActivityLog::where('user_id', '=', \Auth::user()->id)->where('project_id', '=', $this->project_id)->where('task_id', '=', $this->id)->get();
        }
    }

    // Return milestone wise tasks
    public static function getAllSectionedTaskList($request, $project, $filterdata = [], $not_task_ids = [])
    {
        $taskArray    = $sectionArray = [];
        $counter      = 1;
        $taskSections = $project->tasksections()->pluck('title', 'id')->toArray();
        $section_ids  = array_keys($taskSections);
        $task_ids     = Project::getAssignedProjectTasks($project->id, null, $filterdata)->whereNotIn('milestone_id', $section_ids)->whereNotIn('id', $not_task_ids)->orderBy('id', 'desc')->pluck('id')->toArray();
        if(!empty($task_ids) && count($task_ids) > 0)
        {
            $counter                              = 0;
            $taskArray[$counter]['section_id']    = 0;
            $taskArray[$counter]['section_name']  = '';
            $taskArray[$counter]['sectionsClass'] = 'active';
            foreach($task_ids as $task_id)
            {
                $task                            = ProjectTask::find($task_id);
                $taskCollectionArray             = $task->toArray();
                $taskCollectionArray['taskinfo'] = json_decode(app('App\Http\Controllers\ProjectTaskController')->getDefaultTaskInfo($request, $task->id), true);

                $taskArray[$counter]['sections'][] = $taskCollectionArray;
            }
            $counter++;
        }
        if(!empty($section_ids) && count($section_ids) > 0)
        {
            foreach($taskSections as $section_id => $section_name)
            {
                $tasks                               = Project::getAssignedProjectTasks($project->id, null, $filterdata)->where('project_tasks.milestone_id', $section_id)->whereNotIn('id', $not_task_ids)->orderBy('id', 'desc')->get()->toArray();
                $taskArray[$counter]['section_id']   = $section_id;
                $taskArray[$counter]['section_name'] = $section_name;
                $sectiontasks                        = $tasks;

                foreach($tasks as $onekey => $onetask)
                {
                    $sectiontasks[$onekey]['taskinfo'] = json_decode(app('App\Http\Controllers\ProjectTaskController')->getDefaultTaskInfo($request, $onetask['id']), true);
                }

                $taskArray[$counter]['sections']      = $sectiontasks;
                $taskArray[$counter]['sectionsClass'] = 'active';
                $counter++;
            }
        }

        return $taskArray;
    }

    public function timesheets()
    {
        return $this->hasMany('App\Models\Timesheet', 'task_id', 'id')->orderBy('id', 'desc');
    }

    public function issueType()
    {
        return $this->belongsTo('App\Models\IssueType', 'issue_type_id');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\ProjectTask', 'parent_id');
    }

    public function sprint()
    {
        return $this->belongsTo('App\Models\Sprint', 'sprint_id');
    }

    // Scope for backlog items (no sprint assigned)
    public function scopeInBacklog($query)
    {
        return $query->whereNull('sprint_id');
    }

    // Scope for sprint items
    public function scopeInSprint($query, $sprintId)
    {
        return $query->where('sprint_id', $sprintId);
    }

    public function children()
    {
        return $this->hasMany('App\Models\ProjectTask', 'parent_id')->orderBy('order', 'asc');
    }

    public function subtasks()
    {
        return $this->children();
    }

    /**
     * Check if this task is a container type (Epic/Story)
     * Container types aggregate time from children instead of having their own time
     */
    public function isContainer(): bool
    {
        if ($this->issueType) {
            return $this->issueType->is_container ?? false;
        }
        return false;
    }

    /**
     * Check if this task is a work item (Task/Bug/Sub-task)
     * Work items have actual estimated hours
     */
    public function isWorkItem(): bool
    {
        return !$this->isContainer();
    }

    /**
     * Get total estimated hours for this item
     * For containers (Epic/Story): returns sum of all children's hours
     * For work items (Task/Bug/Sub-task): returns own estimated_hrs
     */
    public function getTotalEstimatedHrs(): float
    {
        if ($this->isContainer()) {
            return $this->getChildrenTotalHrs();
        }
        return floatval($this->estimated_hrs ?? 0);
    }

    /**
     * Get total estimated hours from all children (recursive)
     * Only counts work items, not container children
     */
    public function getChildrenTotalHrs(): float
    {
        $total = 0;
        $children = $this->children()->with('issueType')->get();

        foreach ($children as $child) {
            if ($child->isContainer()) {
                // Recursively get hours from container's children
                $total += $child->getChildrenTotalHrs();
            } else {
                // Work item - add its estimated hours
                $total += floatval($child->estimated_hrs ?? 0);
            }
        }

        return $total;
    }

    /**
     * Get all work item descendants (recursive)
     * Returns only Task, Bug, Sub-task - excludes Epic/Story
     */
    public function getWorkItemDescendants(): \Illuminate\Support\Collection
    {
        $workItems = collect();
        $children = $this->children()->with('issueType')->get();

        foreach ($children as $child) {
            if ($child->isContainer()) {
                // Recursively get work items from container
                $workItems = $workItems->merge($child->getWorkItemDescendants());
            } else {
                // This is a work item
                $workItems->push($child);
            }
        }

        return $workItems;
    }

    /**
     * Get count of completed work items vs total
     */
    public function getWorkItemProgress(): array
    {
        $workItems = $this->getWorkItemDescendants();
        $total = $workItems->count();
        $completed = $workItems->where('is_complete', 1)->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (empty($task->issue_key)) {
                $task->issue_key = static::generateIssueKey($task);
            }
        });
    }

    public static function generateIssueKey($task)
    {
        $project = Project::find($task->project_id);
        if (!$project) {
            return null;
        }

        $projectKey = strtoupper(substr($project->project_name, 0, 3));
        $projectKey = preg_replace('/[^A-Z0-9]/', '', $projectKey);

        if (strlen($projectKey) < 2) {
            $projectKey = 'PRJ';
        }

        $lastTask = static::where('project_id', $task->project_id)
            ->whereNotNull('issue_key')
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastTask && $lastTask->issue_key) {
            preg_match('/\d+$/', $lastTask->issue_key, $matches);
            if (!empty($matches)) {
                $nextNumber = intval($matches[0]) + 1;
            }
        }

        return $projectKey . '-' . $nextNumber;
    }
}
