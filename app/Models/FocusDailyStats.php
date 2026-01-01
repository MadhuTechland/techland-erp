<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FocusDailyStats extends Model
{
    protected $table = 'focus_daily_stats';

    protected $fillable = [
        'user_id',
        'date',
        'total_sessions',
        'completed_sessions',
        'abandoned_sessions',
        'total_focus_minutes',
        'points_earned',
        'current_streak',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getOrCreateToday(int $userId): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'date' => Carbon::today(),
            ],
            [
                'total_sessions' => 0,
                'completed_sessions' => 0,
                'abandoned_sessions' => 0,
                'total_focus_minutes' => 0,
                'points_earned' => 0,
                'current_streak' => 0,
            ]
        );
    }

    public static function calculateStreak(int $userId): int
    {
        $streak = 0;
        $date = Carbon::today();

        while (true) {
            $stats = self::where('user_id', $userId)
                ->where('date', $date)
                ->where('completed_sessions', '>', 0)
                ->first();

            if ($stats) {
                $streak++;
                $date = $date->subDay();
            } else {
                // Allow skipping today if no sessions yet
                if ($streak === 0 && $date->isToday()) {
                    $date = $date->subDay();
                    continue;
                }
                break;
            }
        }

        return $streak;
    }

    public static function getWeeklyStats(int $userId): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $stats = self::where('user_id', $userId)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->get();

        $dailyData = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $dayStat = $stats->firstWhere('date', $date->toDateString());

            $dailyData[] = [
                'day' => $date->format('D'),
                'date' => $date->toDateString(),
                'sessions' => $dayStat->completed_sessions ?? 0,
                'minutes' => $dayStat->total_focus_minutes ?? 0,
                'points' => $dayStat->points_earned ?? 0,
            ];
        }

        return [
            'daily' => $dailyData,
            'total_sessions' => $stats->sum('completed_sessions'),
            'total_minutes' => $stats->sum('total_focus_minutes'),
            'total_points' => $stats->sum('points_earned'),
        ];
    }
}
