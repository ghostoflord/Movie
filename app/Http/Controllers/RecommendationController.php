<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Models\Movie;
use App\Models\WatchHistory;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /**
     * Gợi ý phim theo thể loại dựa trên lịch sử xem và phim yêu thích của user đăng nhập.
     */
    public function forYou(Request $request)
    {
        $perPage = (int) $request->query('per_page', 12);
        $perPage = max(1, min($perPage, 50));

        $user = $request->user();

        $watchedMovieIds = WatchHistory::query()
            ->where('user_id', $user->id)
            ->join('episodes', 'episodes.id', '=', 'watch_history.episode_id')
            ->distinct()
            ->pluck('episodes.movie_id')
            ->filter();

        $categoryWeights = [];

        if ($watchedMovieIds->isNotEmpty()) {
            $watchedMovies = Movie::query()
                ->whereIn('id', $watchedMovieIds)
                ->get(['id', 'categories']);

            foreach ($watchedMovies as $movie) {
                foreach ($movie->categories ?? [] as $name) {
                    $categoryWeights[$name] = ($categoryWeights[$name] ?? 0) + 1;
                }
            }
        }

        foreach ($user->favoriteMovies()->get(['categories']) as $movie) {
            foreach ($movie->categories ?? [] as $name) {
                $categoryWeights[$name] = ($categoryWeights[$name] ?? 0) + 2;
            }
        }

        if ($categoryWeights === []) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'reason' => 'no_signals',
                    'message' => 'Chưa có lịch sử xem hoặc yêu thích để suy ra thể loại.',
                ],
            ]);
        }

        arsort($categoryWeights);
        $preferredNames = array_keys($categoryWeights);

        $excludeIds = $watchedMovieIds
            ->merge($user->favoriteMovies()->pluck('movies.id'))
            ->unique()
            ->values()
            ->all();

        $candidates = Movie::query()
            ->whereNotIn('id', $excludeIds)
            ->where(function ($q) use ($preferredNames) {
                foreach ($preferredNames as $name) {
                    $q->orWhereJsonContains('categories', $name);
                }
            })
            ->with('episodes')
            ->limit(300)
            ->get();

        $scored = $candidates->map(function (Movie $movie) use ($categoryWeights) {
            $cats = $movie->categories ?? [];
            $score = 0;
            foreach ($cats as $c) {
                $score += $categoryWeights[$c] ?? 0;
            }

            return ['movie' => $movie, 'score' => $score];
        })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $total = $scored->count();
        $slice = $scored->forPage($page, $perPage);
        $movies = $slice->pluck('movie');

        return response()->json([
            'data' => MovieResource::collection($movies),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'top_categories' => array_slice($categoryWeights, 0, 10, true),
            ],
        ]);
    }
}
