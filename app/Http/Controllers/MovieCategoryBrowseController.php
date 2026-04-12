<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Models\Category;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MovieCategoryBrowseController extends Controller
{
    /**
     * Danh sách thể loại: ưu tiên bảng `categories` + pivot; nếu chưa có pivot thì gộp từ JSON `movies.categories`.
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
        $raw = trim($request->query('category'));

        if (Schema::hasTable('category_movie') && DB::table('category_movie')->exists()) {
            $paginator = Movie::query()
                ->whereHas('movieCategories', function ($q) use ($needle, $raw) {
                    $q->where(function ($sub) use ($raw, $needle) {
                        $sub->where('slug', $raw)
                            ->orWhereRaw('LOWER(TRIM(name)) = ?', [$needle]);
                    });
                })
                ->with(['episodes', 'movieCategories', 'movieCountries'])
                ->orderBy('id')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'data' => MovieResource::collection($paginator->items()),
                'meta' => [
                    'category_query' => $request->query('category'),
                    'normalized' => $needle,
                    'source' => 'pivot',
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $filtered = Movie::query()
            ->whereNotNull('categories')
            ->with(['episodes', 'movieCategories', 'movieCountries'])
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
                'source' => 'json',
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function aggregateCategoriesFromMovies(): array
    {
        if (Schema::hasTable('category_movie') && DB::table('category_movie')->exists()) {
            return Category::query()
                ->withCount('movies')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $c) => [
                    'label' => $c->name,
                    'normalized' => $this->normalizeLabelKey($c->name),
                    'slug' => $c->slug,
                    'movies_count' => $c->movies_count,
                ])
                ->all();
        }

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
