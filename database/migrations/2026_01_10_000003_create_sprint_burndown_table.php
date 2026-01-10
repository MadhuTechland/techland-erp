<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSprintBurndownTable extends Migration
{
    public function up()
    {
        Schema::create('sprint_burndown', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sprint_id');
            $table->date('date');
            $table->decimal('total_points', 8, 1)->default(0);
            $table->decimal('completed_points', 8, 1)->default(0);
            $table->decimal('remaining_points', 8, 1)->default(0);
            $table->integer('total_tasks')->default(0);
            $table->integer('completed_tasks')->default(0);
            $table->timestamps();

            $table->foreign('sprint_id')->references('id')->on('sprints')->onDelete('cascade');
            $table->unique(['sprint_id', 'date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('sprint_burndown');
    }
}
