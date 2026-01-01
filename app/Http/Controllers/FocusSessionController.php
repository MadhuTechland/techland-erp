<?php

namespace App\Http\Controllers;

use App\Models\FocusSession;
use App\Models\FocusSettings;
use App\Models\FocusDailyStats;
use App\Models\FocusAchievement;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\Timesheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FocusSessionController extends Controller
{
    /**
     * Main focus timer dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $settings = FocusSettings::getOrCreate($user->id);
        $activeSession = FocusSession::getActiveSession($user->id);

        // Get today's stats
        $todayStats = FocusDailyStats::getOrCreateToday($user->id);

        // Get user's projects with tasks for the dropdown
        $projects = Project::whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->orWhere('created_by', $user->id)->with(['tasks' => function ($q) {
            $q->where('is_complete', 0)->orderBy('priority_color', 'desc');
        }])->get();

        // Get weekly stats
        $weeklyStats = FocusDailyStats::getWeeklyStats($user->id);

        // Get recent sessions
        $recentSessions = FocusSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['project', 'task'])
            ->orderBy('ended_at', 'desc')
            ->limit(10)
            ->get();

        // Get achievements
        $achievements = FocusAchievement::where('user_id', $user->id)
            ->orderBy('earned_at', 'desc')
            ->get();

        // Calculate streak
        $streak = FocusDailyStats::calculateStreak($user->id);

        // Level info
        $levelInfo = $settings->getLevelInfo();

        // Unlocked trees
        $unlockedTrees = $settings->getUnlockedTrees();

        return view('focus.index', compact(
            'settings',
            'activeSession',
            'todayStats',
            'projects',
            'weeklyStats',
            'recentSessions',
            'achievements',
            'streak',
            'levelInfo',
            'unlockedTrees'
        ));
    }

    /**
     * Start a new focus session
     */
    public function start(Request $request)
    {
        $user = Auth::user();

        // Check for existing active session
        $activeSession = FocusSession::getActiveSession($user->id);
        if ($activeSession) {
            return response()->json([
                'success' => false,
                'error' => 'You already have an active session',
                'session' => $activeSession,
            ], 400);
        }

        $request->validate([
            'duration' => 'required|integer|min:5|max:120',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:project_tasks,id',
            'tree_type' => 'nullable|string',
        ]);

        $settings = FocusSettings::getOrCreate($user->id);

        // Validate tree type is unlocked
        $treeType = $request->tree_type ?? $settings->preferred_tree;
        $treeInfo = FocusSession::$treeTypes[$treeType] ?? null;
        if (!$treeInfo || $settings->level < $treeInfo['level']) {
            $treeType = 'oak';
        }

        $session = FocusSession::create([
            'user_id' => $user->id,
            'project_id' => $request->project_id,
            'task_id' => $request->task_id,
            'planned_duration' => $request->duration,
            'tree_type' => $treeType,
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Update daily stats
        $todayStats = FocusDailyStats::getOrCreateToday($user->id);
        $todayStats->increment('total_sessions');

        return response()->json([
            'success' => true,
            'session' => $session->load(['project', 'task']),
            'tree_info' => $session->getTreeInfo(),
        ]);
    }

    /**
     * Update session progress (called periodically)
     */
    public function tick(Request $request, FocusSession $session)
    {
        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($session->status !== 'active') {
            return response()->json([
                'success' => false,
                'error' => 'Session is not active',
                'status' => $session->status,
            ]);
        }

        // Calculate actual duration
        $startTime = Carbon::parse($session->started_at);
        $elapsed = now()->diffInMinutes($startTime) - ($session->pause_duration / 60);
        $session->actual_duration = max(0, (int) $elapsed);
        $session->save();

        // Check if session should auto-complete
        $isComplete = $session->actual_duration >= $session->planned_duration;

        return response()->json([
            'success' => true,
            'actual_duration' => $session->actual_duration,
            'planned_duration' => $session->planned_duration,
            'progress' => $session->getProgressPercentage(),
            'should_complete' => $isComplete,
        ]);
    }

    /**
     * Complete a focus session
     */
    public function complete(Request $request, FocusSession $session)
    {
        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!in_array($session->status, ['active', 'paused'])) {
            return response()->json(['error' => 'Session cannot be completed'], 400);
        }

        // Calculate final duration
        $startTime = Carbon::parse($session->started_at);
        $elapsed = now()->diffInMinutes($startTime) - ($session->pause_duration / 60);
        $session->actual_duration = max(1, min((int) $elapsed, $session->planned_duration));
        $session->status = 'completed';
        $session->ended_at = now();
        $session->points_earned = $session->calculatePoints();
        $session->save();

        // Update settings
        $settings = FocusSettings::getOrCreate($user->id);
        $settings->addPoints($session->points_earned);
        $settings->increment('total_trees');

        // Update daily stats
        $todayStats = FocusDailyStats::getOrCreateToday($user->id);
        $todayStats->increment('completed_sessions');
        $todayStats->increment('total_focus_minutes', $session->actual_duration);
        $todayStats->increment('points_earned', $session->points_earned);
        $todayStats->current_streak = FocusDailyStats::calculateStreak($user->id);
        $todayStats->save();

        // Update longest streak
        if ($todayStats->current_streak > $settings->longest_streak) {
            $settings->longest_streak = $todayStats->current_streak;
            $settings->save();
        }

        // Auto-log to timesheet if task is linked
        if ($session->task_id && $session->project_id) {
            Timesheet::create([
                'project_id' => $session->project_id,
                'task_id' => $session->task_id,
                'date' => now()->toDateString(),
                'time' => sprintf('%02d:%02d', floor($session->actual_duration / 60), $session->actual_duration % 60),
                'description' => 'Focus session: ' . ($session->notes ?? 'Deep work'),
                'created_by' => $user->id,
            ]);
        }

        // Check for new achievements
        $newAchievements = FocusAchievement::checkAndAward($user->id);

        return response()->json([
            'success' => true,
            'session' => $session,
            'points_earned' => $session->points_earned,
            'total_points' => $settings->total_points,
            'level_info' => $settings->getLevelInfo(),
            'new_achievements' => $newAchievements,
            'streak' => $todayStats->current_streak,
        ]);
    }

    /**
     * Abandon a focus session
     */
    public function abandon(Request $request, FocusSession $session)
    {
        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!in_array($session->status, ['active', 'paused'])) {
            return response()->json(['error' => 'Session cannot be abandoned'], 400);
        }

        $session->status = 'abandoned';
        $session->ended_at = now();
        $session->points_earned = 0;
        $session->save();

        // Update daily stats
        $todayStats = FocusDailyStats::getOrCreateToday($user->id);
        $todayStats->increment('abandoned_sessions');

        return response()->json([
            'success' => true,
            'message' => 'Session abandoned. Your tree withered.',
        ]);
    }

    /**
     * Pause a focus session
     */
    public function pause(Request $request, FocusSession $session)
    {
        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($session->status !== 'active') {
            return response()->json(['error' => 'Session is not active'], 400);
        }

        $session->status = 'paused';
        $session->paused_at = now();
        $session->save();

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    /**
     * Resume a paused session
     */
    public function resume(Request $request, FocusSession $session)
    {
        $user = Auth::user();

        if ($session->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($session->status !== 'paused') {
            return response()->json(['error' => 'Session is not paused'], 400);
        }

        // Calculate pause duration
        if ($session->paused_at) {
            $pauseDuration = now()->diffInSeconds(Carbon::parse($session->paused_at));
            $session->pause_duration += $pauseDuration;
        }

        $session->status = 'active';
        $session->paused_at = null;
        $session->save();

        return response()->json([
            'success' => true,
            'session' => $session,
        ]);
    }

    /**
     * Get current active session status
     */
    public function status()
    {
        $user = Auth::user();
        $session = FocusSession::getActiveSession($user->id);

        if (!$session) {
            return response()->json([
                'has_active' => false,
            ]);
        }

        // Calculate remaining time
        $elapsed = 0;
        if ($session->status === 'active') {
            $startTime = Carbon::parse($session->started_at);
            $elapsed = now()->diffInSeconds($startTime) - $session->pause_duration;
        }

        $plannedSeconds = $session->planned_duration * 60;
        $remaining = max(0, $plannedSeconds - $elapsed);

        return response()->json([
            'has_active' => true,
            'session' => $session->load(['project', 'task']),
            'tree_info' => $session->getTreeInfo(),
            'elapsed_seconds' => (int) $elapsed,
            'remaining_seconds' => (int) $remaining,
            'progress' => min(100, ($elapsed / $plannedSeconds) * 100),
        ]);
    }

    /**
     * Update user settings
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'focus_duration' => 'integer|min:5|max:120',
            'short_break' => 'integer|min:1|max:30',
            'long_break' => 'integer|min:5|max:60',
            'sessions_until_long_break' => 'integer|min:2|max:10',
            'daily_goal' => 'integer|min:1|max:20',
            'auto_start_breaks' => 'boolean',
            'auto_start_focus' => 'boolean',
            'sound_enabled' => 'boolean',
            'notifications_enabled' => 'boolean',
            'preferred_tree' => 'string',
        ]);

        $settings = FocusSettings::getOrCreate($user->id);
        $settings->fill($request->only([
            'focus_duration',
            'short_break',
            'long_break',
            'sessions_until_long_break',
            'daily_goal',
            'auto_start_breaks',
            'auto_start_focus',
            'sound_enabled',
            'notifications_enabled',
            'preferred_tree',
        ]));
        $settings->save();

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Get tasks for a project (AJAX)
     */
    public function getProjectTasks(Project $project)
    {
        $user = Auth::user();

        $tasks = $project->tasks()
            ->where('is_complete', 0)
            ->where(function ($q) use ($user) {
                $q->where('assign_to', 'like', '%' . $user->id . '%')
                    ->orWhere('created_by', $user->id);
            })
            ->orderBy('priority_color', 'desc')
            ->get(['id', 'name', 'issue_key', 'priority']);

        return response()->json($tasks);
    }

    /**
     * Leaderboard
     */
    public function leaderboard()
    {
        // Weekly leaderboard
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $weekly = FocusDailyStats::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->selectRaw('user_id, SUM(total_focus_minutes) as total_minutes, SUM(completed_sessions) as total_sessions, SUM(points_earned) as total_points')
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->limit(10)
            ->with('user:id,name,avatar')
            ->get();

        // All-time top users
        $allTime = FocusSettings::orderByDesc('total_points')
            ->limit(10)
            ->with('user:id,name,avatar')
            ->get();

        return response()->json([
            'weekly' => $weekly,
            'all_time' => $allTime,
        ]);
    }

    /**
     * User's garden (all completed sessions as trees)
     */
    public function garden()
    {
        $user = Auth::user();

        $trees = FocusSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('ended_at', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'tree_type' => $session->tree_type,
                    'tree_info' => $session->getTreeInfo(),
                    'duration' => $session->actual_duration,
                    'date' => $session->ended_at->format('M d, Y'),
                    'task' => $session->task?->name,
                    'project' => $session->project?->project_name,
                ];
            });

        $settings = FocusSettings::getOrCreate($user->id);

        return view('focus.garden', [
            'trees' => $trees,
            'totalTrees' => $settings->total_trees,
            'levelInfo' => $settings->getLevelInfo(),
        ]);
    }
}
