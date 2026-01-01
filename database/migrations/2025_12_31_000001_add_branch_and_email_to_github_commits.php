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
        Schema::table('github_commits', function (Blueprint $table) {
            if (!Schema::hasColumn('github_commits', 'branch')) {
                $table->string('branch', 255)->nullable()->after('commit_message');
            }
            if (!Schema::hasColumn('github_commits', 'author_email')) {
                $table->string('author_email', 255)->nullable()->after('github_username');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('github_commits', function (Blueprint $table) {
            if (Schema::hasColumn('github_commits', 'branch')) {
                $table->dropColumn('branch');
            }
            if (Schema::hasColumn('github_commits', 'author_email')) {
                $table->dropColumn('author_email');
            }
        });
    }
};
