<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IssueType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'icon',
        'color',
        'description',
        'is_active',
        'is_subtask',
        'order',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_subtask' => 'boolean',
    ];

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'issue_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotSubtask($query)
    {
        return $query->where('is_subtask', false);
    }
}
