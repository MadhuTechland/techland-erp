<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ProjectTask;
use App\Models\TaskStage;
use App\Models\TaskReminderRecipient;
use App\Models\TaskReminderTemplate;
use App\Models\TaskReminderSchedule;
use App\Models\TaskReminderLog;
use App\Services\GoogleChatService;
use Illuminate\Support\Facades\Log;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-task-reminders
                            {type? : Type of reminder (no_task_assigned or in_progress_reminder)}
                            {--force : Force send regardless of schedule time}
                            {--dry-run : Preview without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send task reminders to eligible users via Google Chat';

    /**
     * Execute the console command.
     */
    public function handle(GoogleChatService $googleChat)
    {
        $type = $this->argument('type');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if (!$googleChat->isConfigured()) {
            $this->error('Google Chat webhook is not configured!');
            Log::warning('Task reminders: Google Chat webhook not configured');
            return 1;
        }

        // Get all company users (creators)
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->warn('No company users found.');
            return 0;
        }

        $totalSent = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($companies as $company) {
            $creatorId = $company->id;

            $this->info("Processing company: {$company->name} (ID: {$creatorId})");

            // Seed defaults if not exists
            TaskReminderTemplate::seedDefaultsForCompany($creatorId);
            TaskReminderSchedule::seedDefaultsForCompany($creatorId);

            // Determine which types to process
            $typesToProcess = [];
            if ($type) {
                $typesToProcess[] = $type;
            } else {
                $typesToProcess = [
                    TaskReminderSchedule::TYPE_NO_TASK,
                    TaskReminderSchedule::TYPE_IN_PROGRESS,
                ];
            }

            foreach ($typesToProcess as $reminderType) {
                // Check schedule
                $schedule = TaskReminderSchedule::forCreator($creatorId)
                    ->where('type', $reminderType)
                    ->first();

                if (!$schedule) {
                    $this->line("  - No schedule found for {$reminderType}");
                    continue;
                }

                if (!$force && !$schedule->isTimeToRun()) {
                    $this->line("  - Not time to run {$reminderType} (scheduled: {$schedule->formatted_time})");
                    continue;
                }

                if (!$schedule->shouldRunToday()) {
                    $this->line("  - {$reminderType} should not run today (weekend/disabled)");
                    continue;
                }

                // Get template
                $template = TaskReminderTemplate::forCreator($creatorId)
                    ->where('type', $reminderType)
                    ->active()
                    ->first();

                if (!$template) {
                    $this->warn("  - No active template found for {$reminderType}");
                    continue;
                }

                // Get eligible users
                $eligibleUsers = TaskReminderRecipient::getEligibleUsers($creatorId);

                $this->info("  Processing {$reminderType} for {$eligibleUsers->count()} eligible users...");

                foreach ($eligibleUsers as $user) {
                    // Check if already sent today
                    if (TaskReminderLog::wasAlreadySentToday($user->id, $reminderType, $creatorId)) {
                        $totalSkipped++;
                        continue;
                    }

                    // Check conditions based on type
                    $shouldSend = false;
                    $taskData = ['count' => 0, 'tasks' => collect(), 'task_ids' => []];

                    if ($reminderType === TaskReminderSchedule::TYPE_NO_TASK) {
                        $shouldSend = $this->checkNoTaskAssigned($user, $creatorId);
                    } else {
                        $result = $this->checkInProgressTasks($user, $creatorId);
                        $shouldSend = $result['should_send'];
                        $taskData = $result['task_data'];
                    }

                    if (!$shouldSend) {
                        continue;
                    }

                    // Parse message
                    $message = $template->parseTemplate($user, $taskData);

                    if ($dryRun) {
                        $this->line("  [DRY RUN] Would send to {$user->name}:");
                        $this->line("    " . str_replace("\n", "\n    ", $message));
                        $totalSent++;
                        continue;
                    }

                    // Send message
                    $chatMessage = ['text' => $message];
                    $sent = $googleChat->sendMessage($chatMessage);

                    if ($sent) {
                        // Log the reminder
                        TaskReminderLog::logReminder(
                            $user->id,
                            $reminderType,
                            $message,
                            $taskData['count'],
                            $taskData['task_ids'],
                            $creatorId
                        );

                        $this->info("    Sent reminder to {$user->name}");
                        $totalSent++;
                    } else {
                        $this->error("    Failed to send to {$user->name}");
                        $totalErrors++;
                    }

                    // Small delay to avoid rate limiting
                    usleep(500000); // 0.5 seconds
                }
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  - Sent: {$totalSent}");
        $this->info("  - Skipped (already sent): {$totalSkipped}");
        $this->info("  - Errors: {$totalErrors}");

        return 0;
    }

    /**
     * Check if user has no tasks in "In Progress" stage today
     */
    private function checkNoTaskAssigned(User $user, $creatorId): bool
    {
        // Find "In Progress" stage
        $inProgressStage = TaskStage::where('name', 'In Progress')
            ->where(function ($q) use ($creatorId) {
                $q->where('created_by', $creatorId)
                    ->orWhere('created_by', 0);
            })
            ->first();

        if (!$inProgressStage) {
            // If no In Progress stage exists, consider user has no tasks in progress
            return true;
        }

        // Check if user has any tasks in "In Progress" stage
        $taskCount = ProjectTask::where('created_by', $creatorId)
            ->where('stage_id', $inProgressStage->id)
            ->where(function ($query) use ($user) {
                $query->where('assign_to', $user->id)
                    ->orWhere('assign_to', 'LIKE', $user->id . ',%')
                    ->orWhere('assign_to', 'LIKE', '%,' . $user->id . ',%')
                    ->orWhere('assign_to', 'LIKE', '%,' . $user->id);
            })
            ->where('is_complete', 0)
            ->count();

        // Return true if user has NO tasks in progress (should send reminder)
        return $taskCount === 0;
    }

    /**
     * Check if user has tasks in progress
     */
    private function checkInProgressTasks(User $user, $creatorId): array
    {
        // Find "In Progress" stage
        $inProgressStage = TaskStage::where('name', 'In Progress')
            ->where(function ($q) use ($creatorId) {
                $q->where('created_by', $creatorId)
                    ->orWhere('created_by', 0);
            })
            ->first();

        if (!$inProgressStage) {
            return ['should_send' => false, 'task_data' => ['count' => 0, 'tasks' => collect(), 'task_ids' => []]];
        }

        // Get tasks in progress for this user
        $tasks = ProjectTask::where('created_by', $creatorId)
            ->where('stage_id', $inProgressStage->id)
            ->where(function ($query) use ($user) {
                $query->where('assign_to', $user->id)
                    ->orWhere('assign_to', 'LIKE', $user->id . ',%')
                    ->orWhere('assign_to', 'LIKE', '%,' . $user->id . ',%')
                    ->orWhere('assign_to', 'LIKE', '%,' . $user->id);
            })
            ->where('is_complete', 0)
            ->get();

        $shouldSend = $tasks->count() > 0;

        return [
            'should_send' => $shouldSend,
            'task_data' => [
                'count' => $tasks->count(),
                'tasks' => $tasks,
                'task_ids' => $tasks->pluck('id')->toArray(),
            ],
        ];
    }
}
