<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    public function up()
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên phim
            $table->string('origin_name')->nullable(); // Tên gốc
            $table->string('slug')->unique(); // Slug cho URL
            $table->string('thumb_url')->nullable(); // Ảnh thumbnail
            $table->string('poster_url')->nullable(); // Poster
            $table->text('description')->nullable(); // Mô tả
            $table->integer('year')->nullable(); // Năm sản xuất
            $table->string('quality')->nullable(); // Chất lượng
            $table->string('language')->nullable(); // Ngôn ngữ
            $table->json('categories')->nullable(); // Thể loại (JSON)
            $table->json('actors')->nullable(); // Diễn viên (JSON)
            $table->json('directors')->nullable(); // Đạo diễn (JSON)
            $table->string('status')->default('ongoing'); // Trạng thái
            $table->string('episode_current')->nullable(); // Tập hiện tại
            $table->string('episode_total')->nullable(); // Tổng số tập
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
