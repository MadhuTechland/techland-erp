<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FocusAchievement extends Model
{
    protected $fillable = [
        'user_id',
        'achievement_key',
        'achievement_name',
        'description',
        'icon',
        'points_awarded',
        'earned_at',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
    ];

    // Achievement definitions
    public static array $achievements = [
        'first_tree' => [
            'name' => 'First Tree',
            'description' => 'Complete your first focus session',
            'icon' => '🌱',
            'points' => 50,
        ],
        'five_trees' => [
            'name' => 'Small Garden',
            'description' => 'Grow 5 trees',
            'icon' => '🌿',
            'points' => 100,
        ],
        'twenty_five_trees' => [
            'name' => 'Growing Forest',
            'description' => 'Grow 25 trees',
            'icon' => '🌳',
            'points' => 250,
        ],
        'hundred_trees' => [
            'name' => 'Forest Keeper',
            'description' => 'Grow 100 trees',
            'icon' => '🏕️',
            'points' => 500,
        ],
        'three_day_streak' => [
            'name' => 'Getting Started',
            'description' => 'Maintain a 3-day focus streak',
            'icon' => '🔥',
            'points' => 75,
        ],
        'week_streak' => [
            'name' => 'Week Warrior',
            'description' => 'Maintain a 7-day focus streak',
            'icon' => '⚡',
            'points' => 200,
        ],
        'month_streak' => [
            'name' => 'Unstoppable',
            'description' => 'Maintain a 30-day focus streak',
            'icon' => '💪',
            'points' => 1000,
        ],
        'early_bird' => [
            'name' => 'Early Bird',
            'description' => 'Complete 5 sessions before 9 AM',
            'icon' => '🌅',
            'points' => 150,
        ],
        'night_owl' => [
            'name' => 'Night Owl',
            'description' => 'Complete 5 sessions after 8 PM',
            'icon' => '🦉',
            'points' => 150,
        ],
        'marathon' => [
            'name' => 'Marathon Focus',
            'description' => 'Complete 8 sessions in one day',
            'icon' => '🏃',
            'points' => 300,
        ],
        'perfect_week' => [
            'name' => 'Perfect Week',
            'description' => 'Complete daily goal for 7 days straight',
            'icon' => '🌟',
            'points' => 500,
        ],
        'no_abandon' => [
            'name' => 'Committed',
            'description' => 'Complete 20 sessions without abandoning',
            'icon' => '🎯',
            'points' => 200,
        ],
        'task_master' => [
            'name' => 'Task Master',
            'description' => 'Link 50 focus sessions to tasks',
            'icon' => '📋',
            'points' => 250,
        ],
        'hour_focus' => [
            'name' => 'Deep Focus',
            'description' => 'Complete a 60-minute focus session',
            'icon' => '🧘',
            'points' => 100,
        ],
        'thousand_minutes' => [
            'name' => 'Time Master',
            'description' => 'Accumulate 1000 focus minutes',
            'icon' => '⏰',
            'points' => 400,
        ],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function award(int $userId, string $achievementKey): ?self
    {
        // Check if already earned
        $existing = self::where('user_id', $userId)
            ->where('achievement_key', $achievementKey)
            ->first();

        if ($existing) {
            return null;
        }

        $definition = self::$achievements[$achievementKey] ?? null;
        if (!$definition) {
            return null;
        }

        $achievement = self::create([
            'user_id' => $userId,
            'achievement_key' => $achievementKey,
            'achievement_name' => $definition['name'],
            'description' => $definition['description'],
            'icon' => $definition['icon'],
            'points_awarded' => $definition['points'],
            'earned_at' => now(),
        ]);

        // Add points to user settings
        $settings = FocusSettings::getOrCreate($userId);
        $settings->addPoints($definition['points']);

        return $achievement;
    }

    public static function checkAndAward(int $userId): array
    {
        $awarded = [];
        $settings = FocusSettings::getOrCreate($userId);
        $completedSessions = FocusSession::where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        // Tree count achievements
        if ($completedSessions >= 1) {
            if ($achievement = self::award($userId, 'first_tree')) {
                $awarded[] = $achievement;
            }
        }
        if ($completedSessions >= 5) {
            if ($achievement = self::award($userId, 'five_trees')) {
                $awarded[] = $achievement;
            }
        }
        if ($completedSessions >= 25) {
            if ($achievement = self::award($userId, 'twenty_five_trees')) {
                $awarded[] = $achievement;
            }
        }
        if ($completedSessions >= 100) {
            if ($achievement = self::award($userId, 'hundred_trees')) {
                $awarded[] = $achievement;
            }
        }

        // Streak achievements
        $streak = FocusDailyStats::calculateStreak($userId);
        if ($streak >= 3) {
            if ($achievement = self::award($userId, 'three_day_streak')) {
                $awarded[] = $achievement;
            }
        }
        if ($streak >= 7) {
            if ($achievement = self::award($userId, 'week_streak')) {
                $awarded[] = $achievement;
            }
        }
        if ($streak >= 30) {
            if ($achievement = self::award($userId, 'month_streak')) {
                $awarded[] = $achievement;
            }
        }

        // Total minutes achievement
        $totalMinutes = FocusSession::where('user_id', $userId)
            ->where('status', 'completed')
            ->sum('actual_duration');

        if ($totalMinutes >= 1000) {
            if ($achievement = self::award($userId, 'thousand_minutes')) {
                $awarded[] = $achievement;
            }
        }

        // Task-linked sessions
        $taskLinkedSessions = FocusSession::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereNotNull('task_id')
            ->count();

        if ($taskLinkedSessions >= 50) {
            if ($achievement = self::award($userId, 'task_master')) {
                $awarded[] = $achievement;
            }
        }

        return $awarded;
    }
}
