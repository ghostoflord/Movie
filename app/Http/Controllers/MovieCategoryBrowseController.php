<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieCategoryBrowseController extends Controller
{
    /**
     * Danh sách thể loại suy ra từ JSON `movies.categories` (gộp trùng không phân biệt hoa/thường).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 30);
        $perPage = max(1, min($perPage, 100));
        $page = max(1, (int) $request->query('page', 1));
        $sort = $request->query('sort', 'label'); // label | count

        $rows = $this->aggregateCategoriesFromMovies();

        $collection = collect($rows);
        if ($sort === 'count') {
            $collection = $collection->sortByDesc('movies_count')->values();
        } else {
            $collection = $collection->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();
        }

        $total = $collection->count();
        $slice = $collection->forPage($page, $perPage)->values();

        return response()->json([
            'data' => $slice,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Phim thuộc một thể loại (so khớp sau khi chuẩn hoá tên), kèm episodes.
     *
     * Query: ?category=Chính+kịch
     */
    public function movies(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:255',
        ]);

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));
        $page = max(1, (int) $request->query('page', 1));

        $needle = $this->normalizeLabelKey($request->query('category'));

        $filtered = Movie::query()
            ->whereNotNull('categories')
            ->with('episodes')
            ->orderBy('id')
            ->get()
            ->filter(function (Movie $movie) use ($needle) {
                foreach ($movie->categories ?? [] as $c) {
                    if (! is_string($c)) {
                        continue;
                    }
                    if ($this->normalizeLabelKey($c) === $needle) {
                        return true;
                    }
                }

                return false;
            })
            ->values();

        $total = $filtered->count();
        $pageItems = $filtered->forPage($page, $perPage)->values();

        return response()->json([
            'data' => MovieResource::collection($pageItems),
            'meta' => [
                'category_query' => $request->query('category'),
                'normalized' => $needle,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
        ]);
    }

    /**
     * @return list<array{label: string, normalized: string, movies_count: int}>
     */
    private function aggregateCategoriesFromMovies(): array
    {
        $byKey = [];

        Movie::query()
            ->whereNotNull('categories')
            ->select(['id', 'categories'])
            ->orderBy('id')
            ->chunkById(200, function ($movies) use (&$byKey): void {
                foreach ($movies as $movie) {
                    $items = $movie->categories;
                    if (! is_array($items)) {
                        continue;
                    }
                    foreach ($items as $item) {
                        if (! is_string($item)) {
                            continue;
                        }
                        $key = $this->normalizeLabelKey($item);
                        if ($key === '') {
                            continue;
                        }
                        if (! isset($byKey[$key])) {
                            $byKey[$key] = [
                                'label' => trim($item),
                                'normalized' => $key,
                                'movie_ids' => [],
                            ];
                        }
                        $byKey[$key]['movie_ids'][$movie->id] = true;
                    }
                }
            });

        $out = [];
        foreach ($byKey as $row) {
            $out[] = [
                'label' => $row['label'],
                'normalized' => $row['normalized'],
                'movies_count' => count($row['movie_ids']),
            ];
        }

        return $out;
    }

    private function normalizeLabelKey(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return mb_strtolower($name, 'UTF-8');
    }
}
