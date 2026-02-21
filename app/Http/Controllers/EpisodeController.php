<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'episode_number' => 'required|integer',
            'title' => 'required|string',
            'video_url' => 'required|string',
        ]);

        $episode = Episode::create($data);

        return response()->json($episode, 201);
    }
}
