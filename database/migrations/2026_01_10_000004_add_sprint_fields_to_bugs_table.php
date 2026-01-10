<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bugs', function (Blueprint $table) {
            // Sprint integration - only add if not exists
            if (!Schema::hasColumn('bugs', 'sprint_id')) {
                $table->unsignedBigInteger('sprint_id')->nullable()->after('project_id');
            }
            if (!Schema::hasColumn('bugs', 'milestone_id')) {
                $table->unsignedBigInteger('milestone_id')->nullable()->after('sprint_id');
            }
            if (!Schema::hasColumn('bugs', 'story_points')) {
                $table->decimal('story_points', 5, 1)->nullable()->after('priority');
            }
            if (!Schema::hasColumn('bugs', 'backlog_order')) {
                $table->integer('backlog_order')->default(0)->after('story_points');
            }

            // Performance tracking fields
            if (!Schema::hasColumn('bugs', 'severity')) {
                $table->string('severity')->default('minor')->after('priority');
            }
            if (!Schema::hasColumn('bugs', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('bugs', 'resolution_time_hours')) {
                $table->integer('resolution_time_hours')->nullable()->after('resolved_at');
            }
            if (!Schema::hasColumn('bugs', 'resolution_type')) {
                $table->string('resolution_type')->nullable()->after('resolution_time_hours');
            }
            if (!Schema::hasColumn('bugs', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('resolution_type');
            }

            // For linking to tasks/user stories
            if (!Schema::hasColumn('bugs', 'related_task_id')) {
                $table->unsignedBigInteger('related_task_id')->nullable()->after('resolved_by');
            }
        });

        // Add indexes - use raw SQL to check existence
        $indexes = collect(DB::select("SHOW INDEX FROM bugs"))->pluck('Key_name')->unique()->toArray();

        if (!in_array('bugs_sprint_id_index', $indexes)) {
            Schema::table('bugs', function (Blueprint $table) {
                $table->index('sprint_id');
            });
        }
        if (!in_array('bugs_milestone_id_index', $indexes)) {
            Schema::table('bugs', function (Blueprint $table) {
                $table->index('milestone_id');
            });
        }
        if (!in_array('bugs_resolved_by_index', $indexes)) {
            Schema::table('bugs', function (Blueprint $table) {
                $table->index('resolved_by');
            });
        }
        if (!in_array('bugs_related_task_id_index', $indexes)) {
            Schema::table('bugs', function (Blueprint $table) {
                $table->index('related_task_id');
            });
        }
        if (!in_array('bugs_severity_index', $indexes)) {
            Schema::table('bugs', function (Blueprint $table) {
                $table->index('severity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = collect(DB::select("SHOW INDEX FROM bugs"))->pluck('Key_name')->unique()->toArray();

        Schema::table('bugs', function (Blueprint $table) use ($indexes) {
            if (in_array('bugs_sprint_id_index', $indexes)) {
                $table->dropIndex(['sprint_id']);
            }
            if (in_array('bugs_milestone_id_index', $indexes)) {
                $table->dropIndex(['milestone_id']);
            }
            if (in_array('bugs_resolved_by_index', $indexes)) {
                $table->dropIndex(['resolved_by']);
            }
            if (in_array('bugs_related_task_id_index', $indexes)) {
                $table->dropIndex(['related_task_id']);
            }
            if (in_array('bugs_severity_index', $indexes)) {
                $table->dropIndex(['severity']);
            }

            // Drop columns if they exist
            $columns = Schema::getColumnListing('bugs');
            $dropColumns = array_intersect($columns, [
                'sprint_id',
                'milestone_id',
                'story_points',
                'backlog_order',
                'severity',
                'resolved_at',
                'resolution_time_hours',
                'resolution_type',
                'resolved_by',
                'related_task_id'
            ]);

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
