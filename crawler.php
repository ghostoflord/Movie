<?php
// crawler.php - Đặt ở thư mục gốc Laravel

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Movie;
use App\Models\Episode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

echo "=====================================\n";
echo "   CÔNG CỤ CÀO PHIM TỪ OPHIM\n";
echo "=====================================\n\n";

// Hỏi người dùng muốn crawl bao nhiêu trang
echo "Nhập số trang muốn crawl (mặc định 3): ";
$handle = fopen("php://stdin", "r");
$input = trim(fgets($handle));
$pages = is_numeric($input) ? (int)$input : 3;

echo "Bắt đầu crawl {$pages} trang...\n\n";

for ($page = 1; $page <= $pages; $page++) {
    echo "Trang {$page}/{$pages}...\n";

    // Gọi API lấy danh sách phim
    $response = Http::get("https://ophim1.com/danh-sach/phim-moi-cap-nhat", [
        'page' => $page
    ]);

    if (!$response->successful()) {
        echo "Lỗi kết nối trang {$page}\n";
        continue;
    }

    $data = $response->json();

    if (!isset($data['items'])) {
        echo "Không có dữ liệu\n";
        continue;
    }

    foreach ($data['items'] as $item) {
        $slug = $item['slug'] ?? null;
        if (!$slug) continue;

        echo "Đang xử lý: {$item['name']}... ";

        // Gọi API chi tiết phim
        $detailResponse = Http::get("https://ophim1.com/phim/{$slug}");

        if (!$detailResponse->successful()) {
            echo "Lỗi\n";
            continue;
        }

        $detail = $detailResponse->json();

        if (!isset($detail['movie'])) {
            echo "Dữ liệu lỗi\n";
            continue;
        }

        $movieData = $detail['movie'];
        $episodes = $detail['episodes'] ?? [];

        DB::beginTransaction();

        try {
            // Tìm hoặc tạo phim
            $movie = Movie::updateOrCreate(
                ['slug' => $movieData['slug']],
                [
                    'name' => $movieData['name'],
                    'origin_name' => $movieData['origin_name'] ?? null,
                    'thumb_url' => $movieData['thumb_url'] ?? null,
                    'poster_url' => $movieData['poster_url'] ?? null,
                    'description' => $movieData['content'] ?? null,
                    'year' => $movieData['year'] ?? null,
                    'quality' => $movieData['quality'] ?? null,
                    'language' => $movieData['language'] ?? null,
                    'status' => $movieData['status'] ?? 'ongoing',
                    'episode_current' => $movieData['episode_current'] ?? null,
                    'episode_total' => $movieData['episode_total'] ?? null,
                ]
            );

            $episodeCount = 0;

            // Xử lý episodes
            foreach ($episodes as $episodeGroup) {
                if (!isset($episodeGroup['server_data'])) continue;

                foreach ($episodeGroup['server_data'] as $ep) {
                    // Lấy số tập từ tên
                    preg_match('/(\d+)/', $ep['name'], $matches);
                    $epNumber = $matches[1] ?? 1;

                    Episode::updateOrCreate(
                        [
                            'movie_id' => $movie->id,
                            'episode_number' => $epNumber
                        ],
                        [
                            'name' => $ep['name'],
                            'slug' => "tap-{$epNumber}",
                            'embed_url' => $ep['link_embed'] ?? $ep['link_m3u8'] ?? '',
                            'episode_number' => $epNumber
                        ]
                    );
                    $episodeCount++;
                }
            }

            DB::commit();
            echo "{$episodeCount} tập\n";
        } catch (\Exception $e) {
            DB::rollBack();
            echo "Lỗi: " . $e->getMessage() . "\n";
        }

        // Delay 1 giây để tránh quá tải API
        sleep(1);
    }

    // Delay giữa các trang
    if ($page < $pages) {
        echo "Chờ 2 giây trước trang tiếp theo...\n";
        sleep(2);
    }
}

echo "\n=====================================\n";
echo "      HOÀN THÀNH!\n";
echo "=====================================\n";

// Hiển thị thống kê
$totalMovies = Movie::count();
$totalEpisodes = Episode::count();

echo "Tổng số phim: {$totalMovies}\n";
echo "Tổng số tập: {$totalEpisodes}\n";
