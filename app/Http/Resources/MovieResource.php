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
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'year' => $this->year,
            'episode_current' => $this->episode_current,
            'episode_total' => $this->episode_total,
            'views' => $this->views,
            // Chỉ lấy các field cần từ episodes
            'episodes' => EpisodeResource::collection($this->whenLoaded('episodes')),
        ];
    }
}
