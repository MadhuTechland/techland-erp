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
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('issue_type_id')->nullable()->after('id');
            $table->string('issue_key', 50)->nullable()->unique()->after('issue_type_id');
            $table->unsignedBigInteger('parent_id')->nullable()->after('project_id');

            $table->foreign('issue_type_id')->references('id')->on('issue_types')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('project_tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropForeign(['issue_type_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['issue_type_id', 'issue_key', 'parent_id']);
        });
    }
};
