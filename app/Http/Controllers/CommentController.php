<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'movie_id' => 'required_without:episode_id|exists:movies,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $query = Comment::query()
            ->with('user')
            ->orderByDesc('created_at');

        if ($request->filled('movie_id')) {
            $query->where('movie_id', $request->query('movie_id'));
        }

        if ($request->filled('episode_id')) {
            $query->where('episode_id', $request->query('episode_id'));
        }

        $comments = $query->paginate($perPage);

        return response()->json([
            'data' => CommentResource::collection($comments->items()),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'from' => $comments->firstItem(),
                'to' => $comments->lastItem(),
            ],
        ]);
    }

    public function indexByMovie(Request $request, $movieId)
    {
        $request->merge(['movie_id' => $movieId]);

        return $this->index($request);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'movie_id' => 'nullable|exists:movies,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'content' => 'required|string',
        ]);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'movie_id' => $data['movie_id'] ?? null,
            'episode_id' => $data['episode_id'] ?? null,
            'content' => $data['content'],
            'likes_count' => 0,
        ]);

        $comment->load('user');

        return response()->json(['data' => new CommentResource($comment)], 201);
    }
}
