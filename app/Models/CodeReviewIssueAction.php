<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeReviewIssueAction extends Model
{
    protected $fillable = [
        'code_review_id',
        'issue_index',
        'action',
        'developer_comment',
        'actioned_by',
        'actioned_at',
    ];

    protected $casts = [
        'actioned_at' => 'datetime',
    ];

    public function codeReview(): BelongsTo
    {
        return $this->belongsTo(CodeReview::class);
    }

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public static function getActionLabels(): array
    {
        return [
            'implemented' => 'Implemented',
            'rejected' => 'Rejected',
            'will_fix_later' => 'Will Fix Later',
            'not_applicable' => 'Not Applicable',
        ];
    }

    public static function getActionColors(): array
    {
        return [
            'implemented' => 'success',
            'rejected' => 'danger',
            'will_fix_later' => 'warning',
            'not_applicable' => 'secondary',
        ];
    }
}
