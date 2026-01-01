<?php

namespace App\Services;

use App\Models\CodeReview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeCodeReviewService
{
    protected string $provider;
    protected string $geminiApiKey;
    protected string $geminiModel = 'gemini-2.5-flash';

    public function __construct()
    {
        $this->provider = env('CODE_REVIEW_PROVIDER', 'gemini');
        $this->geminiApiKey = env('GEMINI_API_KEY', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->geminiApiKey);
    }

    /**
     * Review code diff using Gemini AI
     */
    public function reviewCode(string $diff, string $language = 'php', array $context = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'Gemini API key not configured. Get free key at: https://aistudio.google.com/app/apikey',
            ];
        }

        $prompt = $this->buildReviewPrompt($diff, $language, $context);

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";

            $response = Http::timeout(120)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 4096,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $reviewText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                return [
                    'success' => true,
                    'review' => $reviewText,
                    'parsed' => $this->parseReview($reviewText),
                    'provider' => 'gemini',
                ];
            }

            Log::error('Gemini API error: ' . $response->status() . ' - ' . $response->body());
            return [
                'success' => false,
                'error' => 'Gemini request failed: ' . $response->status() . ' - ' . ($response->json()['error']['message'] ?? 'Unknown error'),
            ];

        } catch (\Exception $e) {
            Log::error('Gemini API exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the review prompt
     */
    protected function buildReviewPrompt(string $diff, string $language, array $context): string
    {
        $repoName = $context['repo_name'] ?? 'Unknown';
        $branch = $context['branch'] ?? 'Unknown';
        $commitMessage = $context['commit_message'] ?? '';
        $author = $context['author'] ?? 'Unknown';

        return <<<PROMPT
You are an expert code reviewer. Review the following code changes and provide constructive feedback.

**Repository:** {$repoName}
**Branch:** {$branch}
**Author:** {$author}
**Commit Message:** {$commitMessage}

**Code Diff:**
```{$language}
{$diff}
```

Please analyze the code and provide:

1. **CRITICAL ISSUES** (bugs, security vulnerabilities, data loss risks) - prefix with 🔴
2. **WARNINGS** (performance issues, potential bugs, bad practices) - prefix with 🟡
3. **SUGGESTIONS** (improvements, best practices, code style) - prefix with 🟢
4. **POSITIVE FEEDBACK** - what's done well

Format your response as:

## Summary
[One paragraph overview of the changes]

## Issues Found

### Critical (🔴)
[List critical issues with file:line and suggested fix]

### Warnings (🟡)
[List warnings with file:line and suggested fix]

### Suggestions (🟢)
[List suggestions for improvement]

## Good Practices
[List what was done well]

## Stats
- Critical: [count]
- Warnings: [count]
- Suggestions: [count]

Be concise but thorough. Focus on actionable feedback.
If the code looks good, say so! Don't invent issues.
PROMPT;
    }

    /**
     * Parse the review response to extract structured data
     */
    protected function parseReview(string $review): array
    {
        $issues = [];
        $criticalCount = 0;
        $warningCount = 0;
        $infoCount = 0;

        // Count issues by emoji markers
        $criticalCount = substr_count($review, '🔴');
        $warningCount = substr_count($review, '🟡');
        $infoCount = substr_count($review, '🟢');

        // Extract issues with pattern matching
        preg_match_all('/([🔴🟡🟢])\s*\*?\*?([^🔴🟡🟢\n]+)/u', $review, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $type = match($match[1]) {
                '🔴' => 'critical',
                '🟡' => 'warning',
                '🟢' => 'suggestion',
                default => 'info',
            };

            $issues[] = [
                'type' => $type,
                'message' => trim($match[2]),
            ];
        }

        return [
            'issues' => $issues,
            'critical_count' => $criticalCount,
            'warning_count' => $warningCount,
            'info_count' => $infoCount,
            'total_count' => $criticalCount + $warningCount + $infoCount,
        ];
    }

    /**
     * Create and process a code review
     */
    public function createReview(array $data): ?CodeReview
    {
        try {
            // Create review record
            $review = CodeReview::create([
                'repo_name' => $data['repo_name'],
                'branch' => $data['branch'],
                'commit_sha' => $data['commit_sha'],
                'commit_message' => $data['commit_message'] ?? '',
                'author_username' => $data['author_username'],
                'author_email' => $data['author_email'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'code_diff' => $data['diff'] ?? '',
                'files_changed' => $data['files_changed'] ?? 0,
                'lines_added' => $data['lines_added'] ?? 0,
                'lines_deleted' => $data['lines_deleted'] ?? 0,
                'status' => 'reviewing',
            ]);

            // Perform AI review
            $result = $this->reviewCode($data['diff'] ?? '', 'php', [
                'repo_name' => $data['repo_name'],
                'branch' => $data['branch'],
                'commit_message' => $data['commit_message'] ?? '',
                'author' => $data['author_username'],
            ]);

            if ($result['success']) {
                $parsed = $result['parsed'];

                $review->update([
                    'ai_review' => $result['review'],
                    'issues_found' => $parsed['issues'],
                    'issues_count' => $parsed['total_count'],
                    'critical_count' => $parsed['critical_count'],
                    'warning_count' => $parsed['warning_count'],
                    'info_count' => $parsed['info_count'],
                    'status' => 'completed',
                    'reviewed_at' => now(),
                ]);
            } else {
                $review->update([
                    'ai_review' => 'Review failed: ' . ($result['error'] ?? 'Unknown error'),
                    'status' => 'failed',
                ]);
            }

            return $review->fresh();

        } catch (\Exception $e) {
            Log::error('Failed to create code review: ' . $e->getMessage());
            return null;
        }
    }
}
