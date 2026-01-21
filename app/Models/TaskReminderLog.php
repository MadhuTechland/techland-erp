<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskReminderLog extends Model
{
    protected $fillable = [
        'type',
        'user_id',
        'reminder_date',
        'message_sent',
        'response_received',
        'response_message',
        'response_at',
        'task_count',
        'task_ids',
        'created_by',
    ];

    protected $casts = [
        'response_received' => 'boolean',
        'reminder_date' => 'date',
        'response_at' => 'datetime',
        'task_ids' => 'array',
    ];

    // Type constants
    const TYPE_NO_TASK = 'no_task_assigned';
    const TYPE_IN_PROGRESS = 'in_progress_reminder';

    /**
     * Get the user who received the reminder
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for a specific creator
     */
    public function scopeForCreator($query, $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    /**
     * Scope for today's reminders
     */
    public function scopeToday($query)
    {
        return $query->where('reminder_date', now()->toDateString());
    }

    /**
     * Scope for reminders awaiting response
     */
    public function scopeAwaitingResponse($query)
    {
        return $query->where('response_received', false);
    }

    /**
     * Scope for reminders with response
     */
    public function scopeWithResponse($query)
    {
        return $query->where('response_received', true);
    }

    /**
     * Check if a reminder was already sent to user today
     */
    public static function wasAlreadySentToday($userId, $type, $creatorId)
    {
        return self::where('user_id', $userId)
            ->where('type', $type)
            ->where('reminder_date', now()->toDateString())
            ->where('created_by', $creatorId)
            ->exists();
    }

    /**
     * Log a sent reminder
     */
    public static function logReminder($userId, $type, $message, $taskCount, $taskIds, $creatorId)
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'reminder_date' => now()->toDateString(),
            'message_sent' => $message,
            'task_count' => $taskCount,
            'task_ids' => $taskIds,
            'created_by' => $creatorId,
        ]);
    }

    /**
     * Record a response to a reminder
     */
    public function recordResponse($message)
    {
        $this->update([
            'response_received' => true,
            'response_message' => $message,
            'response_at' => now(),
        ]);
    }

    /**
     * Get statistics for a date range
     */
    public static function getStatistics($creatorId, $startDate = null, $endDate = null)
    {
        $query = self::forCreator($creatorId);

        if ($startDate) {
            $query->where('reminder_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('reminder_date', '<=', $endDate);
        }

        $total = $query->count();
        $withResponse = (clone $query)->withResponse()->count();
        $noTaskReminders = (clone $query)->where('type', self::TYPE_NO_TASK)->count();
        $inProgressReminders = (clone $query)->where('type', self::TYPE_IN_PROGRESS)->count();

        return [
            'total_sent' => $total,
            'responses_received' => $withResponse,
            'response_rate' => $total > 0 ? round(($withResponse / $total) * 100, 1) : 0,
            'no_task_reminders' => $noTaskReminders,
            'in_progress_reminders' => $inProgressReminders,
        ];
    }
}
