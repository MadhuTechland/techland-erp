<?php

namespace App\Services;

use App\Models\CodeReview;
use App\Models\GithubUserMapping;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleChatService
{
    protected string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.google_chat.webhook_url', env('GOOGLE_CHAT_WEBHOOK_URL', ''));
    }

    public function isConfigured(): bool
    {
        return !empty($this->webhookUrl);
    }

    /**
     * Send a code review notification to Google Chat
     */
    public function sendCodeReviewNotification(CodeReview $review): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('Google Chat webhook URL not configured');
            return false;
        }

        $message = $this->formatCodeReviewMessage($review);

        return $this->sendMessage($message);
    }

    /**
     * Format a code review into a Google Chat card message
     */
    protected function formatCodeReviewMessage(CodeReview $review): array
    {
        // Get user mention
        $userMention = $this->getUserMention($review->author_username, $review->author_email);

        // Determine status icon and color
        $statusIcon = '✅';
        $statusText = 'No issues found';
        if ($review->critical_count > 0) {
            $statusIcon = '🔴';
            $statusText = "{$review->critical_count} Critical";
        } elseif ($review->warning_count > 0) {
            $statusIcon = '🟡';
            $statusText = "{$review->warning_count} Warnings";
        } elseif ($review->info_count > 0) {
            $statusIcon = '🟢';
            $statusText = "{$review->info_count} Suggestions";
        }

        // Build compact message
        $text = "{$statusIcon} *AI Code Review*\n\n";
        $text .= "*{$review->repo_name}* / {$review->branch}\n";
        $text .= "`" . substr($review->commit_sha, 0, 7) . "` " . $this->truncateWords($review->commit_message, 60) . "\n";
        $text .= "By: {$userMention}\n\n";

        // Issues summary in one line
        $issuesSummary = [];
        if ($review->critical_count > 0) $issuesSummary[] = "🔴 {$review->critical_count}";
        if ($review->warning_count > 0) $issuesSummary[] = "🟡 {$review->warning_count}";
        if ($review->info_count > 0) $issuesSummary[] = "🟢 {$review->info_count}";

        if (!empty($issuesSummary)) {
            $text .= "*Issues:* " . implode("  ", $issuesSummary) . "\n";
        } else {
            $text .= "✨ *Great job!* No issues found.\n";
        }

        $text .= "📊 {$review->files_changed} files | +{$review->lines_added} | -{$review->lines_deleted}\n\n";

        // Add link to view full review in ERP
        $reviewUrl = url("/code-reviews/{$review->id}");
        $text .= "👉 <{$reviewUrl}|View Full Review & Take Action>";

        return [
            'text' => $text,
        ];
    }

    /**
     * Extract summary from AI review
     */
    protected function extractSummary(string $review): ?string
    {
        if (preg_match('/## Summary\s*\n(.+?)(?=\n##|\n\n##|$)/s', $review, $matches)) {
            return $this->truncateWords(trim($matches[1]), 250);
        }
        return null;
    }

    /**
     * Extract top issues from AI review
     */
    protected function extractTopIssues(string $review, int $limit = 3): ?string
    {
        $issues = [];

        // Find critical issues - extract issue title after the dash
        // Pattern: 🔴 **file:line** - **Issue Title**
        preg_match_all('/🔴[^🔴🟡🟢]*?-\s*\*?\*?([^*\n🔴🟡🟢]+)/u', $review, $criticalMatches);
        foreach ($criticalMatches[1] as $issue) {
            $cleaned = $this->cleanIssueText($issue);
            if (strlen($cleaned) >= 10 && !preg_match('/^\d+$/', $cleaned)) {
                $issues[] = '🔴 ' . $this->truncateWords($cleaned, 120);
            }
        }

        // Find warnings if we need more issues
        if (count($issues) < $limit) {
            preg_match_all('/🟡[^🔴🟡🟢]*?-\s*\*?\*?([^*\n🔴🟡🟢]+)/u', $review, $warningMatches);
            foreach ($warningMatches[1] as $issue) {
                $cleaned = $this->cleanIssueText($issue);
                if (strlen($cleaned) >= 10 && !preg_match('/^\d+$/', $cleaned)) {
                    $issues[] = '🟡 ' . $this->truncateWords($cleaned, 120);
                }
            }
        }

        // Limit to top N unique issues
        $issues = array_unique($issues);
        $issues = array_slice($issues, 0, $limit);

        if (empty($issues)) {
            return null;
        }

        return implode("\n", $issues);
    }

    /**
     * Clean issue text - remove markdown formatting and extra spaces
     */
    protected function cleanIssueText(string $text): string
    {
        $text = trim($text);
        // Remove markdown bold/italic markers
        $text = preg_replace('/\*{1,2}([^*]+)\*{1,2}/', '$1', $text);
        // Remove leading dashes or bullets
        $text = preg_replace('/^[-•]\s*/', '', $text);
        // Clean up multiple spaces
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Truncate at word boundary
     */
    protected function truncateWords(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        $truncated = substr($text, 0, $length);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > $length * 0.6) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return $truncated . '...';
    }

    /**
     * Get user mention format
     */
    protected function getUserMention(string $username, ?string $email = null): string
    {
        // Try to find mapped ERP user
        $mapping = GithubUserMapping::whereRaw('LOWER(github_username) = ?', [strtolower($username)])->first();

        if ($mapping && $mapping->user) {
            // Use email for Google Chat mention if available
            $userEmail = $mapping->user->email ?? null;
            if ($userEmail) {
                return "<users/{$userEmail}> ({$username})";
            }
            return "*{$mapping->user->name}* ({$username})";
        }

        // Fallback to just the username
        return "*{$username}*";
    }

    /**
     * Send a raw message to Google Chat
     */
    public function sendMessage(array $message): bool
    {
        try {
            $response = Http::post($this->webhookUrl, $message);

            if ($response->successful()) {
                return true;
            }

            Log::error('Google Chat API error: ' . $response->status() . ' - ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Google Chat API exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send a simple text message
     */
    public function sendText(string $text): bool
    {
        return $this->sendMessage(['text' => $text]);
    }

    /**
     * Truncate text
     */
    protected function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length - 3) . '...';
    }

    /**
     * Test the webhook connection
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Google Chat webhook URL not configured',
            ];
        }

        $testMessage = [
            'text' => "🔧 *Test Message from Techland ERP*\n\nAI Code Review integration is working! ✅",
        ];

        $success = $this->sendMessage($testMessage);

        return [
            'success' => $success,
            'message' => $success ? 'Test message sent successfully' : 'Failed to send test message',
        ];
    }
}
