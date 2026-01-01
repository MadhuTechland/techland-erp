<?php

namespace App\Http\Controllers;

use App\Models\CodeReview;
use App\Models\CodeReviewIssueAction;
use App\Models\GithubUserMapping;
use App\Models\User;
use App\Services\GoogleChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CodeReviewController extends Controller
{
    /**
     * Display list of all code reviews
     */
    public function index(Request $request)
    {
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage code reviews')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $query = CodeReview::with('user')->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('repo')) {
            $query->where('repo_name', $request->repo);
        }

        if ($request->filled('author')) {
            $query->where('author_username', $request->author);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('severity')) {
            if ($request->severity === 'critical') {
                $query->where('critical_count', '>', 0);
            } elseif ($request->severity === 'warning') {
                $query->where('warning_count', '>', 0)->where('critical_count', 0);
            } elseif ($request->severity === 'clean') {
                $query->where('issues_count', 0);
            }
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $reviews = $query->paginate(20);

        // Get filter options
        $repos = CodeReview::distinct()->pluck('repo_name');
        $authors = CodeReview::distinct()->pluck('author_username');

        // Get stats
        $stats = [
            'total' => CodeReview::count(),
            'with_critical' => CodeReview::where('critical_count', '>', 0)->count(),
            'with_warnings' => CodeReview::where('warning_count', '>', 0)->count(),
            'clean' => CodeReview::where('issues_count', 0)->count(),
        ];

        return view('code_reviews.index', compact('reviews', 'repos', 'authors', 'stats'));
    }

    /**
     * Display a specific code review
     */
    public function show($id)
    {
        $review = CodeReview::with(['user', 'issueActions.actionedByUser'])->findOrFail($id);

        // Check access - admin or the developer who made the commit
        $user = Auth::user();
        $hasAccess = $user->type === 'company'
            || $user->can('manage code reviews')
            || $review->user_id === $user->id
            || $this->userOwnsGithubUsername($user->id, $review->author_username);

        if (!$hasAccess) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Format the AI review as HTML
        $formattedReview = $this->formatMarkdown($review->ai_review ?? '');

        // Get action labels and colors
        $actionLabels = CodeReviewIssueAction::getActionLabels();
        $actionColors = CodeReviewIssueAction::getActionColors();

        return view('code_reviews.show', compact('review', 'formattedReview', 'actionLabels', 'actionColors'));
    }

    /**
     * Convert markdown to HTML
     */
    protected function formatMarkdown(?string $text): string
    {
        if (!$text) return '';

        // Escape HTML first
        $text = e($text);

        // Convert code blocks (```language ... ```)
        $text = preg_replace_callback('/```(\w*)\n(.*?)```/s', function($matches) {
            $lang = $matches[1] ?: 'plaintext';
            $code = trim($matches[2]);
            return "<pre><code class=\"language-{$lang}\">{$code}</code></pre>";
        }, $text);

        // Convert inline code (`code`)
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        // Convert headers
        $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);

        // Convert bold (**text**)
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);

        // Convert italic (*text*)
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);

        // Convert numbered lists
        $text = preg_replace('/^(\d+)\.\s+(.+)$/m', '<li>$2</li>', $text);

        // Convert bullet lists
        $text = preg_replace('/^[-*]\s+(.+)$/m', '<li>$1</li>', $text);

        // Wrap consecutive <li> in <ul> or <ol>
        $text = preg_replace('/(<li>.*?<\/li>\n?)+/s', '<ul>$0</ul>', $text);

        // Convert line breaks (but not inside pre/code)
        $lines = explode("\n", $text);
        $result = [];
        $inPre = false;

        foreach ($lines as $line) {
            if (strpos($line, '<pre>') !== false) $inPre = true;
            if (strpos($line, '</pre>') !== false) $inPre = false;

            if (!$inPre && !empty(trim($line)) &&
                !preg_match('/^<(h[23]|ul|ol|li|pre|\/)/i', trim($line))) {
                $line = '<p>' . $line . '</p>';
            }
            $result[] = $line;
        }

        $text = implode("\n", $result);

        // Clean up empty paragraphs and fix nested tags
        $text = preg_replace('/<p>\s*<\/p>/', '', $text);
        $text = preg_replace('/<p>(<h[23]>)/', '$1', $text);
        $text = preg_replace('/(<\/h[23]>)<\/p>/', '$1', $text);
        $text = preg_replace('/<p>(<ul>)/', '$1', $text);
        $text = preg_replace('/(<\/ul>)<\/p>/', '$1', $text);

        return $text;
    }

    /**
     * Developer's code reviews
     */
    public function myReviews(Request $request)
    {
        $user = Auth::user();

        // Get GitHub usernames linked to this user
        $githubUsernames = GithubUserMapping::where('user_id', $user->id)
            ->pluck('github_username')
            ->toArray();

        if (empty($githubUsernames)) {
            return view('code_reviews.my_reviews', [
                'reviews' => collect(),
                'noMapping' => true,
                'stats' => ['total' => 0, 'pending_actions' => 0],
            ]);
        }

        $query = CodeReview::whereIn('author_username', $githubUsernames)
            ->orWhere('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        $reviews = $query->paginate(20);

        // Calculate pending actions
        $reviewIds = $reviews->pluck('id');
        $totalIssues = CodeReview::whereIn('id', $reviewIds)->sum('issues_count');
        $actionedIssues = CodeReviewIssueAction::whereIn('code_review_id', $reviewIds)->count();

        $stats = [
            'total' => $reviews->total(),
            'pending_actions' => $totalIssues - $actionedIssues,
        ];

        return view('code_reviews.my_reviews', compact('reviews', 'stats'));
    }

    /**
     * Take action on an issue
     */
    public function takeAction(Request $request, $reviewId, $issueIndex)
    {
        $review = CodeReview::findOrFail($reviewId);
        $user = Auth::user();

        // Check access
        $hasAccess = $user->type === 'company'
            || $user->can('manage code reviews')
            || $review->user_id === $user->id
            || $this->userOwnsGithubUsername($user->id, $review->author_username);

        if (!$hasAccess) {
            return response()->json(['error' => 'Permission Denied'], 403);
        }

        $request->validate([
            'action' => 'required|in:implemented,rejected,will_fix_later,not_applicable',
            'comment' => 'nullable|string|max:500',
        ]);

        $action = CodeReviewIssueAction::updateOrCreate(
            [
                'code_review_id' => $reviewId,
                'issue_index' => $issueIndex,
            ],
            [
                'action' => $request->action,
                'developer_comment' => $request->comment,
                'actioned_by' => $user->id,
                'actioned_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'action' => $action,
            'label' => CodeReviewIssueAction::getActionLabels()[$action->action],
            'color' => CodeReviewIssueAction::getActionColors()[$action->action],
        ]);
    }

    /**
     * GitHub-User mapping management
     */
    public function mappings(Request $request)
    {
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage code reviews')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        // Get all mappings grouped by user
        $mappings = GithubUserMapping::with('user')
            ->get()
            ->groupBy('user_id');

        // Get users
        $users = User::whereNotIn('type', ['client'])
            ->orderBy('name')
            ->get();

        // Get unmapped GitHub usernames from code reviews
        $mappedUsernames = GithubUserMapping::pluck('github_username')->toArray();
        $unmappedUsernames = CodeReview::distinct()
            ->whereNotIn('author_username', $mappedUsernames)
            ->pluck('author_username');

        return view('code_reviews.mappings', compact('mappings', 'users', 'unmappedUsernames'));
    }

    /**
     * Store a new GitHub username mapping
     */
    public function storeMapping(Request $request)
    {
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage code reviews')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $request->validate([
            'github_username' => 'required|string|max:100|unique:github_user_mappings,github_username',
            'github_email' => 'nullable|email|max:255',
            'user_id' => 'required|exists:users,id',
        ]);

        GithubUserMapping::create([
            'github_username' => $request->github_username,
            'github_email' => $request->github_email,
            'user_id' => $request->user_id,
        ]);

        // Update existing code reviews with this mapping
        CodeReview::where('author_username', $request->github_username)
            ->whereNull('user_id')
            ->update(['user_id' => $request->user_id]);

        return redirect()->back()->with('success', __('GitHub username mapping created successfully.'));
    }

    /**
     * Delete a GitHub username mapping
     */
    public function deleteMapping($id)
    {
        if (Auth::user()->type !== 'company' && !Auth::user()->can('manage code reviews')) {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

        $mapping = GithubUserMapping::findOrFail($id);
        $mapping->delete();

        return redirect()->back()->with('success', __('Mapping deleted successfully.'));
    }

    /**
     * Parse AI review into structured sections
     */
    protected function parseAiReview(?string $review): array
    {
        if (!$review) {
            return [
                'summary' => null,
                'critical' => [],
                'warnings' => [],
                'suggestions' => [],
                'good_practices' => null,
            ];
        }

        $sections = [
            'summary' => null,
            'critical' => [],
            'warnings' => [],
            'suggestions' => [],
            'good_practices' => null,
        ];

        // Extract summary
        if (preg_match('/## Summary\s*\n(.+?)(?=\n##|\z)/s', $review, $matches)) {
            $sections['summary'] = trim($matches[1]);
        }

        // Extract critical issues
        if (preg_match('/### Critical.*?\n(.+?)(?=\n###|\n## |\z)/s', $review, $matches)) {
            $sections['critical'] = $this->parseIssueList($matches[1], 'critical');
        }

        // Extract warnings
        if (preg_match('/### Warnings.*?\n(.+?)(?=\n###|\n## |\z)/s', $review, $matches)) {
            $sections['warnings'] = $this->parseIssueList($matches[1], 'warning');
        }

        // Extract suggestions
        if (preg_match('/### Suggestions.*?\n(.+?)(?=\n###|\n## |\z)/s', $review, $matches)) {
            $sections['suggestions'] = $this->parseIssueList($matches[1], 'suggestion');
        }

        // Extract good practices
        if (preg_match('/## Good Practices\s*\n(.+?)(?=\n##|\z)/s', $review, $matches)) {
            $sections['good_practices'] = trim($matches[1]);
        }

        return $sections;
    }

    /**
     * Parse issue list from markdown
     */
    protected function parseIssueList(string $content, string $type): array
    {
        $issues = [];
        $lines = explode("\n", $content);
        $currentIssue = null;

        foreach ($lines as $line) {
            $line = trim($line);

            // Check for numbered issue or emoji-prefixed issue
            if (preg_match('/^(\d+\.\s*)?[🔴🟡🟢]?\s*\*?\*?(.+?)\*?\*?\s*$/', $line, $matches)) {
                if ($currentIssue) {
                    $issues[] = $currentIssue;
                }

                // Extract file:line if present
                $text = trim($matches[2]);
                $file = null;
                $lineNum = null;

                if (preg_match('/^([^:]+:\d+(?:[-,]\d+)?)\s*[-–]\s*(.+)/', $text, $fileMatch)) {
                    $file = trim($fileMatch[1]);
                    $text = trim($fileMatch[2]);
                }

                $currentIssue = [
                    'type' => $type,
                    'file' => $file,
                    'text' => $text,
                    'details' => '',
                ];
            } elseif ($currentIssue && !empty($line) && !str_starts_with($line, '#')) {
                // Continuation of current issue
                $currentIssue['details'] .= ($currentIssue['details'] ? "\n" : '') . $line;
            }
        }

        if ($currentIssue) {
            $issues[] = $currentIssue;
        }

        return $issues;
    }

    /**
     * Check if user owns a GitHub username
     */
    protected function userOwnsGithubUsername(int $userId, string $githubUsername): bool
    {
        return GithubUserMapping::where('user_id', $userId)
            ->whereRaw('LOWER(github_username) = ?', [strtolower($githubUsername)])
            ->exists();
    }
}
