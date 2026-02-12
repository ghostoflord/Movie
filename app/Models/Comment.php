<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'movie_id',
        'episode_id',
        'content',
        'likes_count',
    ];

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
