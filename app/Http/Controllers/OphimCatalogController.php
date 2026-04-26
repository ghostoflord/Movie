<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxy/cache dữ liệu catalog từ OPhim (thể loại, quốc gia, danh sách phim theo slug).
 * Dùng cho FE (modal filter) — format JSON gần giống https://ophim1.com/v1/api/...
 */
class OphimCatalogController extends Controller
{
    private const BASE = 'https://ophim1.com/v1/api';

    /** Danh sách thể loại — cache 1h */
    public function theLoai(Request $request): JsonResponse
    {
        return $this->cachedGet('ophim:the-loai', self::BASE.'/the-loai', $request, 3600);
    }

    /** Danh sách quốc gia — cache 1h */
    public function quocGia(Request $request): JsonResponse
    {
        return $this->cachedGet('ophim:quoc-gia', self::BASE.'/quoc-gia', $request, 3600);
    }

    /**
     * Phim theo thể loại (slug OPhim), ví dụ hanh-dong.
     * Query: ?page=1
     */
    public function theLoaiBySlug(Request $request, string $slug): JsonResponse
    {
        return $this->liveGet(self::BASE.'/the-loai/'.$slug, $request);
    }

    /**
     * Phim theo quốc gia (slug OPhim), ví dụ han-quoc.
     * Query: ?page=1
     */
    public function quocGiaBySlug(Request $request, string $slug): JsonResponse
    {
        return $this->liveGet(self::BASE.'/quoc-gia/'.$slug, $request);
    }

    private function cachedGet(string $cacheKey, string $url, Request $request, int $seconds): JsonResponse
    {
        $query = $request->query();
        if ($query !== []) {
            $cacheKey .= ':'.md5(json_encode($query));
        }

        try {
            $body = Cache::remember($cacheKey, $seconds, function () use ($url, $query) {
                $payload = $this->fetchOphim($url, $query);
                if ($payload['status'] !== 200) {
                    throw new \RuntimeException('OPhim trả HTTP '.$payload['status']);
                }

                return $payload['body'];
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json($body, 200);
    }

    private function liveGet(string $url, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));
        $payload = $this->fetchOphim($url, ['page' => $page]);

        return response()->json($payload['body'], $payload['status']);
    }

    /**
     * @return array{status: int, body: mixed}
     */
    private function fetchOphim(string $url, array $query): array
    {
        $response = Http::timeout(45)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; MovieAPI/1.0)',
                'Accept' => 'application/json',
            ])
            ->get($url, $query);

        $json = $response->json();

        return [
            'status' => $response->status(),
            'body' => $json ?? ['message' => 'Invalid JSON from OPhim', 'raw' => $response->body()],
        ];
    }
}
