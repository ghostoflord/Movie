<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ActorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'birth_date' => $this->birth_date,

            // pivot info
            'movies_count' => $this->whenCounted('movies'),
            'movie_ids' => $this->whenLoaded('movies', function () {
                return $this->movies->pluck('id')->values()->all();
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

