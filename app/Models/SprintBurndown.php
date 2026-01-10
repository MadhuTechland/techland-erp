<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SprintBurndown extends Model
{
    protected $table = 'sprint_burndown';

    protected $fillable = [
        'sprint_id',
        'date',
        'total_points',
        'completed_points',
        'remaining_points',
        'total_tasks',
        'completed_tasks',
    ];

    protected $casts = [
        'date' => 'date',
        'total_points' => 'float',
        'completed_points' => 'float',
        'remaining_points' => 'float',
    ];

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }
}
