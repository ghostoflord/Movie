<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\OphimMovieActorSync;
use Illuminate\Console\Command;

class BackfillActorsFromMovies extends Command
{
    protected $signature = 'backfill:actors {--chunk=200 : Chunk size} {--only-missing : Only movies without pivot actors}';

    protected $description = 'Backfill actors table + actor_movie pivot from movies.actors JSON';

    public function handle(): int
    {
        $chunk = max(50, (int) $this->option('chunk'));
        $onlyMissing = (bool) $this->option('only-missing');

        $query = Movie::query()->whereNotNull('actors');

        if ($onlyMissing) {
            $query->whereDoesntHave('movieActors');
        }

        $total = (clone $query)->count();
        $this->info("Movies to process: {$total}");

        $done = 0;
        $query->orderBy('id')->chunkById($chunk, function ($movies) use (&$done) {
            foreach ($movies as $movie) {
                $actors = $movie->actors;
                if (! is_array($actors) || $actors === []) {
                    continue;
                }

                OphimMovieActorSync::sync($movie, $actors);
                $done++;
            }
        });

        $this->info("Processed movies: {$done}");

        return self::SUCCESS;
    }
}

