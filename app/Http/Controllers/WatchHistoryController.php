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
            'current_time' => 'required|numeric',
            'duration_watched' => 'nullable|numeric',
        ]);

        $values = [
            'current_time' => (int) $data['current_time'],
            'last_watched_at' => now(),
        ];
        if (array_key_exists('duration_watched', $data) && $data['duration_watched'] !== null && $data['duration_watched'] !== '') {
            $values['duration_watched'] = (int) $data['duration_watched'];
        }

        $history = WatchHistory::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'episode_id' => $data['episode_id'],
            ],
            $values
        );

        return response()->json($history);
    }
}
