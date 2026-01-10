<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'created_by',
    ];

    /**
     * Get the user who created this skill option
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get skills grouped by category
     */
    public static function getGroupedByCategory($creatorId = null)
    {
        $query = self::orderBy('category')->orderBy('name');

        if ($creatorId) {
            $query->where(function($q) use ($creatorId) {
                $q->where('created_by', $creatorId)
                  ->orWhere('created_by', 1); // Include system defaults
            });
        }

        return $query->get()->groupBy('category');
    }

    /**
     * Get all skills as flat list
     */
    public static function getAll($creatorId = null)
    {
        $query = self::orderBy('name');

        if ($creatorId) {
            $query->where(function($q) use ($creatorId) {
                $q->where('created_by', $creatorId)
                  ->orWhere('created_by', 1);
            });
        }

        return $query->get();
    }
}
