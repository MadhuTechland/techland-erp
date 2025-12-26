<?php

namespace App\Services;

use App\Models\GithubCommit;
use App\Models\GithubUserMapping;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ProductivityService
 *
 * Handles all productivity calculations and statistics.
 * This service measures developer ACTIVITY, not performance quality.
 *
 * Scoring Formula:
 * daily_score = (commits * 5) + (files_changed * 1) + (min(lines_added, 500) * 0.01)
 *
 * The scoring is designed to:
 * - Reward consistent commit activity
 * - Value file changes as meaningful work
 * - Cap line additions to prevent gaming (max 500 lines counted)
 */
class ProductivityService
{
    /**
     * Scoring weights - can be configured via .env if needed
     */
    protected const COMMIT_WEIGHT = 5;
    protected const FILE_CHANGE_WEIGHT = 1;
    protected const LINE_ADDITION_WEIGHT = 0.01;
    protected const MAX_LINES_COUNTED = 500;
    protected const MAX_DAILY_SCORE = 100; // Cap to prevent abuse

    /**
     * Calculate productivity score for a single commit.
     */
    public function calculateCommitScore(GithubCommit $commit): float
    {
        $linesScore = min($commit->lines_added, self::MAX_LINES_COUNTED) * self::LINE_ADDITION_WEIGHT;
        $filesScore = $commit->files_changed * self::FILE_CHANGE_WEIGHT;

        return self::COMMIT_WEIGHT + $filesScore + $linesScore;
    }

    /**
     * Calculate daily productivity score for a developer.
     *
     * @param string $githubUsername
     * @param Carbon|string $date
     * @return array{score: float, commits: int, files: int, lines_added: int, lines_deleted: int}
     */
    public function calculateDailyScore(string $githubUsername, $date): array
    {
        $date = Carbon::parse($date);

        $commits = GithubCommit::byUsername($githubUsername)
            ->onDate($date)
            ->get();

        $totalCommits = $commits->count();
        $totalFiles = $commits->sum('files_changed');
        $totalLinesAdded = $commits->sum('lines_added');
        $totalLinesDeleted = $commits->sum('lines_deleted');

        // Calculate score
        $commitScore = $totalCommits * self::COMMIT_WEIGHT;
        $filesScore = $totalFiles * self::FILE_CHANGE_WEIGHT;
        $linesScore = min($totalLinesAdded, self::MAX_LINES_COUNTED) * self::LINE_ADDITION_WEIGHT;

        $totalScore = min($commitScore + $filesScore + $linesScore, self::MAX_DAILY_SCORE);

        return [
            'date' => $date->toDateString(),
            'score' => round($totalScore, 2),
            'commits' => $totalCommits,
            'files_changed' => $totalFiles,
            'lines_added' => $totalLinesAdded,
            'lines_deleted' => $totalLinesDeleted,
        ];
    }

    /**
     * Calculate daily score using ERP user ID.
     */
    public function calculateDailyScoreByUserId(int $userId, $date): array
    {
        $githubUsername = GithubUserMapping::getGithubUsernameByUserId($userId);

        if (!$githubUsername) {
            return [
                'date' => Carbon::parse($date)->toDateString(),
                'score' => 0,
                'commits' => 0,
                'files_changed' => 0,
                'lines_added' => 0,
                'lines_deleted' => 0,
            ];
        }

        return $this->calculateDailyScore($githubUsername, $date);
    }

    /**
     * Get productivity summary for a date range.
     *
     * @param string $githubUsername
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     * @return Collection
     */
    public function getDateRangeSummary(string $githubUsername, $startDate, $endDate): Collection
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $period = CarbonPeriod::create($start, $end);
        $results = collect();

        foreach ($period as $date) {
            $results->push($this->calculateDailyScore($githubUsername, $date));
        }

        return $results;
    }

    /**
     * Get weekly summary for a developer.
     */
    public function getWeeklySummary(string $githubUsername, $weekStart = null): array
    {
        $start = $weekStart ? Carbon::parse($weekStart)->startOfWeek() : Carbon::now()->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $dailyScores = $this->getDateRangeSummary($githubUsername, $start, $end);

        return [
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'total_score' => round($dailyScores->sum('score'), 2),
            'total_commits' => $dailyScores->sum('commits'),
            'total_files_changed' => $dailyScores->sum('files_changed'),
            'total_lines_added' => $dailyScores->sum('lines_added'),
            'total_lines_deleted' => $dailyScores->sum('lines_deleted'),
            'active_days' => $dailyScores->filter(fn($day) => $day['commits'] > 0)->count(),
            'idle_days' => $dailyScores->filter(fn($day) => $day['commits'] === 0)->count(),
            'daily_breakdown' => $dailyScores->toArray(),
        ];
    }

    /**
     * Get monthly summary for a developer.
     */
    public function getMonthlySummary(string $githubUsername, $month = null, $year = null): array
    {
        $date = Carbon::now();
        if ($month && $year) {
            $date = Carbon::createFromDate($year, $month, 1);
        }

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $dailyScores = $this->getDateRangeSummary($githubUsername, $start, $end);

        return [
            'month' => $start->format('F Y'),
            'month_start' => $start->toDateString(),
            'month_end' => $end->toDateString(),
            'total_score' => round($dailyScores->sum('score'), 2),
            'average_daily_score' => round($dailyScores->avg('score'), 2),
            'total_commits' => $dailyScores->sum('commits'),
            'total_files_changed' => $dailyScores->sum('files_changed'),
            'total_lines_added' => $dailyScores->sum('lines_added'),
            'total_lines_deleted' => $dailyScores->sum('lines_deleted'),
            'active_days' => $dailyScores->filter(fn($day) => $day['commits'] > 0)->count(),
            'idle_days' => $dailyScores->filter(fn($day) => $day['commits'] === 0)->count(),
            'daily_breakdown' => $dailyScores->toArray(),
        ];
    }

    /**
     * Get all developers with their summary stats.
     * Used for admin dashboard.
     */
    public function getAllDevelopersSummary($startDate, $endDate): Collection
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $developers = GithubCommit::select('github_username')
            ->distinct()
            ->pluck('github_username');

        return $developers->map(function ($username) use ($start, $end) {
            $commits = GithubCommit::byUsername($username)
                ->dateRange($start, $end)
                ->get();

            $dailyScores = $this->getDateRangeSummary($username, $start, $end);

            // Get linked ERP user if exists
            $mapping = GithubUserMapping::where('github_username', $username)->first();
            $erpUser = $mapping?->user;

            return [
                'github_username' => $username,
                'erp_user_id' => $erpUser?->id,
                'erp_user_name' => $erpUser?->name ?? $username,
                'total_score' => round($dailyScores->sum('score'), 2),
                'average_daily_score' => round($dailyScores->avg('score'), 2),
                'total_commits' => $commits->count(),
                'total_files_changed' => $commits->sum('files_changed'),
                'total_lines_added' => $commits->sum('lines_added'),
                'total_lines_deleted' => $commits->sum('lines_deleted'),
                'active_days' => $dailyScores->filter(fn($day) => $day['commits'] > 0)->count(),
                'idle_days' => $dailyScores->filter(fn($day) => $day['commits'] === 0)->count(),
                'repos' => $commits->pluck('repo_name')->unique()->values()->toArray(),
            ];
        })->sortByDesc('total_score')->values();
    }

    /**
     * Get activity timeline for a developer.
     * Returns commits grouped by date.
     */
    public function getActivityTimeline(string $githubUsername, $startDate, $endDate): Collection
    {
        return GithubCommit::byUsername($githubUsername)
            ->dateRange($startDate, $endDate)
            ->orderBy('committed_at', 'desc')
            ->get()
            ->groupBy(fn($commit) => $commit->committed_at->toDateString());
    }

    /**
     * Get idle days (days with no commits) for a developer.
     */
    public function getIdleDays(string $githubUsername, $startDate, $endDate): Collection
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $period = CarbonPeriod::create($start, $end);

        $activeDates = GithubCommit::byUsername($githubUsername)
            ->dateRange($start, $end)
            ->get()
            ->pluck('committed_at')
            ->map(fn($date) => $date->toDateString())
            ->unique();

        $idleDays = collect();
        foreach ($period as $date) {
            // Only count weekdays as potential idle days
            if ($date->isWeekday() && !$activeDates->contains($date->toDateString())) {
                $idleDays->push($date->toDateString());
            }
        }

        return $idleDays;
    }

    /**
     * Get repository-wise breakdown for a developer.
     */
    public function getRepoBreakdown(string $githubUsername, $startDate, $endDate): Collection
    {
        return GithubCommit::byUsername($githubUsername)
            ->dateRange($startDate, $endDate)
            ->select('repo_name')
            ->selectRaw('COUNT(*) as commits')
            ->selectRaw('SUM(files_changed) as files_changed')
            ->selectRaw('SUM(lines_added) as lines_added')
            ->selectRaw('SUM(lines_deleted) as lines_deleted')
            ->groupBy('repo_name')
            ->orderByDesc('commits')
            ->get();
    }

    /**
     * Get recent commits for a developer.
     */
    public function getRecentCommits(string $githubUsername, int $limit = 10): Collection
    {
        return GithubCommit::byUsername($githubUsername)
            ->orderBy('committed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get overall statistics for admin dashboard.
     */
    public function getOverallStats($startDate, $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $commits = GithubCommit::dateRange($start, $end);

        return [
            'total_commits' => $commits->count(),
            'total_developers' => $commits->distinct('github_username')->count('github_username'),
            'total_repos' => $commits->distinct('repo_name')->count('repo_name'),
            'total_files_changed' => $commits->sum('files_changed'),
            'total_lines_added' => $commits->sum('lines_added'),
            'total_lines_deleted' => $commits->sum('lines_deleted'),
            'average_commits_per_day' => round($commits->count() / max(1, $start->diffInDays($end)), 2),
        ];
    }
}
