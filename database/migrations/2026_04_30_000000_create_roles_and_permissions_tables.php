<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique(); // USER, VIP1, VIP2, ADMIN, SUPER_ADMIN
            $table->unsignedInteger('priority')->default(0); // optional ordering
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // human name
            $table->string('method')->default('GET'); // GET/POST/PUT/PATCH/DELETE/*
            $table->string('api_path'); // ex: api/users, api/admin/* (supports wildcard)
            $table->string('content')->nullable(); // mô tả/nội dung
            $table->timestamps();

            $table->index(['method', 'api_path']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });

        // Seed default roles
        $now = now();
        DB::table('roles')->insertOrIgnore([
            ['name' => 'User', 'slug' => 'USER', 'priority' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'VIP 1', 'slug' => 'VIP1', 'priority' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'VIP 2', 'slug' => 'VIP2', 'priority' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Admin', 'slug' => 'ADMIN', 'priority' => 90, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Super Admin', 'slug' => 'SUPER_ADMIN', 'priority' => 100, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};

