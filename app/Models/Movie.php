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
        'countries',
        'actors', 'directors', 'status', 'episode_current', 'episode_total'
    ];

    protected $casts = [
        'categories' => 'array',
        'countries' => 'array',
        'actors' => 'array',
        'directors' => 'array',
    ];

    // ===== Relationships =====

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }

    /** Pivot thể loại (tránh trùng tên với cột JSON `categories`). */
    public function movieCategories()
    {
        return $this->belongsToMany(Category::class, 'category_movie')->withTimestamps();
    }

    /** Pivot quốc gia (tránh trùng tên với cột JSON `countries`). */
    public function movieCountries()
    {
        return $this->belongsToMany(Country::class, 'country_movie')->withTimestamps();
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
