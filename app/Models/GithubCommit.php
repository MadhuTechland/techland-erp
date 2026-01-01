<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * GithubCommit Model
 *
 * Represents a single commit received from GitHub webhooks.
 * Used for tracking developer activity (not performance evaluation).
 *
 * @property int $id
 * @property string $github_username
 * @property string $repo_name
 * @property string $commit_sha
 * @property string $commit_message
 * @property int $files_changed
 * @property int $lines_added
 * @property int $lines_deleted
 * @property Carbon $committed_at
 * @property int|null $user_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class GithubCommit extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'github_commits';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'github_username',
        'author_email',
        'repo_name',
        'commit_sha',
        'commit_message',
        'branch',
        'files_changed',
        'lines_added',
        'lines_deleted',
        'committed_at',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'files_changed' => 'integer',
        'lines_added' => 'integer',
        'lines_deleted' => 'integer',
        'committed_at' => 'datetime',
    ];

    /**
     * Get the total lines changed (added + deleted).
     */
    public function getTotalLinesChangedAttribute(): int
    {
        return $this->lines_added + $this->lines_deleted;
    }

    /**
     * Get the linked ERP user if mapping exists.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to filter commits by GitHub username.
     */
    public function scopeByUsername($query, string $username)
    {
        return $query->where('github_username', $username);
    }

    /**
     * Scope to filter commits by repository.
     */
    public function scopeByRepo($query, string $repoName)
    {
        return $query->where('repo_name', $repoName);
    }

    /**
     * Scope to filter commits by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('committed_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay(),
        ]);
    }

    /**
     * Scope to filter commits for a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        $date = Carbon::parse($date);
        return $query->whereDate('committed_at', $date);
    }

    /**
     * Scope to filter commits for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('committed_at', Carbon::today());
    }

    /**
     * Scope to filter commits for this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('committed_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ]);
    }

    /**
     * Scope to filter commits for this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('committed_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ]);
    }

    /**
     * Scope to filter by linked ERP user.
     */
    public function scopeByUserId($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if a commit with this SHA already exists.
     */
    public static function existsBySha(string $sha): bool
    {
        return self::where('commit_sha', $sha)->exists();
    }

    /**
     * Get unique GitHub usernames.
     */
    public static function getUniqueUsernames(): array
    {
        return self::distinct()->pluck('github_username')->toArray();
    }

    /**
     * Get unique repositories.
     */
    public static function getUniqueRepos(): array
    {
        return self::distinct()->pluck('repo_name')->toArray();
    }
}
