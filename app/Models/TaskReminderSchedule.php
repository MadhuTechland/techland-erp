<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskReminderSchedule extends Model
{
    protected $fillable = [
        'type',
        'scheduled_time',
        'is_enabled',
        'include_weekends',
        'created_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'include_weekends' => 'boolean',
        'scheduled_time' => 'datetime:H:i',
    ];

    // Schedule type constants (same as template types)
    const TYPE_NO_TASK = 'no_task_assigned';
    const TYPE_IN_PROGRESS = 'in_progress_reminder';

    /**
     * Scope for a specific creator
     */
    public function scopeForCreator($query, $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    /**
     * Scope for enabled schedules
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Get default schedules
     */
    public static function getDefaultSchedules()
    {
        return [
            [
                'type' => self::TYPE_NO_TASK,
                'scheduled_time' => '10:30:00',
                'is_enabled' => true,
                'include_weekends' => false,
            ],
            [
                'type' => self::TYPE_IN_PROGRESS,
                'scheduled_time' => '18:30:00',
                'is_enabled' => true,
                'include_weekends' => false,
            ],
        ];
    }

    /**
     * Check if schedule should run today
     */
    public function shouldRunToday()
    {
        if (!$this->is_enabled) {
            return false;
        }

        // Check if it's a weekend
        $isWeekend = now()->isWeekend();
        if ($isWeekend && !$this->include_weekends) {
            return false;
        }

        return true;
    }

    /**
     * Check if it's time to run this schedule
     */
    public function isTimeToRun()
    {
        if (!$this->shouldRunToday()) {
            return false;
        }

        $scheduledTime = \Carbon\Carbon::parse($this->scheduled_time);
        $now = now();

        // Check if current time is within 5 minutes of scheduled time
        return $now->format('H:i') === $scheduledTime->format('H:i');
    }

    /**
     * Get formatted time for display
     */
    public function getFormattedTimeAttribute()
    {
        return \Carbon\Carbon::parse($this->scheduled_time)->format('g:i A');
    }

    /**
     * Seed default schedules for a company
     */
    public static function seedDefaultsForCompany($creatorId)
    {
        $defaults = self::getDefaultSchedules();

        foreach ($defaults as $schedule) {
            self::firstOrCreate(
                [
                    'type' => $schedule['type'],
                    'created_by' => $creatorId,
                ],
                [
                    'scheduled_time' => $schedule['scheduled_time'],
                    'is_enabled' => $schedule['is_enabled'],
                    'include_weekends' => $schedule['include_weekends'],
                ]
            );
        }
    }
}
