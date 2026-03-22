<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Episode;
use App\Models\Favorite;
use App\Models\Movie;
use App\Models\Notification;
use App\Models\Server;
use App\Models\User;
use App\Models\WatchHistory;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Tổng hợp số liệu cho dashboard (user, phim, tập, …).
     */
    public function stats()
    {
        return response()->json([
            'data' => [
                'users' => User::query()->count(),
                'users_active' => User::query()->where('active', true)->count(),
                'movies' => Movie::query()->count(),
                'episodes' => Episode::query()->count(),
                'comments' => Comment::query()->count(),
                'watch_history' => WatchHistory::query()->count(),
                'favorites' => Favorite::query()->count(),
                'notifications' => Notification::query()->count(),
                'actors' => DB::table('actors')->count(),
                'categories' => DB::table('categories')->count(),
                'ratings' => DB::table('ratings')->count(),
                'servers' => Server::query()->count(),
            ],
        ]);
    }
}
