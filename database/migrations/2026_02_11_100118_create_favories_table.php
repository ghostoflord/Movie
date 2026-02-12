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
        Schema::create('favories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id') // User (1) ---- (n) Favorites
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('movie_id') // Favorites (n) ---- (1) Movie
                ->constrained()
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favories');
    }
};
