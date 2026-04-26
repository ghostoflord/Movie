<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actors', function (Blueprint $table) {
            if (! Schema::hasColumn('actors', 'ophim_id')) {
                $table->string('ophim_id')->nullable()->unique()->after('id');
            }

            // Crawl chỉ có name → cho phép nullable để không bị kẹt.
            $table->string('bio')->nullable()->change();
            $table->string('avatar')->nullable()->change();
            $table->string('birth_date')->nullable()->change();
        });

        Schema::create('actor_movie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['movie_id', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actor_movie');

        Schema::table('actors', function (Blueprint $table) {
            if (Schema::hasColumn('actors', 'ophim_id')) {
                $table->dropColumn('ophim_id');
            }
        });
    }
};

