<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organisation_id')
                ->constrained('organisations')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('display_name');
            $table->text('description')->nullable();

            // System roles are seeded/protected; custom roles may be managed
            // by authorised administrators.
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            $table->unique(['organisation_id', 'name'], 'uniq_roles_organisation_id_name');;

            // Required for tenant-safe composite relationships.
            $table->unique(['organisation_id', 'id'], 'uniq_roles_organisation_id_id');;
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('organisation_id');

            $table->foreignId('user_id');
            $table->foreignId('role_id');

            $table->timestamps();

            $table->primary(['user_id', 'role_id']);

            $table->index(['organisation_id', 'user_id'], 'idx_roles_organisation_id_user_id');;
            $table->index(['organisation_id', 'role_id'], 'idx_roles_organisation_id_role_id');;

            // Prevent cross-tenant user/role assignments at DB level.
            $table->foreign(['organisation_id', 'user_id'])
                ->references(['organisation_id', 'id'])
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign(['organisation_id', 'role_id'])
                ->references(['organisation_id', 'id'])
                ->on('roles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
