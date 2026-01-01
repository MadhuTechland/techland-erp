<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('code_review_issue_actions')) {
            Schema::create('code_review_issue_actions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('code_review_id');
                $table->integer('issue_index'); // Index of the issue in the issues_found array
                $table->enum('action', ['implemented', 'rejected', 'will_fix_later', 'not_applicable'])->default('implemented');
                $table->text('developer_comment')->nullable();
                $table->unsignedBigInteger('actioned_by')->nullable(); // User who took action
                $table->timestamp('actioned_at')->nullable();
                $table->timestamps();

                $table->foreign('code_review_id')->references('id')->on('code_reviews')->onDelete('cascade');
                $table->foreign('actioned_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['code_review_id', 'issue_index']);
            });
        }

        // Add email column to github_user_mappings if not exists
        if (Schema::hasTable('github_user_mappings') && !Schema::hasColumn('github_user_mappings', 'github_email')) {
            Schema::table('github_user_mappings', function (Blueprint $table) {
                $table->string('github_email')->nullable()->after('github_username');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('code_review_issue_actions');

        if (Schema::hasColumn('github_user_mappings', 'github_email')) {
            Schema::table('github_user_mappings', function (Blueprint $table) {
                $table->dropColumn('github_email');
            });
        }
    }
};
