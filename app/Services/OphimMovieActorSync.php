<?php

namespace App\Services;

use App\Models\Actor;
use App\Models\Movie;
use Illuminate\Support\Str;

class OphimMovieActorSync
{
    /**
     * @param array<int, mixed> $actorNames
     */
    public static function sync(Movie $movie, array $actorNames): void
    {
        $ids = [];

        foreach ($actorNames as $nameRaw) {
            if (! is_string($nameRaw)) {
                continue;
            }

            $name = trim($nameRaw);
            if ($name === '') {
                continue;
            }

            $slug = Str::slug($name);
            if ($slug === '') {
                $slug = 'actor-'.md5($name);
            }

            $actor = Actor::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'bio' => null,
                    'avatar' => null,
                    'birth_date' => null,
                ]
            );

            // Nếu trùng slug nhưng name khác thì vẫn cập nhật name cho “đẹp”
            if ($actor->name !== $name) {
                $actor->name = $name;
                $actor->save();
            }

            $ids[] = $actor->id;
        }

        $movie->movieActors()->sync($ids);
    }
}

