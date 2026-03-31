<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $ratings = Rating::query()
            ->where('user_id', $request->user()->id)
            ->paginate($perPage);

        return response()->json([
            'data' => $ratings->items(),
            'meta' => [
                'current_page' => $ratings->currentPage(),
                'last_page' => $ratings->lastPage(),
                'per_page' => $ratings->perPage(),
                'total' => $ratings->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'rating' => 'required|integer|min:1|max:10',
        ]);

        $rating = Rating::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'movie_id' => $data['movie_id'],
            ],
            [
                'rating' => $data['rating'],
            ]
        );

        return response()->json(['data' => $rating], 201);
    }

    public function show(Request $request, $id)
    {
        $rating = Rating::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $rating]);
    }

    public function update(Request $request, $id)
    {
        $rating = Rating::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:10',
        ]);

        $rating->update(['rating' => $data['rating']]);

        return response()->json(['data' => $rating->fresh()]);
    }

    public function destroy(Request $request, $id)
    {
        Rating::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['message' => 'Deleted']);
    }
}

