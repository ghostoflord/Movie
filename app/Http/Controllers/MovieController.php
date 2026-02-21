<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    // GET /api/movies
    public function index()
    {
        $movies = Movie::with('episodes')->paginate(10);

        return response()->json($movies);
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
