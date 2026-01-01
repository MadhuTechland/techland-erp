<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FocusSession extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'planned_duration',
        'actual_duration',
        'status',
        'tree_type',
        'points_earned',
        'notes',
        'started_at',
        'ended_at',
        'paused_at',
        'pause_duration',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'paused_at' => 'datetime',
    ];

    // Tree types with their unlock levels and icons
    public static array $treeTypes = [
        'oak' => ['name' => 'Oak Tree', 'level' => 1, 'icon' => '🌳', 'color' => '#228B22'],
        'pine' => ['name' => 'Pine Tree', 'level' => 2, 'icon' => '🌲', 'color' => '#2E8B57'],
        'cherry' => ['name' => 'Cherry Blossom', 'level' => 3, 'icon' => '🌸', 'color' => '#FFB7C5'],
        'maple' => ['name' => 'Maple Tree', 'level' => 4, 'icon' => '🍁', 'color' => '#FF6347'],
        'palm' => ['name' => 'Palm Tree', 'level' => 5, 'icon' => '🌴', 'color' => '#20B2AA'],
        'bamboo' => ['name' => 'Bamboo', 'level' => 6, 'icon' => '🎋', 'color' => '#9ACD32'],
        'cactus' => ['name' => 'Cactus', 'level' => 7, 'icon' => '🌵', 'color' => '#3CB371'],
        'bonsai' => ['name' => 'Bonsai', 'level' => 8, 'icon' => '🎍', 'color' => '#6B8E23'],
        'crystal' => ['name' => 'Crystal Tree', 'level' => 10, 'icon' => '💎', 'color' => '#E0FFFF'],
        'golden' => ['name' => 'Golden Tree', 'level' => 15, 'icon' => '✨', 'color' => '#FFD700'],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function getTreeInfo(): array
    {
        return self::$treeTypes[$this->tree_type] ?? self::$treeTypes['oak'];
    }

    public function calculatePoints(): int
    {
        if ($this->status !== 'completed') {
            return 0;
        }

        $basePoints = $this->actual_duration; // 1 point per minute

        // Bonus for completing full session
        if ($this->actual_duration >= $this->planned_duration) {
            $basePoints += 10;
        }

        // Bonus for longer sessions
        if ($this->planned_duration >= 50) {
            $basePoints += 15;
        }

        // Bonus for linking to a task
        if ($this->task_id) {
            $basePoints += 5;
        }

        return $basePoints;
    }

    public function getProgressPercentage(): float
    {
        if ($this->planned_duration <= 0) return 0;
        return min(100, ($this->actual_duration / $this->planned_duration) * 100);
    }

    public static function getActiveSession(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->whereIn('status', ['active', 'paused'])
            ->latest()
            ->first();
    }
}
