<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'ophim_id',
        'description',
        'icon',
        'title',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'category_movie')->withTimestamps();
    }
}

