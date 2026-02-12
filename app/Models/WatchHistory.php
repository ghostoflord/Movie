<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchHistory extends Model
{
    protected $fillable = [
        'user_id',
        'episode_id',
        'current_time',
        'duration_watched',
        'last_watched_at',
    ];

    protected function casts(): array
    {
        return [
            'last_watched_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
