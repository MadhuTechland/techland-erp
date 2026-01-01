<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('code_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('repo_name', 255)->index();
            $table->string('branch', 255);
            $table->string('commit_sha', 40)->index();
            $table->string('commit_message', 500)->nullable();
            $table->string('author_username', 100);
            $table->string('author_email', 255)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Review content
            $table->longText('code_diff')->nullable();
            $table->longText('ai_review')->nullable();
            $table->json('issues_found')->nullable();
            $table->unsignedInteger('issues_count')->default(0);
            $table->unsignedInteger('critical_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->unsignedInteger('info_count')->default(0);

            // Stats
            $table->unsignedInteger('files_changed')->default(0);
            $table->unsignedInteger('lines_added')->default(0);
            $table->unsignedInteger('lines_deleted')->default(0);

            // Status
            $table->enum('status', ['pending', 'reviewing', 'completed', 'failed'])->default('pending');
            $table->boolean('sent_to_chat')->default(false);
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_reviews');
    }
};
