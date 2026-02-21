<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
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

        return response()->json($comment, 201);
    }
}
