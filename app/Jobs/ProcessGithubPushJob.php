<?php

namespace App\Jobs;

use App\Models\GithubCommit;
use App\Models\GithubUserMapping;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessGithubPushJob
 *
 * Processes GitHub push webhook payloads asynchronously.
 * This job extracts commit data and stores it for productivity tracking.
 *
 * Processing Rules:
 * - Ignores merge commits
 * - Ignores commits with < 10 total lines changed
 * - Prevents duplicate commits using SHA
 * - Links commits to ERP users if mapping exists
 */
class ProcessGithubPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Minimum lines changed threshold to store a commit.
     */
    protected const MIN_LINES_CHANGED = 10;

    /**
     * The webhook payload.
     */
    protected array $payload;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $repoName = $this->payload['repository']['full_name'] ?? 'unknown';

        Log::info("Processing GitHub push for repository: {$repoName}", [
            'commits_count' => count($this->payload['commits'] ?? []),
        ]);

        $commits = $this->payload['commits'] ?? [];

        if (empty($commits)) {
            Log::info("No commits to process in push event for {$repoName}");
            return;
        }

        $processed = 0;
        $skipped = 0;
        $duplicates = 0;

        DB::beginTransaction();

        try {
            foreach ($commits as $commit) {
                $result = $this->processCommit($commit, $repoName);

                match ($result) {
                    'processed' => $processed++,
                    'skipped' => $skipped++,
                    'duplicate' => $duplicates++,
                    default => null,
                };
            }

            DB::commit();

            Log::info("GitHub push processing completed for {$repoName}", [
                'processed' => $processed,
                'skipped' => $skipped,
                'duplicates' => $duplicates,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to process GitHub push for {$repoName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process a single commit.
     *
     * @param array $commit
     * @param string $repoName
     * @return string Result status: 'processed', 'skipped', or 'duplicate'
     */
    protected function processCommit(array $commit, string $repoName): string
    {
        $sha = $commit['id'] ?? null;
        $message = $commit['message'] ?? '';

        // Skip if no SHA
        if (!$sha) {
            Log::warning("Skipping commit without SHA", ['repo' => $repoName]);
            return 'skipped';
        }

        // Skip merge commits (messages typically start with "Merge")
        if ($this->isMergeCommit($message)) {
            Log::debug("Skipping merge commit", ['sha' => $sha, 'repo' => $repoName]);
            return 'skipped';
        }

        // Check for duplicate
        if (GithubCommit::existsBySha($sha)) {
            Log::debug("Skipping duplicate commit", ['sha' => $sha, 'repo' => $repoName]);
            return 'duplicate';
        }

        // Extract commit stats
        $filesChanged = count($commit['added'] ?? [])
            + count($commit['modified'] ?? [])
            + count($commit['removed'] ?? []);

        // Note: GitHub push event doesn't include line stats directly
        // We estimate based on files changed, or you could make an API call
        // For now, we use a placeholder. Real implementation might need API call.
        $linesAdded = $this->estimateLinesFromFiles($commit);
        $linesDeleted = $this->estimateLinesDeletedFromFiles($commit);

        $totalLines = $linesAdded + $linesDeleted;

        // Skip commits with minimal changes
        if ($totalLines < self::MIN_LINES_CHANGED) {
            Log::debug("Skipping commit with minimal changes", [
                'sha' => $sha,
                'total_lines' => $totalLines,
            ]);
            return 'skipped';
        }

        // Extract author information
        $githubUsername = $commit['author']['username']
            ?? $commit['author']['name']
            ?? 'unknown';

        // Parse commit timestamp
        $committedAt = isset($commit['timestamp'])
            ? Carbon::parse($commit['timestamp'])
            : now();

        // Check for ERP user mapping
        $userId = GithubUserMapping::getUserIdByGithubUsername($githubUsername);

        // Create the commit record
        GithubCommit::create([
            'github_username' => $githubUsername,
            'repo_name' => $repoName,
            'commit_sha' => $sha,
            'commit_message' => mb_substr($message, 0, 65535), // Text column limit
            'files_changed' => $filesChanged,
            'lines_added' => $linesAdded,
            'lines_deleted' => $linesDeleted,
            'committed_at' => $committedAt,
            'user_id' => $userId,
        ]);

        Log::info("Processed commit", [
            'sha' => substr($sha, 0, 7),
            'author' => $githubUsername,
            'repo' => $repoName,
            'files' => $filesChanged,
            'lines' => $totalLines,
        ]);

        return 'processed';
    }

    /**
     * Check if a commit is a merge commit.
     */
    protected function isMergeCommit(string $message): bool
    {
        $mergePatterns = [
            '/^Merge\s+/i',
            '/^Merge branch\s+/i',
            '/^Merge pull request\s+/i',
            '/^Merged\s+/i',
        ];

        foreach ($mergePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Estimate lines added based on files in commit.
     *
     * Note: GitHub push events don't include line stats.
     * This is an estimation. For accurate stats, you would need
     * to call the GitHub API for each commit.
     *
     * Assumption: Average 20 lines per added/modified file
     */
    protected function estimateLinesFromFiles(array $commit): int
    {
        $addedFiles = count($commit['added'] ?? []);
        $modifiedFiles = count($commit['modified'] ?? []);

        // Estimation: new files avg 50 lines, modified files avg 15 lines
        return ($addedFiles * 50) + ($modifiedFiles * 15);
    }

    /**
     * Estimate lines deleted based on files in commit.
     *
     * Assumption: Removed files average 30 lines, modified deletions avg 5 lines
     */
    protected function estimateLinesDeletedFromFiles(array $commit): int
    {
        $removedFiles = count($commit['removed'] ?? []);
        $modifiedFiles = count($commit['modified'] ?? []);

        return ($removedFiles * 30) + ($modifiedFiles * 5);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $repoName = $this->payload['repository']['full_name'] ?? 'unknown';

        Log::error("GitHub push job failed for {$repoName}", [
            'error' => $exception->getMessage(),
            'payload_size' => strlen(json_encode($this->payload)),
        ]);
    }
}
