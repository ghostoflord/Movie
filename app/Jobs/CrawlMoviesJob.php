<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\Movie;
use App\Services\OphimMovieActorSync;
use App\Services\OphimMovieTaxonomySync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CrawlMoviesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $pages;
    public $timeout = 1200;

    public function __construct($pages = 3)
    {
        $this->pages = $pages;
    }

    public function handle(): void
    {
        Log::info("Bắt đầu crawl {$this->pages} trang");

        $client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);

        for ($page = 1; $page <= $this->pages; $page++) {
            Log::info("Crawl trang {$page}...");

            try {
                $response = $client->get("https://ophim1.com/danh-sach/phim-moi-cap-nhat", [
                    'query' => ['page' => $page]
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    Log::error("Lỗi kết nối trang {$page}, status: " . $statusCode);
                    continue;
                }

                $data = json_decode($response->getBody()->getContents(), true);

                if (!isset($data['items'])) {
                    Log::warning("Trang {$page} không có items");
                    continue;
                }

                foreach ($data['items'] as $item) {
                    $slug = $item['slug'] ?? null;
                    if (!$slug) continue;

                    Log::info("Xử lý phim: " . ($item['name'] ?? 'Không tên'));

                    try {
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

                        // ========== XỬ LÝ CATEGORIES ==========
                        $categories = [];
                        if (isset($movieData['category']) && is_array($movieData['category'])) {
                            foreach ($movieData['category'] as $cat) {
                                if (isset($cat['name'])) {
                                    $categories[] = $cat['name'];
                                }
                            }
                        }

                        // ========== XỬ LÝ QUỐC GIA ==========
                        $countries = [];
                        if (isset($movieData['country']) && is_array($movieData['country'])) {
                            foreach ($movieData['country'] as $c) {
                                if (! empty($c['name'])) {
                                    $countries[] = $c['name'];
                                }
                            }
                        }

                        // ========== XỬ LÝ ACTORS ==========
                        $actors = [];
                        if (isset($movieData['actor']) && is_array($movieData['actor'])) {
                            $actors = $movieData['actor'];
                        }

                        // ========== XỬ LÝ DIRECTORS ==========
                        $directors = [];
                        if (isset($movieData['director']) && is_array($movieData['director'])) {
                            $directors = $movieData['director'];
                        }

                        // ========== XỬ LÝ LANGUAGE ==========
                        $language = null;
                        if (isset($movieData['lang'])) {
                            $language = $movieData['lang'];
                        } elseif (isset($movieData['language'])) {
                            $language = $movieData['language'];
                        } elseif ($countries !== []) {
                            // Fallback to country name if no language
                            $language = implode(', ', $countries);
                        }

                        DB::beginTransaction();

                        try {
                            $movie = Movie::updateOrCreate(
                                ['slug' => $movieData['slug']],
                                [
                                    'name'            => $movieData['name'] ?? null,
                                    'origin_name'     => $movieData['origin_name'] ?? null,
                                    'thumb_url'       => $movieData['thumb_url'] ?? null,
                                    'poster_url'      => $movieData['poster_url'] ?? null,
                                    'description'     => $movieData['content'] ?? null,
                                    'year'            => $movieData['year'] ?? null,
                                    'quality'         => $movieData['quality'] ?? null,
                                    'language'        => $language,
                                    'categories'      => $categories,
                                    'countries'       => $countries,
                                    'actors'          => $actors,          // Đã xử lý
                                    'directors'       => $directors,       // Đã xử lý
                                    'status'          => $movieData['status'] ?? 'ongoing',
                                    'episode_current' => $movieData['episode_current'] ?? null,
                                    'episode_total'   => $movieData['episode_total'] ?? null,
                                ]
                            );

                            $episodeCount = 0;

                            // Xử lý episodes
                            foreach ($episodes as $episodeGroup) {
                                if (!isset($episodeGroup['server_data'])) continue;

                                foreach ($episodeGroup['server_data'] as $ep) {
                                    // Xử lý tên tập
                                    $epName = $ep['name'] ?? '';
                                    $epNumber = 1;

                                    if (is_numeric($epName)) {
                                        $epNumber = (int)$epName;
                                    } else {
                                        preg_match('/(\d+)/', $epName, $matches);
                                        $epNumber = isset($matches[1]) ? (int)$matches[1] : 1;
                                    }

                                    Episode::updateOrCreate(
                                        [
                                            'movie_id'       => $movie->id,
                                            'episode_number' => $epNumber
                                        ],
                                        [
                                            'name'           => $epName,
                                            'slug'           => "tap-{$epNumber}",
                                            'embed_url'      => $ep['link_embed'] ?? $ep['link_m3u8'] ?? '',
                                            'episode_number' => $epNumber
                                        ]
                                    );
                                    $episodeCount++;
                                }
                            }

                            OphimMovieTaxonomySync::sync(
                                $movie,
                                is_array($movieData['category'] ?? null) ? $movieData['category'] : [],
                                is_array($movieData['country'] ?? null) ? $movieData['country'] : []
                            );

                            OphimMovieActorSync::sync(
                                $movie,
                                is_array($movieData['actor'] ?? null) ? $movieData['actor'] : []
                            );

                            DB::commit();
                            Log::info("Đã lưu phim {$movie->name}, {$episodeCount} tập");
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::error("Lỗi khi lưu phim {$slug}: " . $e->getMessage());
                        }
                    } catch (RequestException $e) {
                        Log::error("Lỗi HTTP khi lấy chi tiết phim {$slug}: " . $e->getMessage());
                    }

                    sleep(1);
                }
            } catch (RequestException $e) {
                Log::error("Lỗi HTTP khi crawl trang {$page}: " . $e->getMessage());
            }

            if ($page < $this->pages) {
                sleep(2);
            }
        }

        Log::info("Hoàn thành crawl {$this->pages} trang");
    }
}
