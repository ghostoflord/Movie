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
        Schema::create('watch_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('episode_id')
                ->constrained()
                ->onDelete('cascade');
            $table->integer('current_time')->default(0); // giây đang xem
            $table->integer('duration_watched')->default(0); // tổng thời gian đã xem

            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_history');
    }
};
