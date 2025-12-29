<?php

namespace App\Services;

use App\Models\GithubCommit;
use App\Models\GithubUserMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GithubApiService
{
    protected string $apiToken;
    protected string $baseUrl = 'https://api.github.com';

    public function __construct()
    {
        $this->apiToken = config('services.github.api_token', env('GITHUB_API_TOKEN', ''));
    }

    /**
     * Get configured repositories from env
     */
    public function getConfiguredRepositories(): array
    {
        $repos = env('GITHUB_REPOSITORIES', '');
        if (empty($repos)) {
            return [];
        }

        return array_map('trim', explode(',', $repos));
    }

    /**
     * Fetch all branches from a repository
     */
    public function fetchBranches(string $repoFullName): array
    {
        $allBranches = [];
        $page = 1;

        do {
            $response = $this->makeRequest("repos/{$repoFullName}/branches", [
                'per_page' => 100,
                'page' => $page,
            ]);

            if (empty($response)) {
                break;
            }

            $allBranches = array_merge($allBranches, $response);
            $page++;

            if ($page > 10) {
                break;
            }

        } while (count($response) === 100);

        return $allBranches;
    }

    /**
     * Fetch commits from a repository (all branches)
     */
    public function fetchCommits(string $repoFullName, ?string $since = null, ?string $until = null, int $perPage = 100): array
    {
        $allCommits = [];
        $seenShas = [];

        // Default to last 30 days if not specified
        if (!$since) {
            $since = Carbon::now()->subDays(30)->toIso8601String();
        }

        // Get all branches
        $branches = $this->fetchBranches($repoFullName);

        if (empty($branches)) {
            // Fallback to default branch if we can't get branches
            $branches = [['name' => 'main'], ['name' => 'master']];
        }

        foreach ($branches as $branch) {
            $branchName = $branch['name'];
            $page = 1;

            do {
                $response = $this->makeRequest("repos/{$repoFullName}/commits", [
                    'sha' => $branchName,
                    'since' => $since,
                    'until' => $until,
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

                if (empty($response)) {
                    break;
                }

                // Add commits, avoiding duplicates (same commit can be in multiple branches)
                foreach ($response as $commit) {
                    $sha = $commit['sha'];
                    if (!isset($seenShas[$sha])) {
                        $seenShas[$sha] = true;
                        $commit['_branch'] = $branchName; // Track which branch
                        $allCommits[] = $commit;
                    }
                }

                $page++;

                // Safety limit per branch
                if ($page > 20) {
                    break;
                }

            } while (count($response) === $perPage);
        }

        return $allCommits;
    }

    /**
     * Fetch detailed commit info (for lines added/deleted)
     */
    public function fetchCommitDetails(string $repoFullName, string $sha): ?array
    {
        return $this->makeRequest("repos/{$repoFullName}/commits/{$sha}");
    }

    /**
     * Sync commits from a repository to database
     */
    public function syncRepository(string $repoFullName, ?string $since = null, ?string $until = null, callable $progressCallback = null): array
    {
        $stats = [
            'repo' => $repoFullName,
            'fetched' => 0,
            'new' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $repoName = $this->extractRepoName($repoFullName);
        $commits = $this->fetchCommits($repoFullName, $since, $until);
        $stats['fetched'] = count($commits);

        foreach ($commits as $index => $commitData) {
            try {
                $sha = $commitData['sha'];

                // Skip if commit already exists
                if (GithubCommit::where('commit_sha', $sha)->exists()) {
                    $stats['skipped']++;
                    continue;
                }

                // Skip merge commits
                if (isset($commitData['parents']) && count($commitData['parents']) > 1) {
                    $stats['skipped']++;
                    continue;
                }

                // Get commit author
                $authorUsername = $commitData['author']['login'] ??
                                  $commitData['commit']['author']['name'] ?? 'unknown';

                // Fetch detailed commit info for file stats
                $details = $this->fetchCommitDetails($repoFullName, $sha);

                $filesChanged = $details['stats']['total'] ?? 0;
                $linesAdded = $details['stats']['additions'] ?? 0;
                $linesDeleted = $details['stats']['deletions'] ?? 0;

                // Skip very small commits (less than 10 lines changed)
                $totalLines = $linesAdded + $linesDeleted;
                if ($totalLines < 10) {
                    $stats['skipped']++;
                    continue;
                }

                // Get user mapping if exists
                $mapping = GithubUserMapping::where('github_username', $authorUsername)->first();

                // Create commit record
                GithubCommit::create([
                    'github_username' => $authorUsername,
                    'repo_name' => $repoName,
                    'commit_sha' => $sha,
                    'commit_message' => $commitData['commit']['message'] ?? '',
                    'files_changed' => count($details['files'] ?? []),
                    'lines_added' => $linesAdded,
                    'lines_deleted' => $linesDeleted,
                    'committed_at' => Carbon::parse($commitData['commit']['author']['date'] ?? now()),
                    'user_id' => $mapping?->user_id,
                ]);

                $stats['new']++;

                // Call progress callback if provided
                if ($progressCallback) {
                    $progressCallback($index + 1, $stats['fetched'], $commitData);
                }

                // Rate limiting - GitHub allows 5000 requests/hour for authenticated requests
                // Add small delay to be safe
                usleep(100000); // 100ms delay

            } catch (\Exception $e) {
                Log::error("Failed to sync commit {$sha}: " . $e->getMessage());
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Sync all configured repositories
     */
    public function syncAllRepositories(?string $since = null, ?string $until = null, callable $progressCallback = null): array
    {
        $repos = $this->getConfiguredRepositories();
        $results = [];

        foreach ($repos as $repo) {
            if ($progressCallback) {
                $progressCallback("Syncing {$repo}...");
            }

            $results[$repo] = $this->syncRepository($repo, $since, $until);
        }

        return $results;
    }

    /**
     * Make API request to GitHub
     */
    protected function makeRequest(string $endpoint, array $params = []): ?array
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => "Bearer {$this->apiToken}",
                'User-Agent' => 'TechlandERP-Productivity-Tracker',
            ])->get("{$this->baseUrl}/{$endpoint}", $params);

            if ($response->successful()) {
                return $response->json();
            }

            if ($response->status() === 403) {
                Log::warning('GitHub API rate limit reached or access denied');
                return null;
            }

            Log::error("GitHub API error: " . $response->status() . " - " . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error("GitHub API request failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract repo name from full name (owner/repo -> repo)
     */
    protected function extractRepoName(string $fullName): string
    {
        $parts = explode('/', $fullName);
        return end($parts);
    }

    /**
     * Check if API token is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken);
    }

    /**
     * Test API connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'GitHub API token not configured',
            ];
        }

        $response = $this->makeRequest('user');

        if ($response) {
            return [
                'success' => true,
                'message' => 'Connected as: ' . ($response['login'] ?? 'Unknown'),
                'user' => $response['login'] ?? null,
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to connect to GitHub API',
        ];
    }

    /**
     * Get rate limit status
     */
    public function getRateLimitStatus(): ?array
    {
        return $this->makeRequest('rate_limit');
    }
}
