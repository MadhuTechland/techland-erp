<?php

namespace App\Console\Commands;

use App\Services\GithubApiService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncGithubCommits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:sync-commits
                            {--repo= : Specific repository to sync (owner/repo format)}
                            {--since= : Start date (Y-m-d format, default: 30 days ago)}
                            {--until= : End date (Y-m-d format, default: now)}
                            {--days= : Number of days to sync (alternative to --since)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync commit history from GitHub repositories';

    /**
     * Execute the console command.
     */
    public function handle(GithubApiService $githubService)
    {
        $this->info('GitHub Commit Sync');
        $this->line('==================');

        // Check if API is configured
        if (!$githubService->isConfigured()) {
            $this->error('GitHub API token not configured!');
            $this->line('Please set GITHUB_API_TOKEN in your .env file');
            $this->line('Generate a token at: https://github.com/settings/tokens');
            return 1;
        }

        // Test connection
        $this->info('Testing GitHub API connection...');
        $connectionTest = $githubService->testConnection();

        if (!$connectionTest['success']) {
            $this->error($connectionTest['message']);
            return 1;
        }

        $this->info($connectionTest['message']);

        // Determine date range
        $since = $this->option('since');
        $until = $this->option('until');
        $days = $this->option('days');

        if ($days) {
            $since = Carbon::now()->subDays((int) $days)->toIso8601String();
        } elseif ($since) {
            $since = Carbon::parse($since)->startOfDay()->toIso8601String();
        }

        if ($until) {
            $until = Carbon::parse($until)->endOfDay()->toIso8601String();
        }

        // Determine repositories to sync
        $specificRepo = $this->option('repo');

        if ($specificRepo) {
            $repos = [$specificRepo];
        } else {
            $repos = $githubService->getConfiguredRepositories();
        }

        if (empty($repos)) {
            $this->error('No repositories configured!');
            $this->line('Please set GITHUB_REPOSITORIES in your .env file');
            $this->line('Example: GITHUB_REPOSITORIES=owner/repo1,owner/repo2');
            return 1;
        }

        $this->info('Repositories to sync: ' . implode(', ', $repos));
        $this->line('');

        $totalStats = [
            'fetched' => 0,
            'new' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($repos as $repo) {
            $this->info("Syncing {$repo}...");

            $progressBar = $this->output->createProgressBar(100);
            $progressBar->setFormat(' %current%/%max% [%bar%] %message%');
            $progressBar->setMessage('Fetching commits...');
            $progressBar->start();

            $stats = $githubService->syncRepository(
                $repo,
                $since,
                $until,
                function ($current, $total, $commit) use ($progressBar) {
                    $progressBar->setMaxSteps($total);
                    $progressBar->setProgress($current);
                    $sha = substr($commit['sha'], 0, 7);
                    $progressBar->setMessage("Processing {$sha}");
                }
            );

            $progressBar->finish();
            $this->line('');

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Fetched', $stats['fetched']],
                    ['New', $stats['new']],
                    ['Skipped', $stats['skipped']],
                    ['Errors', $stats['errors']],
                ]
            );

            $totalStats['fetched'] += $stats['fetched'];
            $totalStats['new'] += $stats['new'];
            $totalStats['skipped'] += $stats['skipped'];
            $totalStats['errors'] += $stats['errors'];

            $this->line('');
        }

        $this->info('Sync Complete!');
        $this->table(
            ['Total', 'Count'],
            [
                ['Commits Fetched', $totalStats['fetched']],
                ['New Commits Added', $totalStats['new']],
                ['Skipped (existing/merge/small)', $totalStats['skipped']],
                ['Errors', $totalStats['errors']],
            ]
        );

        return 0;
    }
}
