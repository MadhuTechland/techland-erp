<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Focus sessions - individual pomodoro sessions
        Schema::create('focus_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->integer('planned_duration')->default(25); // minutes
            $table->integer('actual_duration')->default(0); // minutes
            $table->enum('status', ['active', 'completed', 'abandoned', 'paused'])->default('active');
            $table->string('tree_type')->default('oak'); // tree variety
            $table->integer('points_earned')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->integer('pause_duration')->default(0); // total paused seconds
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('task_id')->references('id')->on('project_tasks')->onDelete('set null');

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        // Daily aggregated stats for quick queries
        Schema::create('focus_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->integer('total_sessions')->default(0);
            $table->integer('completed_sessions')->default(0);
            $table->integer('abandoned_sessions')->default(0);
            $table->integer('total_focus_minutes')->default(0);
            $table->integer('points_earned')->default(0);
            $table->integer('current_streak')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'date']);
        });

        // User settings for focus timer
        Schema::create('focus_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->integer('focus_duration')->default(25); // minutes
            $table->integer('short_break')->default(5); // minutes
            $table->integer('long_break')->default(15); // minutes
            $table->integer('sessions_until_long_break')->default(4);
            $table->integer('daily_goal')->default(8); // sessions
            $table->boolean('auto_start_breaks')->default(false);
            $table->boolean('auto_start_focus')->default(false);
            $table->boolean('sound_enabled')->default(true);
            $table->boolean('notifications_enabled')->default(true);
            $table->string('preferred_tree')->default('oak');
            $table->integer('total_points')->default(0);
            $table->integer('total_trees')->default(0);
            $table->integer('level')->default(1);
            $table->integer('longest_streak')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Achievements/Badges
        Schema::create('focus_achievements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('achievement_key'); // e.g., 'first_tree', 'week_streak', etc.
            $table->string('achievement_name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('points_awarded')->default(0);
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'achievement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('focus_achievements');
        Schema::dropIfExists('focus_settings');
        Schema::dropIfExists('focus_daily_stats');
        Schema::dropIfExists('focus_sessions');
    }
};
