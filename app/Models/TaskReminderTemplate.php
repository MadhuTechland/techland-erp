<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskReminderTemplate extends Model
{
    protected $fillable = [
        'type',
        'name',
        'message_template',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Template type constants
    const TYPE_NO_TASK = 'no_task_assigned';
    const TYPE_IN_PROGRESS = 'in_progress_reminder';

    // Available variables for templates
    public static $availableVariables = [
        '{Name}' => 'User\'s full name',
        '{FirstName}' => 'User\'s first name',
        '{Date}' => 'Current date',
        '{Day}' => 'Day of the week',
        '{TaskCount}' => 'Number of tasks in progress',
        '{TaskNames}' => 'List of task names',
        '{Time}' => 'Current time',
    ];

    /**
     * Scope for a specific creator
     */
    public function scopeForCreator($query, $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get default templates
     */
    public static function getDefaultTemplates()
    {
        return [
            [
                'type' => self::TYPE_NO_TASK,
                'name' => 'No Task Assigned - Morning Check',
                'message_template' => "Good morning {Name}! 👋\n\nWe noticed you don't have any tasks assigned for today ({Date}).\n\nIf you're available and looking for something to work on, please reach out to your team lead or check the backlog for any tasks you can pick up.\n\nHave a productive day! 🌟",
            ],
            [
                'type' => self::TYPE_IN_PROGRESS,
                'name' => 'In Progress Tasks - Evening Check',
                'message_template' => "Hi {Name}! 👋\n\nJust a friendly check-in! We noticed you have {TaskCount} task(s) still marked as \"In Progress\":\n\n{TaskNames}\n\nCould you please share a quick update on these? No pressure - just helps us keep track of progress and see if you need any support.\n\nThanks for your hard work today! 💪",
            ],
        ];
    }

    /**
     * Parse template with variables
     */
    public function parseTemplate(User $user, array $taskData = [])
    {
        $message = $this->message_template;

        // Get user's first name
        $nameParts = explode(' ', $user->name);
        $firstName = $nameParts[0] ?? $user->name;

        // Build task names list
        $taskNames = '';
        if (!empty($taskData['tasks'])) {
            foreach ($taskData['tasks'] as $index => $task) {
                $taskNames .= "• " . $task->name . "\n";
            }
        }

        // Replace variables
        $replacements = [
            '{Name}' => $user->name,
            '{FirstName}' => $firstName,
            '{Date}' => now()->format('l, F j, Y'),
            '{Day}' => now()->format('l'),
            '{TaskCount}' => $taskData['count'] ?? 0,
            '{TaskNames}' => $taskNames ?: 'No tasks',
            '{Time}' => now()->format('g:i A'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Seed default templates for a company
     */
    public static function seedDefaultsForCompany($creatorId)
    {
        $defaults = self::getDefaultTemplates();

        foreach ($defaults as $template) {
            self::firstOrCreate(
                [
                    'type' => $template['type'],
                    'created_by' => $creatorId,
                ],
                [
                    'name' => $template['name'],
                    'message_template' => $template['message_template'],
                    'is_active' => true,
                ]
            );
        }
    }
}
