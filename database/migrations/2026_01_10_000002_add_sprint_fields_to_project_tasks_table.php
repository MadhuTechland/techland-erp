<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSprintFieldsToProjectTasksTable extends Migration
{
    public function up()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('project_tasks', 'sprint_id')) {
                $table->unsignedBigInteger('sprint_id')->nullable()->after('milestone_id');
                $table->foreign('sprint_id')->references('id')->on('sprints')->onDelete('set null');
                $table->index('sprint_id');
            }

            if (!Schema::hasColumn('project_tasks', 'story_points')) {
                $table->decimal('story_points', 5, 1)->nullable()->after('estimated_hrs');
            }

            if (!Schema::hasColumn('project_tasks', 'completed_at')) {
                $table->date('completed_at')->nullable()->after('marked_at');
            }

            if (!Schema::hasColumn('project_tasks', 'backlog_order')) {
                $table->integer('backlog_order')->default(0)->after('order');
            }
        });
    }

    public function down()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropForeign(['sprint_id']);
            $table->dropColumn(['sprint_id', 'story_points', 'completed_at', 'backlog_order']);
        });
    }
}
