<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\Movie;
use App\Services\OphimMovieActorSync;
use App\Services\OphimMovieTaxonomySync;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrawlCountryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;

    public $tries = 3;

    public function __construct(
        protected string $countrySlug,
        protected int $pages = 3
    ) {}

    public function handle(): void
    {
        Log::info("Bắt đầu crawl quốc gia [{$this->countrySlug}] - {$this->pages} trang");

        $client = new Client([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            ],
        ]);

        for ($page = 1; $page <= $this->pages; $page++) {
            Log::info("Crawl quốc gia {$this->countrySlug} - trang {$page}");

            try {
                $response = $client->get("https://ophim1.com/v1/api/quoc-gia/{$this->countrySlug}", [
                    'query' => ['page' => $page],
                ]);

                if ($response->getStatusCode() !== 200) {
                    Log::error("Lỗi kết nối quốc gia {$this->countrySlug} trang {$page}");

                    continue;
                }

                $data = json_decode($response->getBody()->getContents(), true);

                if (empty($data['data']['items'])) {
                    Log::warning("Không có phim ở quốc gia {$this->countrySlug} trang {$page}");

                    continue;
                }

                foreach ($data['data']['items'] as $item) {
                    $slug = $item['slug'] ?? null;
                    if (! $slug) {
                        continue;
                    }

                    Log::info('Xử lý phim: '.($item['name'] ?? 'Không tên'));

                    try {
                        $detailResponse = $client->get("https://ophim1.com/phim/{$slug}");

                        if ($detailResponse->getStatusCode() !== 200) {
                            Log::error("Lỗi lấy chi tiết phim {$slug}");

                            continue;
                        }

                        $detail = json_decode($detailResponse->getBody()->getContents(), true);

                        if (! isset($detail['movie'])) {
                            Log::error("Dữ liệu phim {$slug} không hợp lệ");

                            continue;
                        }

                        $movieData = $detail['movie'];
                        $episodes = $detail['episodes'] ?? [];

                        $categories = [];
                        if (isset($movieData['category']) && is_array($movieData['category'])) {
                            foreach ($movieData['category'] as $cat) {
                                if (isset($cat['name'])) {
                                    $categories[] = $cat['name'];
                                }
                            }
                        }

                        $countries = [];
                        if (isset($movieData['country']) && is_array($movieData['country'])) {
                            foreach ($movieData['country'] as $c) {
                                if (! empty($c['name'])) {
                                    $countries[] = $c['name'];
                                }
                            }
                        }

                        $actors = is_array($movieData['actor'] ?? null) ? $movieData['actor'] : [];
                        $directors = is_array($movieData['director'] ?? null) ? $movieData['director'] : [];

                        $language = $movieData['lang'] ?? $movieData['language'] ?? null;
                        if ($language === null && $countries !== []) {
                            $language = implode(', ', $countries);
                        }

                        DB::beginTransaction();

                        try {
                            $movie = Movie::updateOrCreate(
                                ['slug' => $movieData['slug']],
                                [
                                    'name' => $movieData['name'] ?? null,
                                    'origin_name' => $movieData['origin_name'] ?? null,
                                    'thumb_url' => $movieData['thumb_url'] ?? null,
                                    'poster_url' => $movieData['poster_url'] ?? null,
                                    'description' => $movieData['content'] ?? null,
                                    'year' => $movieData['year'] ?? null,
                                    'quality' => $movieData['quality'] ?? null,
                                    'language' => $language,
                                    'categories' => $categories,
                                    'countries' => $countries,
                                    'actors' => $actors,
                                    'directors' => $directors,
                                    'status' => $movieData['status'] ?? 'ongoing',
                                    'episode_current' => $movieData['episode_current'] ?? null,
                                    'episode_total' => $movieData['episode_total'] ?? null,
                                ]
                            );

                            $episodeCount = 0;

                            foreach ($episodes as $episodeGroup) {
                                if (! isset($episodeGroup['server_data'])) {
                                    continue;
                                }

                                foreach ($episodeGroup['server_data'] as $ep) {
                                    $epName = $ep['name'] ?? '';
                                    $epNumber = 1;
                                    if (is_numeric($epName)) {
                                        $epNumber = (int) $epName;
                                    } else {
                                        preg_match('/(\d+)/', (string) $epName, $matches);
                                        $epNumber = isset($matches[1]) ? (int) $matches[1] : 1;
                                    }

                                    Episode::updateOrCreate(
                                        [
                                            'movie_id' => $movie->id,
                                            'episode_number' => $epNumber,
                                        ],
                                        [
                                            'name' => $epName,
                                            'slug' => "tap-{$epNumber}",
                                            'embed_url' => $ep['link_embed'] ?? $ep['link_m3u8'] ?? '',
                                            'episode_number' => $epNumber,
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
                            Log::error("Lỗi khi lưu phim {$slug}: ".$e->getMessage());
                        }
                    } catch (RequestException $e) {
                        Log::error("Lỗi HTTP khi lấy chi tiết phim {$slug}: ".$e->getMessage());
                    }

                    sleep(1);
                }
            } catch (RequestException $e) {
                Log::error("Lỗi HTTP khi crawl quốc gia {$this->countrySlug} trang {$page}: ".$e->getMessage());
            }

            if ($page < $this->pages) {
                sleep(2);
            }
        }

        Log::info("Hoàn thành crawl quốc gia {$this->countrySlug}");
    }

    public function middleware(): array
    {
        return [
            new ThrottlesExceptions(10, 1),
        ];
    }
}
