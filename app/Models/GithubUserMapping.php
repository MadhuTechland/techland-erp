<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GithubUserMapping Model
 *
 * Maps GitHub usernames to ERP users for linking commits
 * to internal user accounts.
 *
 * @property int $id
 * @property string $github_username
 * @property int $user_id
 */
class GithubUserMapping extends Model
{
    use HasFactory;

    protected $table = 'github_user_mappings';

    protected $fillable = [
        'github_username',
        'user_id',
    ];

    /**
     * Get the ERP user associated with this GitHub username.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Find user ID by GitHub username.
     */
    public static function getUserIdByGithubUsername(string $githubUsername): ?int
    {
        $mapping = self::where('github_username', $githubUsername)->first();
        return $mapping?->user_id;
    }

    /**
     * Find GitHub username by user ID.
     */
    public static function getGithubUsernameByUserId(int $userId): ?string
    {
        $mapping = self::where('user_id', $userId)->first();
        return $mapping?->github_username;
    }
}
