<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Actor extends Model
{
    protected $fillable = [
        'ophim_id',
        'name',
        'slug',
        'bio',
        'avatar',
        'birth_date',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'actor_movie')->withTimestamps();
    }
}

