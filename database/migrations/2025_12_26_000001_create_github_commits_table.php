<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for GitHub Commits tracking table.
 *
 * This table stores commit data received from GitHub webhooks
 * for developer activity tracking purposes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('github_commits')) {
            return;
        }

        Schema::create('github_commits', function (Blueprint $table) {
            $table->id();

            // GitHub author information
            $table->string('github_username', 100)->index();

            // Repository information
            $table->string('repo_name', 255)->index();

            // Commit identification - must be unique to prevent duplicates
            $table->string('commit_sha', 40)->unique();

            // Commit details
            $table->text('commit_message');

            // Activity metrics
            $table->unsignedInteger('files_changed')->default(0);
            $table->unsignedInteger('lines_added')->default(0);
            $table->unsignedInteger('lines_deleted')->default(0);

            // Timestamp when the commit was made (from GitHub)
            $table->timestamp('committed_at')->nullable()->index();

            // Optional: Link to ERP user if mapping exists
            // Assumption: The existing users table has an 'id' column
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Laravel timestamps
            $table->timestamps();

            // Composite index for common queries
            $table->index(['github_username', 'committed_at']);
            $table->index(['repo_name', 'committed_at']);
        });

        // Optional: Create a mapping table for GitHub usernames to ERP users
        if (Schema::hasTable('github_user_mappings')) {
            return;
        }

        Schema::create('github_user_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('github_username', 100)->unique();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_user_mappings');
        Schema::dropIfExists('github_commits');
    }
};
