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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('original_title');
            $table->string('slug');
            $table->string('description');
            $table->string('duration');
            $table->string('language');
            $table->string('country');
            $table->string('release_year');
            $table->string('poster_url');
            $table->string('trailer_url');
            $table->string('views');
            $table->string('is_featured')->default('NONE');
            $table->string('status')->default('UPCOMING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
