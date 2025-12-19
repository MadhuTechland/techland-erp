<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IssueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $issueTypes = [
            [
                'name' => 'Epic',
                'key' => 'EPIC',
                'icon' => 'ti ti-bolt',
                'color' => 'purple',
                'description' => 'A large body of work that can be broken down into smaller stories',
                'is_active' => true,
                'is_subtask' => false,
                'order' => 1,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Story',
                'key' => 'STORY',
                'icon' => 'ti ti-bookmark',
                'color' => 'success',
                'description' => 'A user story that delivers value to the end user',
                'is_active' => true,
                'is_subtask' => false,
                'order' => 2,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Task',
                'key' => 'TASK',
                'icon' => 'ti ti-checkbox',
                'color' => 'primary',
                'description' => 'A task that needs to be done',
                'is_active' => true,
                'is_subtask' => false,
                'order' => 3,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bug',
                'key' => 'BUG',
                'icon' => 'ti ti-bug',
                'color' => 'danger',
                'description' => 'A problem which impairs or prevents proper function',
                'is_active' => true,
                'is_subtask' => false,
                'order' => 4,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sub-task',
                'key' => 'SUBTASK',
                'icon' => 'ti ti-subtask',
                'color' => 'info',
                'description' => 'A smaller piece of work that is part of a larger issue',
                'is_active' => true,
                'is_subtask' => true,
                'order' => 5,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('issue_types')->insert($issueTypes);
    }
}
