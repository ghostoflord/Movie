<?php

namespace App\Http\Controllers;

use App\Models\Actor;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MovieActorController extends Controller
{
    /**
     * POST /api/movies/{movieId}/actors
     * - actor_ids: [1,2] (attach, không xoá cái cũ)
     * - actors: ["Tom Cruise", "Brad Pitt"] (tự tạo nếu chưa có, rồi attach)
     */
    public function attach(Request $request, string $movieId)
    {
        $movie = Movie::query()->findOrFail($movieId);

        $data = $request->validate([
            'actor_ids' => 'sometimes|array',
            'actor_ids.*' => 'integer|exists:actors,id',
            'actors' => 'sometimes|array',
            'actors.*' => 'string|max:255',
        ]);

        $attachIds = [];

        if (isset($data['actor_ids'])) {
            $attachIds = array_values(array_unique($data['actor_ids']));
        }

        if (isset($data['actors'])) {
            foreach ($data['actors'] as $nameRaw) {
                $name = trim((string) $nameRaw);
                if ($name === '') {
                    continue;
                }
                $slug = Str::slug($name) ?: ('actor-'.md5($name));
                $actor = Actor::query()->firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name]
                );
                $attachIds[] = $actor->id;
            }
        }

        $attachIds = array_values(array_unique($attachIds));
        if ($attachIds !== []) {
            $movie->movieActors()->syncWithoutDetaching($attachIds);
        }

        return response()->json([
            'message' => 'Actors attached',
            'data' => [
                'movie_id' => $movie->id,
                'actor_ids' => $movie->movieActors()->pluck('actors.id')->values(),
            ],
        ]);
    }

    /**
     * PUT /api/movies/{movieId}/actors
     * Sync đúng danh sách actor_ids (thay thế toàn bộ)
     */
    public function sync(Request $request, string $movieId)
    {
        $movie = Movie::query()->findOrFail($movieId);

        $data = $request->validate([
            'actor_ids' => 'required|array',
            'actor_ids.*' => 'integer|exists:actors,id',
        ]);

        $ids = array_values(array_unique($data['actor_ids']));
        $movie->movieActors()->sync($ids);

        return response()->json([
            'message' => 'Actors synced',
            'data' => [
                'movie_id' => $movie->id,
                'actor_ids' => $movie->movieActors()->pluck('actors.id')->values(),
            ],
        ]);
    }

    /**
     * DELETE /api/movies/{movieId}/actors/{actorId}
     * Detach 1 actor khỏi movie
     */
    public function detach(string $movieId, string $actorId)
    {
        $movie = Movie::query()->findOrFail($movieId);
        $actor = Actor::query()->findOrFail($actorId);

        $movie->movieActors()->detach($actor->id);

        return response()->json([
            'message' => 'Actor detached',
            'data' => [
                'movie_id' => $movie->id,
                'actor_id' => $actor->id,
            ],
        ]);
    }
}

