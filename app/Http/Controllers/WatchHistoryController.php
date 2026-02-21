<?php

namespace App\Http\Controllers;

use App\Models\WatchHistory;
use Illuminate\Http\Request;

class WatchHistoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'episode_id' => 'required|exists:episodes,id',
            'current_time' => 'required|string',
            'duration_watched' => 'nullable|string',
        ]);

        $history = WatchHistory::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'episode_id' => $data['episode_id'],
            ],
            [
                'current_time' => $data['current_time'],
                'duration_watched' => $data['duration_watched'] ?? null,
                'last_watched_at' => now(),
            ]
        );

        return response()->json($history);
    }
}
