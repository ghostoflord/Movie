<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'origin_name', 'slug', 'thumb_url', 'poster_url',
        'description', 'year', 'quality', 'language', 'categories',
        'actors', 'directors', 'status', 'episode_current', 'episode_total'
    ];

    protected $casts = [
        'categories' => 'array',
        'actors' => 'array',
        'directors' => 'array',
    ];

    // ===== Relationships =====

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }
}
