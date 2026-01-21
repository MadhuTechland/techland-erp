<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskReminderRecipient extends Model
{
    protected $fillable = [
        'type',
        'type_id',
        'type_name',
        'should_receive',
        'created_by',
    ];

    protected $casts = [
        'should_receive' => 'boolean',
    ];

    // Type constants
    const TYPE_DEPARTMENT = 'department';
    const TYPE_DESIGNATION = 'designation';
    const TYPE_USER_TYPE = 'user_type';
    const TYPE_USER = 'user';

    /**
     * Get the department if type is department
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'type_id');
    }

    /**
     * Get the designation if type is designation
     */
    public function designation()
    {
        return $this->belongsTo(Designation::class, 'type_id');
    }

    /**
     * Scope for a specific creator
     */
    public function scopeForCreator($query, $creatorId)
    {
        return $query->where('created_by', $creatorId);
    }

    /**
     * Scope for recipients that should receive reminders
     */
    public function scopeShouldReceive($query)
    {
        return $query->where('should_receive', true);
    }

    /**
     * Scope for recipients that should NOT receive reminders
     */
    public function scopeExcluded($query)
    {
        return $query->where('should_receive', false);
    }

    /**
     * Get all users who should receive task reminders
     */
    public static function getEligibleUsers($creatorId)
    {
        // Get excluded departments
        $excludedDepartments = self::forCreator($creatorId)
            ->where('type', self::TYPE_DEPARTMENT)
            ->excluded()
            ->pluck('type_id')
            ->toArray();

        // Get excluded designations
        $excludedDesignations = self::forCreator($creatorId)
            ->where('type', self::TYPE_DESIGNATION)
            ->excluded()
            ->pluck('type_id')
            ->toArray();

        // Get excluded user types
        $excludedUserTypes = self::forCreator($creatorId)
            ->where('type', self::TYPE_USER_TYPE)
            ->excluded()
            ->pluck('type_name')
            ->toArray();

        // Get excluded individual users
        $excludedUsers = self::forCreator($creatorId)
            ->where('type', self::TYPE_USER)
            ->excluded()
            ->pluck('type_id')
            ->toArray();

        // Get eligible users - use same pattern as rest of the codebase
        $query = User::where('created_by', $creatorId)
            ->where('type', '!=', 'client')
            ->where('type', '!=', 'company')
            ->where('type', '!=', 'super admin');

        // Apply additional user type exclusions if configured
        if (!empty($excludedUserTypes)) {
            $query->whereNotIn('type', $excludedUserTypes);
        }

        // Exclude specific users
        if (!empty($excludedUsers)) {
            $query->whereNotIn('id', $excludedUsers);
        }

        // Get users and filter by department/designation via Employee
        $users = $query->get();

        // Further filter by department and designation (only if exclusions are configured)
        $eligibleUsers = $users->filter(function ($user) use ($excludedDepartments, $excludedDesignations) {
            // If no department or designation exclusions, include all users
            if (empty($excludedDepartments) && empty($excludedDesignations)) {
                return true;
            }

            $employee = Employee::where('user_id', $user->id)->first();

            // If no employee record, include the user (they can't be excluded by dept/designation)
            if (!$employee) {
                return true;
            }

            // Check department exclusion
            if (!empty($excludedDepartments) && in_array($employee->department_id, $excludedDepartments)) {
                return false;
            }

            // Check designation exclusion
            if (!empty($excludedDesignations) && in_array($employee->designation_id, $excludedDesignations)) {
                return false;
            }

            return true;
        });

        return $eligibleUsers;
    }

    /**
     * Get the user if type is user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'type_id');
    }

    /**
     * Get display name for the recipient configuration
     */
    public function getDisplayNameAttribute()
    {
        switch ($this->type) {
            case self::TYPE_DEPARTMENT:
                return $this->department ? $this->department->name : 'Unknown Department';
            case self::TYPE_DESIGNATION:
                return $this->designation ? $this->designation->name : 'Unknown Designation';
            case self::TYPE_USER_TYPE:
                return ucfirst($this->type_name);
            case self::TYPE_USER:
                return $this->user ? $this->user->name : 'Unknown User';
            default:
                return 'Unknown';
        }
    }
}
