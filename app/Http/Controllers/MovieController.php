<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\Request;

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
        $data = $request->validate([
            'title' => 'required|string',
            'slug' => 'required|string|unique:movies,slug',
            'description' => 'nullable|string',
        ]);

        $movie = Movie::create($data);

        return response()->json($movie, 201);
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
}
