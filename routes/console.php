<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Movie;
use App\Services\OphimMovieActorSync;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('backfill:actors {--chunk=200} {--only-missing}', function () {
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
})->purpose('Backfill actors + actor_movie from movies.actors JSON');

Schedule::command('vip:expire')->hourly();
