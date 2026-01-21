<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackPhoto extends Model
{
    protected $fillable = [
        'track_id',
        'user_id',
        'img_path',
        'time',
        'status',
        'created_by',
    ];

    protected $casts = [
        'time' => 'datetime',
    ];

    public function tracker()
    {
        return $this->belongsTo(TimeTracker::class, 'track_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
