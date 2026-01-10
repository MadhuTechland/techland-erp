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
        // Skill options master table
        Schema::create('skill_options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category')->nullable(); // Backend, Frontend, Mobile, DevOps, etc.
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Project team skills - per-project skill assignment
        Schema::create('project_team_skills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->json('skills')->nullable();
            $table->string('experience')->nullable();
            $table->string('role')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // BRD documents table
        Schema::create('brd_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('project_name')->nullable();
            $table->text('project_description')->nullable();
            $table->string('file_path');
            $table->string('original_name');
            $table->longText('extracted_text')->nullable();
            $table->json('team_data')->nullable();
            $table->json('milestone_data')->nullable();
            $table->json('parsed_data')->nullable();
            $table->enum('status', ['uploaded', 'team_setup', 'milestones_setup', 'processing', 'parsed', 'generated', 'failed'])->default('uploaded');
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        // Seed default skill options
        $defaultSkills = [
            ['name' => 'Laravel', 'category' => 'Backend'],
            ['name' => 'PHP', 'category' => 'Backend'],
            ['name' => 'Node.js', 'category' => 'Backend'],
            ['name' => 'Python', 'category' => 'Backend'],
            ['name' => 'Java', 'category' => 'Backend'],
            ['name' => 'C#', 'category' => 'Backend'],
            ['name' => '.NET', 'category' => 'Backend'],
            ['name' => 'Ruby on Rails', 'category' => 'Backend'],
            ['name' => 'Go', 'category' => 'Backend'],
            ['name' => 'React', 'category' => 'Frontend'],
            ['name' => 'Vue.js', 'category' => 'Frontend'],
            ['name' => 'Angular', 'category' => 'Frontend'],
            ['name' => 'JavaScript', 'category' => 'Frontend'],
            ['name' => 'TypeScript', 'category' => 'Frontend'],
            ['name' => 'HTML/CSS', 'category' => 'Frontend'],
            ['name' => 'Tailwind CSS', 'category' => 'Frontend'],
            ['name' => 'Flutter', 'category' => 'Mobile'],
            ['name' => 'React Native', 'category' => 'Mobile'],
            ['name' => 'Swift', 'category' => 'Mobile'],
            ['name' => 'Kotlin', 'category' => 'Mobile'],
            ['name' => 'Android', 'category' => 'Mobile'],
            ['name' => 'iOS', 'category' => 'Mobile'],
            ['name' => 'MySQL', 'category' => 'Database'],
            ['name' => 'PostgreSQL', 'category' => 'Database'],
            ['name' => 'MongoDB', 'category' => 'Database'],
            ['name' => 'Redis', 'category' => 'Database'],
            ['name' => 'AWS', 'category' => 'DevOps'],
            ['name' => 'Docker', 'category' => 'DevOps'],
            ['name' => 'Kubernetes', 'category' => 'DevOps'],
            ['name' => 'CI/CD', 'category' => 'DevOps'],
            ['name' => 'Git', 'category' => 'DevOps'],
            ['name' => 'UI/UX Design', 'category' => 'Design'],
            ['name' => 'Figma', 'category' => 'Design'],
            ['name' => 'REST API', 'category' => 'Backend'],
            ['name' => 'GraphQL', 'category' => 'Backend'],
            ['name' => 'Testing', 'category' => 'QA'],
            ['name' => 'Selenium', 'category' => 'QA'],
        ];

        foreach ($defaultSkills as $skill) {
            \DB::table('skill_options')->insert([
                'name' => $skill['name'],
                'category' => $skill['category'],
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brd_documents');
        Schema::dropIfExists('project_team_skills');
        Schema::dropIfExists('skill_options');
    }
};
