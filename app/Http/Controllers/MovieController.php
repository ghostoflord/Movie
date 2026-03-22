<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MovieController extends Controller
{
    // GET /api/movies
    public function index(Request $request)
    {
        // Lấy page và per_page từ request, có giá trị mặc định
        $perPage = $request->query('per_page', 10); // Mặc định 10
        $page = $request->query('page', 1); // Mặc định trang 1

        // Giới hạn perPage trong khoảng hợp lý (tránh quá tải)
        $perPage = min($perPage, 100);
        $perPage = max($perPage, 1); // Đảm bảo ít nhất 1

        $movies = Movie::with('episodes')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => MovieResource::collection($movies->items()),
            'meta' => [
                'current_page' => $movies->currentPage(),
                'last_page' => $movies->lastPage(),
                'per_page' => $movies->perPage(),
                'total' => $movies->total(),
                'from' => $movies->firstItem(),
                'to' => $movies->lastItem(),
            ],
        ]);
    }
    // POST /api/movies
    public function store(Request $request)
    {
        $data = $request->validate($this->movieRules());

        $movie = Movie::create($data);

        return response()->json([
            'data' => new MovieResource($movie->load('episodes')),
        ], 201);
    }

    // PUT/PATCH /api/movies/{id}
    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);

        $data = $request->validate($this->movieRules(forUpdate: true, movieId: $movie->id));

        $movie->update($data);

        return response()->json([
            'data' => new MovieResource($movie->fresh()->load('episodes')),
        ]);
    }

    // GET /api/movies/{id}
    public function show($id)
    {
        $movie = Movie::with(['episodes', 'comments'])->findOrFail($id);

        return response()->json($movie);
    }

    // DELETE
    public function destroy($id)
    {
        Movie::findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * @return array<string, mixed>
     */
    private function movieRules(bool $forUpdate = false, ?int $movieId = null): array
    {
        $slugRule = Rule::unique('movies', 'slug');
        if ($forUpdate && $movieId !== null) {
            $slugRule = $slugRule->ignore($movieId);
        }

        $required = $forUpdate ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'max:255'],
            'origin_name' => 'nullable|string|max:255',
            'slug' => [$required, 'string', 'max:255', $slugRule],
            'thumb_url' => 'nullable|string|max:2048',
            'poster_url' => 'nullable|string|max:2048',
            'description' => 'nullable|string',
            'year' => 'nullable|integer|min:1800|max:2100',
            'quality' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:50',
            'categories' => 'nullable|array',
            'categories.*' => 'string|max:255',
            'actors' => 'nullable|array',
            'actors.*' => 'string|max:255',
            'directors' => 'nullable|array',
            'directors.*' => 'string|max:255',
            'status' => 'nullable|string|max:50',
            'episode_current' => 'nullable|string|max:100',
            'episode_total' => 'nullable|string|max:100',
        ];
    }
}
