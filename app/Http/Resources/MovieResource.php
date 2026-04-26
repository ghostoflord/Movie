<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            // Basic info
            'id' => $this->id,
            'name' => $this->name,
            'origin_name' => $this->origin_name,
            'slug' => $this->slug,

            // Images
            'thumb_url' => $this->thumb_url,
            'poster_url' => $this->poster_url,

            // Description & details
            'description' => $this->description,
            'year' => $this->year,

            // Quality & language
            'quality' => $this->quality,
            'language' => $this->language,

            // Thể loại / quốc gia: ưu tiên pivot (bảng); fallback cột JSON (dữ liệu cũ)
            'categories' => ($this->relationLoaded('movieCategories') && $this->movieCategories->isNotEmpty())
                ? $this->movieCategories->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'ophim_id' => $c->ophim_id,
                ])->values()->all()
                : $this->categories,
            'countries' => ($this->relationLoaded('movieCountries') && $this->movieCountries->isNotEmpty())
                ? $this->movieCountries->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'ophim_id' => $c->ophim_id,
                ])->values()->all()
                : $this->countries,
            'actors' => ($this->relationLoaded('movieActors') && $this->movieActors->isNotEmpty())
                ? $this->movieActors->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'slug' => $a->slug,
                    'ophim_id' => $a->ophim_id,
                    'avatar' => $a->avatar,
                    'birth_date' => $a->birth_date,
                ])->values()->all()
                : $this->actors,
            'directors' => $this->directors,

            // Status & episodes
            'status' => $this->status,
            'episode_current' => $this->episode_current,
            'episode_total' => $this->episode_total,

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'episodes' => EpisodeResource::collection($this->whenLoaded('episodes')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'favorited_by_count' => $this->whenCounted('favoritedByUsers'),
        ];
    }
}
