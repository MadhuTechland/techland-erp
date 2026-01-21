<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table for configuring which departments/roles should receive task reminders
        Schema::create('task_reminder_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('department'); // 'department', 'designation', 'user_type'
            $table->unsignedBigInteger('type_id')->nullable(); // department_id, designation_id, or null for user_type
            $table->string('type_name')->nullable(); // For user_type like 'employee', 'company'
            $table->boolean('should_receive')->default(true); // true = should receive, false = excluded
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['type', 'type_id', 'created_by']);
        });

        // Table for message templates
        Schema::create('task_reminder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'no_task_assigned', 'in_progress_reminder'
            $table->string('name');
            $table->text('message_template');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['type', 'created_by']);
        });

        // Table for reminder schedule settings
        Schema::create('task_reminder_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'no_task_assigned', 'in_progress_reminder'
            $table->time('scheduled_time'); // Time to run (e.g., 10:30:00, 18:30:00)
            $table->boolean('is_enabled')->default(true);
            $table->boolean('include_weekends')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['type', 'created_by']);
        });

        // Table to track sent reminders and responses
        Schema::create('task_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'no_task_assigned', 'in_progress_reminder'
            $table->unsignedBigInteger('user_id');
            $table->date('reminder_date');
            $table->text('message_sent');
            $table->boolean('response_received')->default(false);
            $table->text('response_message')->nullable();
            $table->timestamp('response_at')->nullable();
            $table->integer('task_count')->default(0); // Number of tasks at time of reminder
            $table->json('task_ids')->nullable(); // IDs of tasks in progress (for tracking)
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['user_id', 'reminder_date', 'type']);
            $table->index(['created_by', 'reminder_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_reminder_logs');
        Schema::dropIfExists('task_reminder_schedules');
        Schema::dropIfExists('task_reminder_templates');
        Schema::dropIfExists('task_reminder_recipients');
    }
};
