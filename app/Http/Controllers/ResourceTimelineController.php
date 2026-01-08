<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Timesheet;
use App\Models\TimeTracker;
use App\Models\User;
use App\Models\Utility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResourceTimelineController extends Controller
{
    /**
     * Display the resource timeline view
     */
    public function index(Request $request)
    {
        if (Auth::user()->isClient()) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $user = Auth::user();

        // Get all projects for filter dropdown
        if ($user->type == 'company') {
            $projects = Project::where('created_by', $user->id)->get();
        } else {
            $projectIds = $user->projects()->pluck('projects.id');
            $projects = Project::whereIn('id', $projectIds)->get();
        }

        return view('resource-timeline.index', compact('projects'));
    }

    /**
     * Get timeline data via AJAX
     */
    public function getTimelineData(Request $request)
    {
        $user = Auth::user();
        $viewType = $request->get('view_type', 'daily'); // daily or weekly
        $date = $request->get('date', date('Y-m-d'));
        $projectId = $request->get('project_id', null);

        // Calculate date range based on view type
        if ($viewType == 'weekly') {
            $startDate = Carbon::parse($date)->startOfWeek();
            $endDate = Carbon::parse($date)->endOfWeek();
            $timeSlots = $this->getWeekDays($startDate);
        } else {
            $startDate = Carbon::parse($date)->startOfDay();
            $endDate = Carbon::parse($date)->endOfDay();
            $timeSlots = $this->getHourSlots();
        }

        // Get users based on permission
        if ($user->type == 'company') {
            $users = User::where('created_by', $user->id)
                ->where('type', '!=', 'client')
                ->orderBy('name')
                ->get();
        } else {
            // Non-company users see only themselves
            $users = User::where('id', $user->id)->get();
        }

        $usersData = [];
        $usersWithTasks = 0;
        $usersWithoutTasks = 0;
        $unassignedUserIds = [];

        foreach ($users as $u) {
            $userData = [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar' => $u->avatar ? asset(Storage::url('uploads/avatar/' . $u->avatar)) : asset('assets/images/user/avatar-1.jpg'),
                'type' => $u->type,
                'has_tasks' => false,
                'tasks' => [],
                'total_estimated_hrs' => 0,
                'total_actual_hrs' => 0,
            ];

            // Get tasks assigned to this user within date range
            $tasksQuery = ProjectTask::whereRaw("FIND_IN_SET(?, assign_to)", [$u->id])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->orWhereBetween('end_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->orWhere(function ($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate->format('Y-m-d'))
                                ->where('end_date', '>=', $endDate->format('Y-m-d'));
                        });
                })
                ->with(['project', 'stage']);

            if ($projectId) {
                $tasksQuery->where('project_id', $projectId);
            }

            $tasks = $tasksQuery->get();

            if ($tasks->count() > 0) {
                $userData['has_tasks'] = true;
                $usersWithTasks++;

                foreach ($tasks as $task) {
                    $taskData = $this->formatTaskData($task, $u->id, $viewType, $startDate, $endDate, $timeSlots);
                    if ($taskData) {
                        $userData['tasks'][] = $taskData;
                        $userData['total_estimated_hrs'] += $task->estimated_hrs ?? 0;
                    }
                }

                // Get actual hours from timesheets
                $actualTime = Timesheet::where('created_by', $u->id)
                    ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->get();

                $totalMinutes = 0;
                foreach ($actualTime as $ts) {
                    if ($ts->time) {
                        $parts = explode(':', $ts->time);
                        $totalMinutes += (int)$parts[0] * 60 + (int)($parts[1] ?? 0);
                    }
                }
                $userData['total_actual_hrs'] = round($totalMinutes / 60, 1);
            } else {
                $usersWithoutTasks++;
                $unassignedUserIds[] = $u->id;
            }

            $usersData[] = $userData;
        }

        return response()->json([
            'success' => true,
            'view_type' => $viewType,
            'date' => $date,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'time_slots' => $timeSlots,
            'users' => $usersData,
            'summary' => [
                'total_users' => count($usersData),
                'users_with_tasks' => $usersWithTasks,
                'users_without_tasks' => $usersWithoutTasks,
                'unassigned_users' => $unassignedUserIds,
            ],
        ]);
    }

    /**
     * Get hour slots for daily view (8 AM - 6 PM)
     */
    private function getHourSlots()
    {
        $slots = [];
        for ($hour = 8; $hour <= 18; $hour++) {
            $slots[] = [
                'label' => sprintf('%02d:00', $hour),
                'value' => $hour,
            ];
        }
        return $slots;
    }

    /**
     * Get week days for weekly view
     */
    private function getWeekDays($startDate)
    {
        $slots = [];
        $current = $startDate->copy();
        for ($i = 0; $i < 7; $i++) {
            $slots[] = [
                'label' => $current->format('D'),
                'date' => $current->format('Y-m-d'),
                'day' => $current->format('j'),
                'is_today' => $current->isToday(),
            ];
            $current->addDay();
        }
        return $slots;
    }

    /**
     * Format task data for timeline display
     */
    private function formatTaskData($task, $userId, $viewType, $startDate, $endDate, $timeSlots)
    {
        $taskStart = $task->start_date ? Carbon::parse($task->start_date) : null;
        $taskEnd = $task->end_date ? Carbon::parse($task->end_date) : $taskStart;

        if (!$taskStart) {
            return null;
        }

        // Priority colors
        $priorityColors = [
            'critical' => '#dc3545',
            'high' => '#fd7e14',
            'medium' => '#ffc107',
            'low' => '#28a745',
        ];

        // Calculate position and span
        if ($viewType == 'weekly') {
            // For weekly view, calculate which day columns the task spans
            $startSlot = max(0, $taskStart->diffInDays($startDate, false));
            $endSlot = min(6, $taskEnd->diffInDays($startDate, false));
            $span = max(1, $endSlot - $startSlot + 1);

            // Clamp to visible range
            if ($startSlot > 6 || $endSlot < 0) {
                return null;
            }
            $startSlot = max(0, $startSlot);
        } else {
            // For daily view, distribute task across hours based on estimated_hrs
            $estimatedHrs = $task->estimated_hrs ?? 2;
            $startSlot = 0; // Start at first slot (8 AM)
            $span = min($estimatedHrs, 10); // Max 10 hours shown
        }

        // Get actual time logged for this task
        $actualMinutes = 0;
        $timesheets = Timesheet::where('task_id', $task->id)
            ->where('created_by', $userId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        foreach ($timesheets as $ts) {
            if ($ts->time) {
                $parts = explode(':', $ts->time);
                $actualMinutes += (int)$parts[0] * 60 + (int)($parts[1] ?? 0);
            }
        }
        $actualHrs = round($actualMinutes / 60, 1);

        // Calculate progress
        $progress = 0;
        if ($task->estimated_hrs > 0) {
            $progress = min(100, round(($actualHrs / $task->estimated_hrs) * 100));
        }

        return [
            'id' => $task->id,
            'name' => $task->name,
            'project_id' => $task->project_id,
            'project_name' => $task->project ? $task->project->project_name : 'No Project',
            'start_date' => $taskStart->format('Y-m-d'),
            'end_date' => $taskEnd->format('Y-m-d'),
            'start_slot' => $startSlot,
            'span' => $span,
            'priority' => $task->priority ?? 'medium',
            'color' => $priorityColors[$task->priority ?? 'medium'] ?? '#6c757d',
            'estimated_hrs' => $task->estimated_hrs ?? 0,
            'actual_hrs' => $actualHrs,
            'progress' => $progress,
            'stage' => $task->stage ? $task->stage->name : 'To Do',
            'is_complete' => $task->is_complete == 1,
        ];
    }
}
