<?php

namespace App\Http\Controllers;

use App\Models\Sprint;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Bug;
use App\Models\IssueType;
use App\Models\TaskStage;
use App\Models\BugStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SprintController extends Controller
{
    /**
     * Sprint planning view (backlog + sprints) - Hierarchical
     */
    public function planning($projectId)
    {
        if (!Auth::user()->can('manage project task')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $project = Project::where('id', $projectId)
            ->where('created_by', Auth::user()->creatorId())
            ->firstOrFail();

        // Get issue types
        $epicType = IssueType::where('name', 'Epic')->first();
        $storyType = IssueType::where('name', 'Story')->first();
        $workItemTypeIds = IssueType::where('is_container', false)->pluck('id')->toArray();
        $containerTypeIds = IssueType::where('is_container', true)->pluck('id')->toArray();

        // Get milestones for the project
        $milestones = \App\Models\Milestone::where('project_id', $projectId)
            ->orderBy('due_date')
            ->get();

        // Build hierarchical backlog structure
        $backlogHierarchy = [];

        // Process each milestone
        foreach ($milestones as $milestone) {
            $milestoneData = [
                'id' => $milestone->id,
                'title' => $milestone->title,
                'due_date' => $milestone->due_date,
                'type' => 'milestone',
                'epics' => [],
                'standalone_tasks' => [], // Tasks directly under milestone (no epic/story parent)
            ];

            // Get Epics under this milestone
            $epics = ProjectTask::where('project_id', $projectId)
                ->where('milestone_id', $milestone->id)
                ->where('issue_type_id', $epicType ? $epicType->id : 0)
                ->whereNull('sprint_id')
                ->with(['issueType'])
                ->orderBy('order')
                ->get();

            foreach ($epics as $epic) {
                $epicData = [
                    'id' => $epic->id,
                    'name' => $epic->name,
                    'issue_key' => $epic->issue_key,
                    'color' => $epic->issueType->color ?? '#6366f1',
                    'type' => 'epic',
                    'stories' => [],
                    'direct_tasks' => [], // Tasks directly under epic (no story parent)
                ];

                // Get Stories under this Epic
                $stories = ProjectTask::where('project_id', $projectId)
                    ->where('parent_id', $epic->id)
                    ->where('issue_type_id', $storyType ? $storyType->id : 0)
                    ->whereNull('sprint_id')
                    ->with(['issueType'])
                    ->orderBy('order')
                    ->get();

                foreach ($stories as $story) {
                    $storyData = [
                        'id' => $story->id,
                        'name' => $story->name,
                        'issue_key' => $story->issue_key,
                        'color' => $story->issueType->color ?? '#3b82f6',
                        'type' => 'story',
                        'tasks' => [],
                    ];

                    // Get work items under this Story
                    $storyData['tasks'] = ProjectTask::where('project_id', $projectId)
                        ->where('parent_id', $story->id)
                        ->whereNull('sprint_id')
                        ->where(function ($q) use ($workItemTypeIds) {
                            $q->whereIn('issue_type_id', $workItemTypeIds)
                                ->orWhereNull('issue_type_id');
                        })
                        ->with(['issueType', 'stage'])
                        ->orderBy('order')
                        ->get();

                    $epicData['stories'][] = $storyData;
                }

                // Get tasks directly under Epic (not under any Story)
                $epicData['direct_tasks'] = ProjectTask::where('project_id', $projectId)
                    ->where('parent_id', $epic->id)
                    ->whereNull('sprint_id')
                    ->where(function ($q) use ($workItemTypeIds) {
                        $q->whereIn('issue_type_id', $workItemTypeIds)
                            ->orWhereNull('issue_type_id');
                    })
                    ->with(['issueType', 'stage'])
                    ->orderBy('order')
                    ->get();

                $milestoneData['epics'][] = $epicData;
            }

            // Get standalone tasks under milestone (no epic parent)
            $milestoneData['standalone_tasks'] = ProjectTask::where('project_id', $projectId)
                ->where('milestone_id', $milestone->id)
                ->whereNull('parent_id')
                ->whereNull('sprint_id')
                ->where(function ($q) use ($workItemTypeIds) {
                    $q->whereIn('issue_type_id', $workItemTypeIds)
                        ->orWhereNull('issue_type_id');
                })
                ->with(['issueType', 'stage'])
                ->orderBy('order')
                ->get();

            $backlogHierarchy[] = $milestoneData;
        }

        // Get items without milestone (Unassigned)
        $unassignedData = [
            'id' => 0,
            'title' => __('Unassigned'),
            'due_date' => null,
            'type' => 'unassigned',
            'epics' => [],
            'standalone_tasks' => [],
        ];

        // Epics without milestone
        $unassignedEpics = ProjectTask::where('project_id', $projectId)
            ->whereNull('milestone_id')
            ->where('issue_type_id', $epicType ? $epicType->id : 0)
            ->whereNull('sprint_id')
            ->with(['issueType'])
            ->orderBy('order')
            ->get();

        foreach ($unassignedEpics as $epic) {
            $epicData = [
                'id' => $epic->id,
                'name' => $epic->name,
                'issue_key' => $epic->issue_key,
                'color' => $epic->issueType->color ?? '#6366f1',
                'type' => 'epic',
                'stories' => [],
                'direct_tasks' => [],
            ];

            // Stories under this Epic
            $stories = ProjectTask::where('project_id', $projectId)
                ->where('parent_id', $epic->id)
                ->where('issue_type_id', $storyType ? $storyType->id : 0)
                ->whereNull('sprint_id')
                ->with(['issueType'])
                ->orderBy('order')
                ->get();

            foreach ($stories as $story) {
                $storyData = [
                    'id' => $story->id,
                    'name' => $story->name,
                    'issue_key' => $story->issue_key,
                    'color' => $story->issueType->color ?? '#3b82f6',
                    'type' => 'story',
                    'tasks' => ProjectTask::where('project_id', $projectId)
                        ->where('parent_id', $story->id)
                        ->whereNull('sprint_id')
                        ->where(function ($q) use ($workItemTypeIds) {
                            $q->whereIn('issue_type_id', $workItemTypeIds)
                                ->orWhereNull('issue_type_id');
                        })
                        ->with(['issueType', 'stage'])
                        ->orderBy('order')
                        ->get(),
                ];
                $epicData['stories'][] = $storyData;
            }

            // Direct tasks under Epic
            $epicData['direct_tasks'] = ProjectTask::where('project_id', $projectId)
                ->where('parent_id', $epic->id)
                ->whereNull('sprint_id')
                ->where(function ($q) use ($workItemTypeIds) {
                    $q->whereIn('issue_type_id', $workItemTypeIds)
                        ->orWhereNull('issue_type_id');
                })
                ->with(['issueType', 'stage'])
                ->orderBy('order')
                ->get();

            $unassignedData['epics'][] = $epicData;
        }

        // Standalone tasks without milestone and without parent
        $unassignedData['standalone_tasks'] = ProjectTask::where('project_id', $projectId)
            ->whereNull('milestone_id')
            ->whereNull('parent_id')
            ->whereNull('sprint_id')
            ->where(function ($q) use ($workItemTypeIds) {
                $q->whereIn('issue_type_id', $workItemTypeIds)
                    ->orWhereNull('issue_type_id');
            })
            ->with(['issueType', 'stage'])
            ->orderBy('order')
            ->get();

        // Add unassigned section if it has items
        if (count($unassignedData['epics']) > 0 || count($unassignedData['standalone_tasks']) > 0) {
            $backlogHierarchy[] = $unassignedData;
        }

        // Count total backlog items
        $backlogCount = 0;
        foreach ($backlogHierarchy as $milestone) {
            $backlogCount += count($milestone['standalone_tasks']);
            foreach ($milestone['epics'] as $epic) {
                $backlogCount += count($epic['direct_tasks']);
                foreach ($epic['stories'] as $story) {
                    $backlogCount += count($story['tasks']);
                }
            }
        }

        // Get bugs in backlog (not in any sprint)
        $backlogBugs = Bug::where('project_id', $projectId)
            ->whereNull('sprint_id')
            ->with(['bug_status', 'assignTo'])
            ->orderBy('backlog_order')
            ->orderBy('created_at', 'desc')
            ->get();

        $backlogCount += $backlogBugs->count();

        // All sprints for this project (planning and active first)
        $sprints = Sprint::forProject($projectId)
            ->orderByRaw("FIELD(status, 'active', 'planning', 'completed', 'cancelled')")
            ->orderBy('start_date', 'desc')
            ->get();

        // Load tasks and bugs for each sprint
        foreach ($sprints as $sprint) {
            $sprint->sprintTasks = ProjectTask::where('project_id', $projectId)
                ->where('sprint_id', $sprint->id)
                ->where(function ($q) use ($workItemTypeIds) {
                    $q->whereIn('issue_type_id', $workItemTypeIds)
                        ->orWhereNull('issue_type_id');
                })
                ->with(['issueType', 'stage', 'parent'])
                ->orderBy('order')
                ->get();

            $sprint->sprintBugs = Bug::where('project_id', $projectId)
                ->where('sprint_id', $sprint->id)
                ->with(['bug_status', 'assignTo'])
                ->orderBy('backlog_order')
                ->get();
        }

        // Get bug statuses for display
        $bugStatuses = BugStatus::where(function ($q) {
            $q->where('created_by', Auth::user()->creatorId())
                ->orWhere('created_by', 0);
        })->get();

        // Get project users for assignment
        $projectUsers = $project->users;

        return view('sprints.planning', compact(
            'project',
            'backlogHierarchy',
            'backlogCount',
            'backlogBugs',
            'sprints',
            'bugStatuses',
            'projectUsers'
        ));
    }

    /**
     * Sprint board (Kanban view for a sprint)
     */
    public function board($projectId, $sprintId = null)
    {
        if (!Auth::user()->can('manage project task')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $project = Project::where('id', $projectId)
            ->where('created_by', Auth::user()->creatorId())
            ->firstOrFail();

        // Get sprint (active or specified)
        if ($sprintId) {
            $sprint = Sprint::where('id', $sprintId)
                ->where('project_id', $projectId)
                ->firstOrFail();
        } else {
            $sprint = Sprint::forProject($projectId)->active()->first();
        }

        if (!$sprint) {
            return redirect()->route('sprints.planning', $projectId)
                ->with('info', __('No active sprint. Start planning one.'));
        }

        // Get stages for Kanban columns
        $stages = TaskStage::orderBy('order')
            ->where(function ($q) {
                $q->where('created_by', Auth::user()->creatorId())
                    ->orWhere('created_by', 0);
            })
            ->get();

        // Get work item types
        $workItemTypeIds = IssueType::where('is_container', false)->pluck('id')->toArray();

        // Get tasks for each stage
        foreach ($stages as $stage) {
            $stage->sprintTasks = ProjectTask::where('project_id', $projectId)
                ->where('sprint_id', $sprint->id)
                ->where('stage_id', $stage->id)
                ->where(function ($q) use ($workItemTypeIds) {
                    $q->whereIn('issue_type_id', $workItemTypeIds)
                        ->orWhereNull('issue_type_id');
                })
                ->with(['issueType', 'parent'])
                ->orderBy('order')
                ->get();
        }

        // Get bugs for the sprint (grouped by status)
        $sprintBugs = Bug::where('project_id', $projectId)
            ->where('sprint_id', $sprint->id)
            ->with(['bug_status', 'assignTo'])
            ->orderBy('backlog_order')
            ->get();

        // Get bug statuses
        $bugStatuses = BugStatus::where(function ($q) {
            $q->where('created_by', Auth::user()->creatorId())
                ->orWhere('created_by', 0);
        })->get();

        // Get other sprints for switching
        $allSprints = Sprint::forProject($projectId)
            ->whereIn('status', [Sprint::STATUS_ACTIVE, Sprint::STATUS_COMPLETED, Sprint::STATUS_PLANNING])
            ->orderBy('start_date', 'desc')
            ->get();

        // Get burndown data
        $burndownData = $sprint->getBurndownChartData();

        return view('sprints.board', compact(
            'project',
            'sprint',
            'stages',
            'sprintBugs',
            'bugStatuses',
            'allSprints',
            'burndownData'
        ));
    }

    /**
     * Create sprint form (modal)
     */
    public function create($projectId)
    {
        if (!Auth::user()->can('create project')) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $project = Project::findOrFail($projectId);

        // Get last sprint end date for suggested start
        $lastSprint = Sprint::forProject($projectId)->orderBy('end_date', 'desc')->first();
        $suggestedStart = $lastSprint
            ? $lastSprint->end_date->addDay()
            : Carbon::today();

        // Default 2-week sprint
        $suggestedEnd = $suggestedStart->copy()->addDays(13);

        // Generate sprint name
        $sprintCount = Sprint::forProject($projectId)->count();
        $suggestedName = 'Sprint ' . ($sprintCount + 1);

        return view('sprints.create', compact('project', 'suggestedStart', 'suggestedEnd', 'suggestedName'));
    }

    /**
     * Store new sprint
     */
    public function store(Request $request, $projectId)
    {
        if (!Auth::user()->can('create project')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'goal' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $project = Project::findOrFail($projectId);

        // Get max order
        $maxOrder = Sprint::forProject($projectId)->max('order') ?? 0;

        $sprint = Sprint::create([
            'name' => $request->name,
            'goal' => $request->goal,
            'project_id' => $projectId,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => Sprint::STATUS_PLANNING,
            'order' => $maxOrder + 1,
            'created_by' => Auth::user()->creatorId(),
        ]);

        return redirect()->route('sprints.planning', $projectId)
            ->with('success', __('Sprint created successfully.'));
    }

    /**
     * Edit sprint form
     */
    public function edit($projectId, $sprintId)
    {
        if (!Auth::user()->can('edit project')) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $project = Project::findOrFail($projectId);
        $sprint = Sprint::where('id', $sprintId)
            ->where('project_id', $projectId)
            ->firstOrFail();

        return view('sprints.edit', compact('project', 'sprint'));
    }

    /**
     * Update sprint
     */
    public function update(Request $request, $projectId, $sprintId)
    {
        if (!Auth::user()->can('edit project')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'goal' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $sprint = Sprint::where('id', $sprintId)
            ->where('project_id', $projectId)
            ->firstOrFail();

        $sprint->update([
            'name' => $request->name,
            'goal' => $request->goal,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return redirect()->route('sprints.planning', $projectId)
            ->with('success', __('Sprint updated successfully.'));
    }

    /**
     * Delete sprint
     */
    public function destroy($projectId, $sprintId)
    {
        if (!Auth::user()->can('delete project')) {
            return response()->json(['error' => __('Permission Denied.')], 403);
        }

        $sprint = Sprint::where('id', $sprintId)
            ->where('project_id', $projectId)
            ->firstOrFail();

        // Move all tasks and bugs back to backlog
        ProjectTask::where('sprint_id', $sprintId)->update(['sprint_id' => null]);
        Bug::where('sprint_id', $sprintId)->update(['sprint_id' => null]);

        $sprint->delete();

        return response()->json([
            'success' => true,
            'message' => __('Sprint deleted successfully.'),
        ]);
    }

    /**
     * Start sprint
     */
    public function start($projectId, $sprintId)
    {
        $sprint = Sprint::where('id', $sprintId)
            ->where('project_id', $projectId)
            ->firstOrFail();

        if (!$sprint->canStart()) {
            return response()->json(['error' => __('Sprint cannot be started.')], 400);
        }

        // Check if another sprint is active
        $activeSprint = Sprint::forProject($projectId)->active()->first();
        if ($activeSprint) {
            return response()->json([
                'error' => __('Another sprint is already active. Complete it first.')
            ], 400);
        }

        $sprint->update([
            'status' => Sprint::STATUS_ACTIVE,
            'start_date' => Carbon::today(),
        ]);

        // Record initial burndown
        $sprint->recordBurndownSnapshot();

        return response()->json([
            'success' => true,
            'message' => __('Sprint started successfully.'),
        ]);
    }

    /**
     * Complete sprint
     */
    public function complete($projectId, $sprintId)
    {
        $sprint = Sprint::where('id', $sprintId)
            ->where('project_id', $projectId)
            ->firstOrFail();

        if (!$sprint->canComplete()) {
            return response()->json(['error' => __('Sprint cannot be completed.')], 400);
        }

        $sprint->update([
            'status' => Sprint::STATUS_COMPLETED,
            'end_date' => Carbon::today(),
        ]);

        // Record final burndown
        $sprint->recordBurndownSnapshot();

        // Get incomplete tasks
        $workItemTypeIds = IssueType::where('is_container', false)->pluck('id')->toArray();
        $incompleteTasks = ProjectTask::where('sprint_id', $sprintId)
            ->where('is_complete', 0)
            ->where(function ($q) use ($workItemTypeIds) {
                $q->whereIn('issue_type_id', $workItemTypeIds)
                    ->orWhereNull('issue_type_id');
            })
            ->get();

        return response()->json([
            'success' => true,
            'message' => __('Sprint completed successfully.'),
            'incomplete_count' => $incompleteTasks->count(),
            'incomplete_tasks' => $incompleteTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'story_points' => $task->story_points,
                ];
            }),
        ]);
    }

    /**
     * Move incomplete tasks to backlog or next sprint
     */
    public function moveIncompleteTasks(Request $request, $projectId, $sprintId)
    {
        $destination = $request->input('destination', 'backlog');
        $taskIds = $request->input('task_ids', []);

        if (empty($taskIds)) {
            // Move all incomplete tasks
            $taskIds = ProjectTask::where('sprint_id', $sprintId)
                ->where('is_complete', 0)
                ->pluck('id')
                ->toArray();
        }

        if ($destination === 'backlog') {
            ProjectTask::whereIn('id', $taskIds)->update(['sprint_id' => null]);
        } else {
            // Move to specified sprint
            $nextSprintId = $request->input('next_sprint_id');
            if ($nextSprintId) {
                ProjectTask::whereIn('id', $taskIds)->update(['sprint_id' => $nextSprintId]);
            }
        }

        return response()->json(['success' => true, 'message' => __('Tasks moved successfully.')]);
    }

    /**
     * AJAX: Move task to sprint or backlog
     */
    public function moveTask(Request $request)
    {
        $taskId = $request->input('task_id');
        $sprintId = $request->input('sprint_id'); // null for backlog
        $order = $request->input('order', 0);

        $task = ProjectTask::findOrFail($taskId);
        $oldSprintId = $task->sprint_id;

        $task->update([
            'sprint_id' => $sprintId ?: null,
            'order' => $order,
        ]);

        // Record burndown for affected sprints
        if ($oldSprintId) {
            $oldSprint = Sprint::find($oldSprintId);
            if ($oldSprint && $oldSprint->isActive()) {
                $oldSprint->recordBurndownSnapshot();
            }
        }

        if ($sprintId) {
            $newSprint = Sprint::find($sprintId);
            if ($newSprint && $newSprint->isActive()) {
                $newSprint->recordBurndownSnapshot();
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('Task moved successfully.'),
        ]);
    }

    /**
     * AJAX: Reorder tasks within sprint/backlog
     */
    public function reorderTasks(Request $request)
    {
        $tasks = $request->input('tasks', []);
        $sprintId = $request->input('sprint_id');

        foreach ($tasks as $index => $taskId) {
            if ($sprintId) {
                ProjectTask::where('id', $taskId)->update(['order' => $index]);
            } else {
                ProjectTask::where('id', $taskId)->update(['backlog_order' => $index]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * AJAX: Update task stage (for board drag-drop)
     */
    public function updateTaskStage(Request $request)
    {
        $taskId = $request->input('task_id');
        $stageId = $request->input('stage_id');
        $order = $request->input('order', 0);

        $task = ProjectTask::findOrFail($taskId);
        $stage = TaskStage::findOrFail($stageId);

        $task->update([
            'stage_id' => $stageId,
            'order' => $order,
            'is_complete' => $stage->complete ? 1 : 0,
            'completed_at' => $stage->complete ? Carbon::today() : null,
        ]);

        // Update burndown if in active sprint
        if ($task->sprint && $task->sprint->isActive()) {
            $task->sprint->recordBurndownSnapshot();
        }

        return response()->json([
            'success' => true,
            'message' => __('Task updated successfully.'),
        ]);
    }

    /**
     * Get burndown chart data
     */
    public function burndownData($projectId, $sprintId)
    {
        $sprint = Sprint::where('id', $sprintId)
            ->where('project_id', $projectId)
            ->firstOrFail();

        return response()->json($sprint->getBurndownChartData());
    }

    /**
     * Get velocity data
     */
    public function velocityData($projectId)
    {
        $sprints = Sprint::forProject($projectId)
            ->where('status', Sprint::STATUS_COMPLETED)
            ->orderBy('end_date', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        $data = [
            'labels' => $sprints->pluck('name')->toArray(),
            'committed' => [],
            'completed' => [],
        ];

        foreach ($sprints as $sprint) {
            $data['committed'][] = (float) $sprint->total_story_points;
            $data['completed'][] = (float) $sprint->completed_story_points;
        }

        // Calculate average velocity
        $avgVelocity = count($data['completed']) > 0
            ? array_sum($data['completed']) / count($data['completed'])
            : 0;

        return response()->json([
            'chart' => $data,
            'average_velocity' => round($avgVelocity, 1),
        ]);
    }

    /**
     * AJAX: Update story points for task
     */
    public function updateStoryPoints(Request $request, $taskId)
    {
        $task = ProjectTask::findOrFail($taskId);
        $task->update(['story_points' => $request->input('story_points')]);

        // Update burndown if in active sprint
        if ($task->sprint && $task->sprint->isActive()) {
            $task->sprint->recordBurndownSnapshot();
        }

        return response()->json([
            'success' => true,
            'story_points' => $task->story_points,
        ]);
    }

    /**
     * AJAX: Move bug to sprint or backlog
     */
    public function moveBug(Request $request)
    {
        $bugId = $request->input('bug_id');
        $sprintId = $request->input('sprint_id'); // null for backlog
        $order = $request->input('order', 0);

        $bug = Bug::findOrFail($bugId);
        $oldSprintId = $bug->sprint_id;

        $bug->update([
            'sprint_id' => $sprintId ?: null,
            'backlog_order' => $order,
        ]);

        // Record burndown for affected sprints
        if ($oldSprintId) {
            $oldSprint = Sprint::find($oldSprintId);
            if ($oldSprint && $oldSprint->isActive()) {
                $oldSprint->recordBurndownSnapshot();
            }
        }

        if ($sprintId) {
            $newSprint = Sprint::find($sprintId);
            if ($newSprint && $newSprint->isActive()) {
                $newSprint->recordBurndownSnapshot();
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('Bug moved successfully.'),
        ]);
    }

    /**
     * AJAX: Update story points for bug
     */
    public function updateBugStoryPoints(Request $request, $bugId)
    {
        $bug = Bug::findOrFail($bugId);
        $bug->update(['story_points' => $request->input('story_points')]);

        // Update burndown if in active sprint
        if ($bug->sprint && $bug->sprint->isActive()) {
            $bug->sprint->recordBurndownSnapshot();
        }

        return response()->json([
            'success' => true,
            'story_points' => $bug->story_points,
        ]);
    }

    /**
     * AJAX: Update bug status
     */
    public function updateBugStatus(Request $request)
    {
        $bugId = $request->input('bug_id');
        $statusId = $request->input('status_id');
        $order = $request->input('order', 0);

        $bug = Bug::findOrFail($bugId);
        $status = BugStatus::findOrFail($statusId);

        $bug->update([
            'status' => $statusId,
            'backlog_order' => $order,
        ]);

        // Check if this status is a "resolved" status and mark bug as resolved
        if (stripos($status->title, 'resolved') !== false ||
            stripos($status->title, 'closed') !== false ||
            stripos($status->title, 'done') !== false) {
            if (!$bug->resolved_at) {
                $bug->markResolved('fixed', Auth::id());
            }
        }

        // Update burndown if in active sprint
        if ($bug->sprint && $bug->sprint->isActive()) {
            $bug->sprint->recordBurndownSnapshot();
        }

        return response()->json([
            'success' => true,
            'message' => __('Bug status updated successfully.'),
        ]);
    }

    /**
     * AJAX: Reorder bugs within sprint/backlog
     */
    public function reorderBugs(Request $request)
    {
        $bugs = $request->input('bugs', []);

        foreach ($bugs as $index => $bugId) {
            Bug::where('id', $bugId)->update(['backlog_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
