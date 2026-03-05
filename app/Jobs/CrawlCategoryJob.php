<?php

namespace App\Jobs;

use App\Models\Movie;
use App\Models\Episode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use GuzzleHttp\Client;                // Thay vì Http facade
use GuzzleHttp\Exception\RequestException;

class CrawlCategoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 phút
    public $tries = 3;      // Thử lại 3 lần nếu thất bại

    protected $categorySlug;
    protected $pages;

    public function __construct(string $categorySlug, int $pages = 3)
    {
        $this->categorySlug = $categorySlug;
        $this->pages = $pages;
    }

    public function handle()
    {
        Log::info("Bắt đầu crawl thể loại [{$this->categorySlug}] - {$this->pages} trang");

        // Tạo Guzzle client với User-Agent
        $client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);

        for ($page = 1; $page <= $this->pages; $page++) {
            Log::info("Crawl thể loại {$this->categorySlug} - trang {$page}");

            try {
                // Gọi API danh sách phim theo thể loại
                $response = $client->get("https://ophim1.com/v1/api/the-loai/{$this->categorySlug}?page={$page}", [
                    'query' => ['page' => $page]
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    Log::error("Lỗi kết nối thể loại {$this->categorySlug} trang {$page}, status: " . $statusCode);
                    continue;
                }

                $data = json_decode($response->getBody()->getContents(), true);

                if (!isset($data['data']['items']) || empty($data['data']['items'])) {
                    Log::warning("Không có phim ở thể loại {$this->categorySlug} trang {$page}");
                    continue;
                }

                foreach ($data['data']['items'] as $item) {
                    $slug = $item['slug'] ?? null;
                    if (!$slug) continue;

                    Log::info("Xử lý phim: " . ($item['name'] ?? 'Không tên'));

                    try {
                        // Gọi API chi tiết phim để lấy thông tin đầy đủ và tập
                        $detailResponse = $client->get("https://ophim1.com/phim/{$slug}");

                        if ($detailResponse->getStatusCode() !== 200) {
                            Log::error("Lỗi lấy chi tiết phim {$slug}");
                            continue;
                        }

                        $detail = json_decode($detailResponse->getBody()->getContents(), true);

                        if (!isset($detail['movie'])) {
                            Log::error("Dữ liệu phim {$slug} không hợp lệ");
                            continue;
                        }

                        $movieData = $detail['movie'];
                        $episodes = $detail['episodes'] ?? [];

                        DB::beginTransaction();

                        try {
                            // Lưu hoặc cập nhật phim
                            $movie = Movie::updateOrCreate(
                                ['slug' => $movieData['slug']],
                                [
                                    'name'           => $movieData['name'] ?? null,
                                    'origin_name'    => $movieData['origin_name'] ?? null,
                                    'thumb_url'      => $movieData['thumb_url'] ?? null,
                                    'poster_url'     => $movieData['poster_url'] ?? null,
                                    'description'    => $movieData['content'] ?? null,
                                    'year'           => $movieData['year'] ?? null,
                                    'quality'        => $movieData['quality'] ?? null,
                                    'language'       => $movieData['language'] ?? null,
                                    'status'         => $movieData['status'] ?? 'ongoing',
                                    'episode_current' => $movieData['episode_current'] ?? null,
                                    'episode_total'  => $movieData['episode_total'] ?? null,
                                ]
                            );

                            $episodeCount = 0;

                            // Xử lý tập phim
                            foreach ($episodes as $episodeGroup) {
                                if (!isset($episodeGroup['server_data'])) continue;

                                foreach ($episodeGroup['server_data'] as $ep) {
                                    preg_match('/(\d+)/', $ep['name'], $matches);
                                    $epNumber = $matches[1] ?? 1;

                                    Episode::updateOrCreate(
                                        [
                                            'movie_id'       => $movie->id,
                                            'episode_number' => $epNumber
                                        ],
                                        [
                                            'name'          => $ep['name'],
                                            'slug'          => "tap-{$epNumber}",
                                            'embed_url'     => $ep['link_embed'] ?? $ep['link_m3u8'] ?? '',
                                            'episode_number' => $epNumber
                                        ]
                                    );
                                    $episodeCount++;
                                }
                            }

                            DB::commit();
                            Log::info("Đã lưu phim {$movie->name}, {$episodeCount} tập");
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::error("Lỗi khi lưu phim {$slug}: " . $e->getMessage());
                        }
                    } catch (RequestException $e) {
                        Log::error("Lỗi HTTP khi lấy chi tiết phim {$slug}: " . $e->getMessage());
                    }

                    sleep(1); // delay tránh quá tải API
                }
            } catch (RequestException $e) {
                Log::error("Lỗi HTTP khi crawl thể loại {$this->categorySlug} trang {$page}: " . $e->getMessage());
            }

            // Delay giữa các trang
            if ($page < $this->pages) {
                sleep(2);
            }
        }

        Log::info("Hoàn thành crawl thể loại {$this->categorySlug}");
    }

    /**
     * Middleware để xử lý rate limit (tránh bị chặn)
     */
    public function middleware()
    {
        return [
            new ThrottlesExceptions(10, 1) // Cho phép 10 lần thử lại mỗi phút
        ];
    }
}
