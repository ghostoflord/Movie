<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Episode;
use App\Models\Favorite;
use App\Models\Movie;
use App\Models\Notification;
use App\Models\Server;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Tổng hợp số liệu cho dashboard (user, phim, tập, …).
     */
    public function stats()
    {
        return response()->json([
            'data' => [
                'users' => User::query()->count(),
                'users_active' => User::query()->where('active', true)->count(),
                'movies' => Movie::query()->count(),
                'episodes' => Episode::query()->count(),
                'comments' => Comment::query()->count(),
                'watch_history' => WatchHistory::query()->count(),
                'favorites' => Favorite::query()->count(),
                'notifications' => Notification::query()->count(),
                // Đếm từ JSON trên movies (không dùng bảng actors): gộp trùng tên, không phân biệt hoa/thường
                'actors' => $this->countUniqueNamesFromMoviesJson('actors'),
                'directors' => $this->countUniqueNamesFromMoviesJson('directors'),
                'categories' => $this->countUniqueNamesFromMoviesJson('categories'),
                'ratings' => DB::table('ratings')->count(),
                'servers' => Server::query()->count(),
            ],
        ]);
    }

    /**
     * Đếm số giá trị khác nhau trong cột JSON (actors/directors/categories) của bảng movies.
     * Chuẩn hoá: trim, gộp khoảng trắng, so sánh không phân biệt hoa/thường (UTF-8).
     */
    private function countUniqueNamesFromMoviesJson(string $column): int
    {
        if (! in_array($column, ['actors', 'directors', 'categories'], true)) {
            return 0;
        }

        $seen = [];

        Movie::query()
            ->whereNotNull($column)
            ->select(['id', $column])
            ->orderBy('id')
            ->chunkById(200, function ($movies) use ($column, &$seen): void {
                foreach ($movies as $movie) {
                    $items = $movie->{$column};
                    if (! is_array($items)) {
                        continue;
                    }
                    foreach ($items as $item) {
                        if (! is_string($item)) {
                            continue;
                        }
                        $key = $this->normalizePersonNameKey($item);
                        if ($key === '') {
                            continue;
                        }
                        $seen[$key] = true;
                    }
                }
            });

        return count($seen);
    }

    private function normalizePersonNameKey(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return mb_strtolower($name, 'UTF-8');
    }
}
