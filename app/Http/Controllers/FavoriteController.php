<?php

namespace App\Http\Controllers;


class FavoriteController extends Controller
{
    // POST /api/favorites/{movieId}
    public function toggle($movieId)
    {
        $user = auth()->user();

        if ($user->favoriteMovies()->where('movie_id', $movieId)->exists()) {
            $user->favoriteMovies()->detach($movieId);
            return response()->json(['message' => 'Removed from favorites']);
        }

        $user->favoriteMovies()->attach($movieId);
        return response()->json(['message' => 'Added to favorites']);
    }
}
