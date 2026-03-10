<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Dùng DB cho nhanh hoặc Model nếu bạn có

class EpisodeDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Cập nhật thông tin tập phim (Episode) có ID là 8174
        // Chúng ta xóa chữ TEST và đổi link embed
        DB::table('episodes')->where('id', 8174)->update([
            'name' => 'Tập 3: Cao trào',
            'slug' => 'tap-3-cao-trao',
            'embed_url' => 'https://www.youtube.com/embed/REAL_LINK_123',
            'updated_at' => now(),
        ]);

        // 2. Cập nhật thông tin bộ phim cha (Movie) của tập đó
        // Giả sử phim này đã hoàn thành, mình update status và số tập hiện tại
        DB::table('movies')->where('id', 1)->update([
            'status' => 'completed',
            'episode_current' => 'Tập 90/90',
            'quality' => 'FHD', // Nâng cấp chất lượng lên luôn
            'updated_at' => now(),
        ]);

        $this->command->info('Đã fix xong dữ liệu tập 8174 và bộ phim Ma Ri!');
    }
}
