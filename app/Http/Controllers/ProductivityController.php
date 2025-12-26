<?php

namespace App\Http\Controllers;

use App\Models\GithubCommit;
use App\Models\GithubUserMapping;
use App\Models\User;
use App\Services\GithubApiService;
use App\Services\ProductivityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

/**
 * ProductivityController
 *
 * Handles productivity dashboard views for both admins and developers.
 * This controller displays developer ACTIVITY metrics, not performance evaluations.
 */
class ProductivityController extends Controller
{
    protected ProductivityService $productivityService;

    public function __construct(ProductivityService $productivityService)
    {
        $this->productivityService = $productivityService;
    }

    /**
     * Admin Dashboard - View all developers' activity.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function adminDashboard(Request $request)
    {
        // Check admin permission
        // Assumption: Using existing permission system
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage productivity')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Date range - default to current month
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());
        $viewType = $request->input('view', 'daily'); // daily, weekly, monthly

        // Get overall stats
        $overallStats = $this->productivityService->getOverallStats($startDate, $endDate);

        // Get all developers summary
        $developersSummary = $this->productivityService->getAllDevelopersSummary($startDate, $endDate);

        // Get unique repositories
        $repositories = GithubCommit::getUniqueRepos();

        // Filter by repository if specified
        $selectedRepo = $request->input('repo');
        if ($selectedRepo) {
            $developersSummary = $developersSummary->filter(function ($dev) use ($selectedRepo) {
                return in_array($selectedRepo, $dev['repos']);
            })->values();
        }

        // Get daily activity for chart
        $dailyActivity = $this->getDailyActivityChart($startDate, $endDate);

        return view('productivity.admin-dashboard', compact(
            'overallStats',
            'developersSummary',
            'repositories',
            'selectedRepo',
            'startDate',
            'endDate',
            'viewType',
            'dailyActivity'
        ));
    }

    /**
     * Developer Dashboard - View own activity.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function developerDashboard(Request $request)
    {
        $user = Auth::user();

        // Get GitHub username for current user
        $githubUsername = GithubUserMapping::getGithubUsernameByUserId($user->id);

        // If no mapping exists, try to find commits by similar username
        if (!$githubUsername) {
            // Check if there are any commits that might belong to this user
            // This is a fallback - proper mapping is recommended
            $possibleUsername = GithubCommit::where('github_username', 'like', '%' . $user->name . '%')
                ->orWhere('github_username', 'like', '%' . explode('@', $user->email)[0] . '%')
                ->value('github_username');

            $githubUsername = $possibleUsername;
        }

        // Date range - default to current month
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        if (!$githubUsername) {
            return view('productivity.developer-dashboard', [
                'noMapping' => true,
                'user' => $user,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]);
        }

        // Get daily scores
        $dailyScores = $this->productivityService->getDateRangeSummary($githubUsername, $startDate, $endDate);

        // Get weekly summary
        $weeklySummary = $this->productivityService->getWeeklySummary($githubUsername);

        // Get monthly summary
        $monthlySummary = $this->productivityService->getMonthlySummary($githubUsername);

        // Get activity timeline
        $activityTimeline = $this->productivityService->getActivityTimeline($githubUsername, $startDate, $endDate);

        // Get recent commits
        $recentCommits = $this->productivityService->getRecentCommits($githubUsername, 15);

        // Get idle days
        $idleDays = $this->productivityService->getIdleDays($githubUsername, $startDate, $endDate);

        // Get repo breakdown
        $repoBreakdown = $this->productivityService->getRepoBreakdown($githubUsername, $startDate, $endDate);

        // Calculate total score for period
        $totalScore = round($dailyScores->sum('score'), 2);
        $averageScore = round($dailyScores->avg('score'), 2);

        return view('productivity.developer-dashboard', compact(
            'user',
            'githubUsername',
            'dailyScores',
            'weeklySummary',
            'monthlySummary',
            'activityTimeline',
            'recentCommits',
            'idleDays',
            'repoBreakdown',
            'totalScore',
            'averageScore',
            'startDate',
            'endDate'
        ));
    }

    /**
     * View specific developer's activity (Admin only).
     *
     * @param Request $request
     * @param string $username GitHub username
     * @return \Illuminate\View\View
     */
    public function viewDeveloper(Request $request, string $username)
    {
        // Check admin permission
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage productivity')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Date range
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        // Get developer info
        $mapping = GithubUserMapping::where('github_username', $username)->first();
        $erpUser = $mapping?->user;

        // Get daily scores
        $dailyScores = $this->productivityService->getDateRangeSummary($username, $startDate, $endDate);

        // Get activity timeline
        $activityTimeline = $this->productivityService->getActivityTimeline($username, $startDate, $endDate);

        // Get recent commits
        $recentCommits = $this->productivityService->getRecentCommits($username, 20);

        // Get idle days
        $idleDays = $this->productivityService->getIdleDays($username, $startDate, $endDate);

        // Get repo breakdown
        $repoBreakdown = $this->productivityService->getRepoBreakdown($username, $startDate, $endDate);

        // Calculate totals
        $totalScore = round($dailyScores->sum('score'), 2);
        $averageScore = round($dailyScores->avg('score'), 2);
        $totalCommits = $dailyScores->sum('commits');
        $activeDays = $dailyScores->filter(fn($d) => $d['commits'] > 0)->count();

        return view('productivity.view-developer', compact(
            'username',
            'erpUser',
            'dailyScores',
            'activityTimeline',
            'recentCommits',
            'idleDays',
            'repoBreakdown',
            'totalScore',
            'averageScore',
            'totalCommits',
            'activeDays',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Manage GitHub username mappings (Admin only).
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function manageMappings(Request $request)
    {
        // Check admin permission
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage productivity')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Get all mappings
        $mappings = GithubUserMapping::with('user')->get();

        // Get unmapped GitHub usernames
        $mappedUsernames = $mappings->pluck('github_username')->toArray();
        $unmappedUsernames = GithubCommit::distinct()
            ->whereNotIn('github_username', $mappedUsernames)
            ->pluck('github_username');

        // Get users without mapping
        $mappedUserIds = $mappings->pluck('user_id')->toArray();
        $unmappedUsers = User::whereNotIn('id', $mappedUserIds)
            ->where('type', '!=', 'client')
            ->get();

        return view('productivity.manage-mappings', compact(
            'mappings',
            'unmappedUsernames',
            'unmappedUsers'
        ));
    }

    /**
     * Store a new GitHub username mapping.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeMapping(Request $request)
    {
        // Check admin permission
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage productivity')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'github_username' => 'required|string|max:100|unique:github_user_mappings,github_username',
            'user_id' => 'required|exists:users,id',
        ]);

        GithubUserMapping::create([
            'github_username' => $request->github_username,
            'user_id' => $request->user_id,
        ]);

        // Update existing commits with this mapping
        GithubCommit::where('github_username', $request->github_username)
            ->whereNull('user_id')
            ->update(['user_id' => $request->user_id]);

        return redirect()->back()->with('success', __('GitHub username mapping created successfully.'));
    }

    /**
     * Delete a GitHub username mapping.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteMapping(int $id)
    {
        // Check admin permission
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage productivity')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $mapping = GithubUserMapping::findOrFail($id);

        // Remove user_id from commits
        GithubCommit::where('github_username', $mapping->github_username)
            ->update(['user_id' => null]);

        $mapping->delete();

        return redirect()->back()->with('success', __('Mapping deleted successfully.'));
    }

    /**
     * Show sync settings page (Admin only).
     */
    public function syncSettings()
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $githubService = app(GithubApiService::class);

        $isConfigured = $githubService->isConfigured();
        $connectionTest = $isConfigured ? $githubService->testConnection() : null;
        $repositories = $githubService->getConfiguredRepositories();
        $rateLimit = $isConfigured ? $githubService->getRateLimitStatus() : null;

        // Get last sync info
        $lastCommit = GithubCommit::orderBy('created_at', 'desc')->first();
        $totalCommits = GithubCommit::count();
        $uniqueRepos = GithubCommit::distinct()->count('repo_name');

        return view('productivity.sync-settings', compact(
            'isConfigured',
            'connectionTest',
            'repositories',
            'rateLimit',
            'lastCommit',
            'totalCommits',
            'uniqueRepos'
        ));
    }

    /**
     * Sync commits from GitHub API (Admin only).
     */
    public function syncCommits(Request $request)
    {
        if (Auth::user()->type !== 'company') {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $githubService = app(GithubApiService::class);

        if (!$githubService->isConfigured()) {
            return redirect()->back()->with('error', __('GitHub API token not configured. Please add GITHUB_API_TOKEN to your .env file.'));
        }

        $days = $request->input('days', 30);
        $repo = $request->input('repo');

        $since = Carbon::now()->subDays($days)->toIso8601String();

        try {
            if ($repo) {
                $results = [$repo => $githubService->syncRepository($repo, $since)];
            } else {
                $results = $githubService->syncAllRepositories($since);
            }

            $totalNew = 0;
            $totalFetched = 0;
            foreach ($results as $repoResult) {
                $totalNew += $repoResult['new'];
                $totalFetched += $repoResult['fetched'];
            }

            return redirect()->back()->with('success', __('Sync completed! Fetched :fetched commits, added :new new commits.', [
                'fetched' => $totalFetched,
                'new' => $totalNew,
            ]));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Sync failed: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Get daily activity data for chart.
     */
    protected function getDailyActivityChart(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $data = GithubCommit::selectRaw('DATE(committed_at) as date, COUNT(*) as commits')
            ->whereBetween('committed_at', [$start->startOfDay(), $end->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $values = [];

        $period = \Carbon\CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $labels[] = $date->format('M d');
            $values[] = $data->firstWhere('date', $dateStr)?->commits ?? 0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
