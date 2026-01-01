<?php

namespace App\Http\Controllers;

use App\Models\CodeReview;
use App\Models\GithubUserMapping;
use App\Services\ClaudeCodeReviewService;
use App\Services\GithubApiService;
use App\Services\GoogleChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GitHubWebhookController extends Controller
{
    protected ClaudeCodeReviewService $claudeService;
    protected GoogleChatService $chatService;
    protected GithubApiService $githubService;

    public function __construct(
        ClaudeCodeReviewService $claudeService,
        GoogleChatService $chatService,
        GithubApiService $githubService
    ) {
        $this->claudeService = $claudeService;
        $this->chatService = $chatService;
        $this->githubService = $githubService;
    }

    /**
     * Handle GitHub webhook events
     */
    public function handleWebhook(Request $request)
    {
        $event = $request->header('X-GitHub-Event');
        $payload = $request->all();

        Log::info("GitHub webhook received: {$event}");

        // Verify webhook signature if secret is configured
        $secret = env('GITHUB_WEBHOOK_SECRET');
        if ($secret) {
            $signature = $request->header('X-Hub-Signature-256');
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

            if (!hash_equals($expectedSignature, $signature ?? '')) {
                Log::warning('GitHub webhook signature verification failed');
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        switch ($event) {
            case 'push':
                return $this->handlePushEvent($payload);

            case 'pull_request':
                return $this->handlePullRequestEvent($payload);

            case 'ping':
                return response()->json(['message' => 'pong']);

            default:
                return response()->json(['message' => "Event '{$event}' received but not processed"]);
        }
    }

    /**
     * Handle push events - trigger AI code review
     */
    protected function handlePushEvent(array $payload)
    {
        $repoFullName = $payload['repository']['full_name'] ?? '';
        $repoName = $payload['repository']['name'] ?? '';
        $branch = str_replace('refs/heads/', '', $payload['ref'] ?? '');
        $commits = $payload['commits'] ?? [];

        // Skip if no commits
        if (empty($commits)) {
            return response()->json(['message' => 'No commits to review']);
        }

        // Get the head commit (most recent)
        $headCommit = $payload['head_commit'] ?? $commits[count($commits) - 1] ?? null;

        if (!$headCommit) {
            return response()->json(['message' => 'No head commit found']);
        }

        $commitSha = $headCommit['id'] ?? '';
        $commitMessage = $headCommit['message'] ?? '';
        $authorUsername = $headCommit['author']['username'] ?? $headCommit['author']['name'] ?? 'unknown';
        $authorEmail = $headCommit['author']['email'] ?? null;

        // Skip merge commits
        if (str_starts_with(strtolower($commitMessage), 'merge')) {
            return response()->json(['message' => 'Skipping merge commit']);
        }

        // Check if already reviewed
        if (CodeReview::where('commit_sha', $commitSha)->exists()) {
            return response()->json(['message' => 'Commit already reviewed']);
        }

        // Fetch commit diff from GitHub API
        $commitDetails = $this->githubService->fetchCommitDetails($repoFullName, $commitSha);

        if (!$commitDetails) {
            Log::error("Failed to fetch commit details for {$commitSha}");
            return response()->json(['error' => 'Failed to fetch commit details'], 500);
        }

        // Build diff from files
        $diff = $this->buildDiffFromFiles($commitDetails['files'] ?? []);
        $filesChanged = count($commitDetails['files'] ?? []);
        $linesAdded = $commitDetails['stats']['additions'] ?? 0;
        $linesDeleted = $commitDetails['stats']['deletions'] ?? 0;

        // Skip very small changes
        if ($linesAdded + $linesDeleted < 5) {
            return response()->json(['message' => 'Skipping trivial change']);
        }

        // Find mapped user
        $mapping = GithubUserMapping::whereRaw('LOWER(github_username) = ?', [strtolower($authorUsername)])->first();

        // Create and process the review
        $review = $this->claudeService->createReview([
            'repo_name' => $repoName,
            'branch' => $branch,
            'commit_sha' => $commitSha,
            'commit_message' => $commitMessage,
            'author_username' => $authorUsername,
            'author_email' => $authorEmail,
            'user_id' => $mapping?->user_id,
            'diff' => $diff,
            'files_changed' => $filesChanged,
            'lines_added' => $linesAdded,
            'lines_deleted' => $linesDeleted,
        ]);

        if (!$review) {
            return response()->json(['error' => 'Failed to create review'], 500);
        }

        // Send to Google Chat
        if ($review->status === 'completed') {
            $sent = $this->chatService->sendCodeReviewNotification($review);
            $review->update(['sent_to_chat' => $sent]);
        }

        return response()->json([
            'message' => 'Review completed',
            'review_id' => $review->id,
            'issues_found' => $review->issues_count,
        ]);
    }

    /**
     * Handle pull request events
     */
    protected function handlePullRequestEvent(array $payload)
    {
        $action = $payload['action'] ?? '';

        // Only review on opened or synchronize (new commits pushed)
        if (!in_array($action, ['opened', 'synchronize'])) {
            return response()->json(['message' => "PR action '{$action}' not processed"]);
        }

        Log::info('Pull request event received', [
            'action' => $action,
            'pr_number' => $payload['pull_request']['number'] ?? '',
            'repo' => $payload['repository']['full_name'] ?? '',
        ]);

        return response()->json(['message' => 'PR event logged']);
    }

    /**
     * Build diff string from commit files
     */
    protected function buildDiffFromFiles(array $files): string
    {
        $diff = '';

        foreach ($files as $file) {
            $filename = $file['filename'] ?? '';
            $status = $file['status'] ?? '';
            $patch = $file['patch'] ?? '';

            if (empty($patch)) {
                continue;
            }

            $diff .= "\n--- a/{$filename}\n";
            $diff .= "+++ b/{$filename}\n";
            $diff .= "Status: {$status}\n";
            $diff .= $patch . "\n";
        }

        // Limit diff size to avoid token limits
        $maxSize = 15000;
        if (strlen($diff) > $maxSize) {
            $diff = substr($diff, 0, $maxSize) . "\n\n... [diff truncated due to size] ...";
        }

        return $diff;
    }

    /**
     * Manual trigger for testing
     */
    public function testReview(Request $request)
    {
        $repoFullName = $request->input('repo', 'MadhuTechland/Zenfoo-Admin-Panel');
        $commitSha = $request->input('sha');

        if (!$commitSha) {
            return response()->json(['error' => 'Commit SHA required'], 400);
        }

        $commitDetails = $this->githubService->fetchCommitDetails($repoFullName, $commitSha);

        if (!$commitDetails) {
            return response()->json(['error' => 'Failed to fetch commit'], 404);
        }

        $repoName = explode('/', $repoFullName)[1] ?? $repoFullName;
        $authorUsername = $commitDetails['author']['login'] ?? $commitDetails['commit']['author']['name'] ?? 'unknown';
        $authorEmail = $commitDetails['commit']['author']['email'] ?? null;

        $diff = $this->buildDiffFromFiles($commitDetails['files'] ?? []);

        $mapping = GithubUserMapping::whereRaw('LOWER(github_username) = ?', [strtolower($authorUsername)])->first();

        $review = $this->claudeService->createReview([
            'repo_name' => $repoName,
            'branch' => 'manual-test',
            'commit_sha' => $commitSha,
            'commit_message' => $commitDetails['commit']['message'] ?? '',
            'author_username' => $authorUsername,
            'author_email' => $authorEmail,
            'user_id' => $mapping?->user_id,
            'diff' => $diff,
            'files_changed' => count($commitDetails['files'] ?? []),
            'lines_added' => $commitDetails['stats']['additions'] ?? 0,
            'lines_deleted' => $commitDetails['stats']['deletions'] ?? 0,
        ]);

        if (!$review) {
            return response()->json(['error' => 'Failed to create review'], 500);
        }

        if ($review->status === 'completed') {
            $sent = $this->chatService->sendCodeReviewNotification($review);
            $review->update(['sent_to_chat' => $sent]);
        }

        return response()->json([
            'success' => true,
            'review' => $review,
        ]);
    }

    /**
     * Test Google Chat connection
     */
    public function testGoogleChat()
    {
        $result = $this->chatService->testConnection();
        return response()->json($result);
    }
}
