<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * is_container = true means this is a container type (Epic, Story, Milestone)
     * Container types don't have their own estimated hours - they aggregate from children
     * Only work items (Task, Bug, Sub-task) should have estimated hours that count toward project total
     */
    public function up(): void
    {
        Schema::table('issue_types', function (Blueprint $table) {
            $table->boolean('is_container')->default(false)->after('is_subtask');
        });

        // Update Epic and Story to be containers
        DB::table('issue_types')->whereIn('key', ['EPIC', 'STORY'])->update(['is_container' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_types', function (Blueprint $table) {
            $table->dropColumn('is_container');
        });
    }
};
