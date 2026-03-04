<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Movie;
use App\Models\Episode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class CrawlMoviesCommand extends Command
{
    protected $signature = 'crawl:movies {pages=3}';
    protected $description = 'Crawl movies from OPhim';

    public function handle()
    {
        $pages = $this->argument('pages');
        $this->info("Bắt đầu crawl {$pages} trang...");

        for ($page = 1; $page <= $pages; $page++) {
            $this->info("Trang {$page}/{$pages}...");

            $response = Http::get("https://ophim1.com/danh-sach/phim-moi-cap-nhat", ['page' => $page]);

            if (!$response->successful()) {
                $this->error("Lỗi kết nối trang {$page}");
                continue;
            }

            $data = $response->json();
            if (!isset($data['items'])) {
                $this->warn("Không có dữ liệu");
                continue;
            }

            foreach ($data['items'] as $item) {
                $slug = $item['slug'] ?? null;
                if (!$slug) continue;

                $this->line("Đang xử lý: {$item['name']}...");

                $detailResponse = Http::get("https://ophim1.com/phim/{$slug}");

                if (!$detailResponse->successful()) {
                    $this->error("Lỗi");
                    continue;
                }

                $detail = $detailResponse->json();
                if (!isset($detail['movie'])) {
                    $this->error("Dữ liệu lỗi");
                    continue;
                }

                $movieData = $detail['movie'];
                $episodes = $detail['episodes'] ?? [];

                DB::beginTransaction();
                try {
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
                    foreach ($episodes as $episodeGroup) {
                        if (!isset($episodeGroup['server_data'])) continue;
                        foreach ($episodeGroup['server_data'] as $ep) {
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
                    $this->info(" => {$episodeCount} tập");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Lỗi: " . $e->getMessage());
                }
                sleep(1);
            }
            if ($page < $pages) {
                $this->line("Chờ 2 giây...");
                sleep(2);
            }
        }

        $totalMovies = Movie::count();
        $totalEpisodes = Episode::count();
        $this->info("Hoàn thành! Tổng số phim: {$totalMovies}, tập: {$totalEpisodes}");
        return 0;
    }
}
