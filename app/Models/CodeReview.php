<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CodeReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'repo_name',
        'branch',
        'commit_sha',
        'commit_message',
        'author_username',
        'author_email',
        'user_id',
        'code_diff',
        'ai_review',
        'issues_found',
        'issues_count',
        'critical_count',
        'warning_count',
        'info_count',
        'files_changed',
        'lines_added',
        'lines_deleted',
        'status',
        'sent_to_chat',
        'reviewed_at',
    ];

    protected $casts = [
        'issues_found' => 'array',
        'sent_to_chat' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issueActions(): HasMany
    {
        return $this->hasMany(CodeReviewIssueAction::class);
    }

    public function getIssueAction(int $issueIndex): ?CodeReviewIssueAction
    {
        return $this->issueActions()->where('issue_index', $issueIndex)->first();
    }

    public function scopeByRepo($query, string $repo)
    {
        return $query->where('repo_name', $repo);
    }

    public function scopeByBranch($query, string $branch)
    {
        return $query->where('branch', $branch);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeWithIssues($query)
    {
        return $query->where('issues_count', '>', 0);
    }
}
