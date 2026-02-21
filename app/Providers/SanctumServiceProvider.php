<?php

namespace App\Providers;

use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\SanctumServiceProvider as BaseProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Str;

class SanctumServiceProvider extends BaseProvider
{
    public function boot()
    {
        PersonalAccessToken::creating(function ($token) {
            $token->token = hash('sha256', Str::random(40));
        });
    }
}