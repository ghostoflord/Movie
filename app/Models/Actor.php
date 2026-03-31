<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Actor extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'bio',
        'avatar',
        'birth_date',
    ];
}

