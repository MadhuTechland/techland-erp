<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Generate UUIDs for existing projects
        $projects = DB::table('projects')->get();
        foreach ($projects as $project) {
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['uuid' => Str::uuid()]);
        }

        // Make UUID unique and not nullable
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->nullable(false)->change();
        });

        // Add new permissions for budget and expense visibility
        $permissions = [
            ['name' => 'view project budget', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view project expense summary', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore($permission);
        }

        // Assign these permissions to company role by default
        $companyRole = DB::table('roles')->where('name', 'company')->first();
        if ($companyRole) {
            $newPermissions = DB::table('permissions')
                ->whereIn('name', ['view project budget', 'view project expense summary'])
                ->get();

            foreach ($newPermissions as $permission) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->id,
                    'role_id' => $companyRole->id,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        // Remove permissions
        DB::table('permissions')
            ->whereIn('name', ['view project budget', 'view project expense summary'])
            ->delete();
    }
};
