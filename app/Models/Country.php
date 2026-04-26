<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Country extends Model
{
    protected $fillable = [
        'ophim_id',
        'name',
        'slug',
    ];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'country_movie')->withTimestamps();
    }
}
