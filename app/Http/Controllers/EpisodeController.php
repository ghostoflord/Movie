<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{
    /**
     * Display a listing of episodes with pagination.
     */
    public function index(Request $request)
    {
        // Validate query parameters
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'movie_id' => 'nullable|exists:movies,id',
            'sort_by' => 'nullable|in:episode_number,created_at',
            'sort_order' => 'nullable|in:asc,desc'
        ]);

        // Start query
        $query = Episode::query();

        // Filter by movie if provided
        if ($request->has('movie_id')) {
            $query->where('movie_id', $request->movie_id);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'episode_number');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Get pagination limit
        $perPage = $request->get('per_page', 15);

        // Return paginated results
        $episodes = $query->paginate($perPage);

        return response()->json([
            'data' => $episodes->items(),
            'pagination' => [
                'current_page' => $episodes->currentPage(),
                'per_page' => $episodes->perPage(),
                'total' => $episodes->total(),
                'last_page' => $episodes->lastPage(),
                'next_page_url' => $episodes->nextPageUrl(),
                'prev_page_url' => $episodes->previousPageUrl(),
            ]
        ], 200);
    }

    /**
     * Store a newly created episode.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'name' => 'required|string', // Đổi từ title thành name cho khớp với migration
            'slug' => 'required|string|unique:episodes,slug', // Thêm slug với unique
            'embed_url' => 'required|string', // Đổi từ video_url thành embed_url
            'episode_number' => 'required|integer',
        ]);

        // Tự động tạo slug nếu không được cung cấp? 
        // Bạn có thể dùng: $data['slug'] = Str::slug($data['name']);
        // Nhớ thêm use Illuminate\Support\Str; ở đầu file

        $episode = Episode::create($data);

        return response()->json($episode, 201);
    }

    /**
     * Display the specified episode.
     */
    public function show($id)
    {
        $episode = Episode::with('movie')->find($id);

        if (!$episode) {
            return response()->json(['message' => 'Episode not found'], 404);
        }

        return response()->json($episode, 200);
    }

    /**
     * Update the specified episode.
     */
    public function update(Request $request, $id)
    {
        $episode = Episode::find($id);

        if (!$episode) {
            return response()->json(['message' => 'Episode not found'], 404);
        }

        $data = $request->validate([
            'movie_id' => 'sometimes|exists:movies,id',
            'name' => 'sometimes|string',
            'slug' => 'sometimes|string|unique:episodes,slug,' . $id,
            'embed_url' => 'sometimes|string',
            'episode_number' => 'sometimes|integer',
        ]);

        $episode->update($data);

        return response()->json($episode, 200);
    }

    /**
     * Remove the specified episode.
     */
    public function destroy($id)
    {
        $episode = Episode::find($id);

        if (!$episode) {
            return response()->json(['message' => 'Episode not found'], 404);
        }

        $episode->delete();

        return response()->json(['message' => 'Episode deleted successfully'], 200);
    }

    /**
     * Get episodes by movie with pagination.
     */
    public function getByMovie($movieId, Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $perPage = $request->get('per_page', 15);

        $episodes = Episode::where('movie_id', $movieId)
            ->orderBy('episode_number', 'asc')
            ->paginate($perPage);

        return response()->json([
            'data' => $episodes->items(),
            'pagination' => [
                'current_page' => $episodes->currentPage(),
                'per_page' => $episodes->perPage(),
                'total' => $episodes->total(),
                'last_page' => $episodes->lastPage(),
            ]
        ], 200);
    }
}
