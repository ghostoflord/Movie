<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WatchHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $duration = (int) $this->duration_watched;
        $current = (int) $this->current_time;
        $episode = $this->relationLoaded('episode') ? $this->episode : null;
        $movie = $episode && $episode->relationLoaded('movie') ? $episode->movie : null;

        return [
            'id' => $this->id,
            'current_time' => $current,
            'resume_at' => $current,
            'duration_watched' => $duration,
            'progress_percent' => $duration > 0
                ? min(100, round(($current / $duration) * 100, 1))
                : null,
            'last_watched_at' => $this->last_watched_at,
            'episode_label' => $episode
                ? ($episode->episode_number
                    ? 'Tập '.$episode->episode_number
                    : ($episode->name ?: 'Tập'))
                : null,
            'watch_url' => ($movie && $episode) ? $this->buildWatchUrl($movie, $episode, $current) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'episode' => $this->whenLoaded('episode', function () {
                return [
                    'id' => $this->episode->id,
                    'name' => $this->episode->name,
                    'slug' => $this->episode->slug,
                    'episode_number' => $this->episode->episode_number,
                    'movie_id' => $this->episode->movie_id,
                ];
            }),
            'movie' => $this->when($movie, function () use ($movie) {
                return [
                    'id' => $movie->id,
                    'name' => $movie->name,
                    'slug' => $movie->slug,
                    'thumb_url' => $movie->thumb_url,
                    'poster_url' => $movie->poster_url,
                    'episode_current' => $movie->episode_current,
                    'episode_total' => $movie->episode_total,
                ];
            }),
        ];
    }

    private function buildWatchUrl($movie, $episode, int $currentTime): string
    {
        $path = str_replace(
            ['{movie_slug}', '{episode_slug}', '{movie_id}', '{episode_id}'],
            [$movie->slug, $episode->slug, (string) $movie->id, (string) $episode->id],
            config('frontend.watch_path', '/watch/{movie_slug}/{episode_slug}')
        );

        $url = config('frontend.url', '').$path;

        if ($currentTime > 0) {
            $url .= (str_contains($url, '?') ? '&' : '?').'t='.$currentTime;
        }

        return $url;
    }
}
