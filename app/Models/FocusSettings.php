<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FocusSettings extends Model
{
    protected $table = 'focus_settings';

    protected $fillable = [
        'user_id',
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
        'total_points',
        'total_trees',
        'level',
        'longest_streak',
    ];

    protected $casts = [
        'auto_start_breaks' => 'boolean',
        'auto_start_focus' => 'boolean',
        'sound_enabled' => 'boolean',
        'notifications_enabled' => 'boolean',
    ];

    // Level thresholds
    public static array $levels = [
        1 => ['name' => 'Seedling', 'points' => 0, 'icon' => '🌱'],
        2 => ['name' => 'Sprout', 'points' => 200, 'icon' => '🌿'],
        3 => ['name' => 'Sapling', 'points' => 500, 'icon' => '🌱'],
        4 => ['name' => 'Young Tree', 'points' => 1000, 'icon' => '🌳'],
        5 => ['name' => 'Tree', 'points' => 2000, 'icon' => '🌲'],
        6 => ['name' => 'Grove Keeper', 'points' => 3500, 'icon' => '🏕️'],
        7 => ['name' => 'Forest Guardian', 'points' => 5000, 'icon' => '🦉'],
        8 => ['name' => 'Nature Spirit', 'points' => 7500, 'icon' => '🧚'],
        9 => ['name' => 'Forest Sage', 'points' => 10000, 'icon' => '🧙'],
        10 => ['name' => 'Master Cultivator', 'points' => 15000, 'icon' => '👑'],
        15 => ['name' => 'Legendary Grower', 'points' => 30000, 'icon' => '⭐'],
        20 => ['name' => 'Forest Deity', 'points' => 50000, 'icon' => '🌟'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getOrCreate(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'focus_duration' => 25,
                'short_break' => 5,
                'long_break' => 15,
                'sessions_until_long_break' => 4,
                'daily_goal' => 8,
            ]
        );
    }

    public function getLevelInfo(): array
    {
        $currentLevel = 1;
        $nextLevel = null;
        $progress = 0;

        foreach (self::$levels as $level => $info) {
            if ($this->total_points >= $info['points']) {
                $currentLevel = $level;
            } else {
                $nextLevel = $info;
                break;
            }
        }

        $currentLevelInfo = self::$levels[$currentLevel];

        if ($nextLevel) {
            $pointsInLevel = $this->total_points - $currentLevelInfo['points'];
            $pointsNeeded = $nextLevel['points'] - $currentLevelInfo['points'];
            $progress = ($pointsInLevel / $pointsNeeded) * 100;
        } else {
            $progress = 100;
        }

        return [
            'level' => $currentLevel,
            'name' => $currentLevelInfo['name'],
            'icon' => $currentLevelInfo['icon'],
            'points' => $this->total_points,
            'next_level_points' => $nextLevel['points'] ?? null,
            'progress' => round($progress, 1),
        ];
    }

    public function addPoints(int $points): void
    {
        $this->total_points += $points;

        // Check for level up
        foreach (self::$levels as $level => $info) {
            if ($this->total_points >= $info['points']) {
                $this->level = $level;
            }
        }

        $this->save();
    }

    public function getUnlockedTrees(): array
    {
        $unlocked = [];
        foreach (FocusSession::$treeTypes as $key => $tree) {
            if ($this->level >= $tree['level']) {
                $unlocked[$key] = $tree;
            }
        }
        return $unlocked;
    }
}
