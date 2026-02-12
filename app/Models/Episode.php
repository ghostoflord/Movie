<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'episode_number',
        'title',
        'slug',
        'video_url',
        'duration',
        'views',
        'is_free',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
        ];
    }

    // ===== Relationships =====

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function watchHistories()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

