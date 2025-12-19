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
        Schema::create('issue_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 10);
            $table->string('icon')->nullable();
            $table->string('color', 20)->default('primary');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_subtask')->default(false);
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('created_by')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_types');
    }
};
