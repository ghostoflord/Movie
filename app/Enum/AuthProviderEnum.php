<?php


namespace App\Enum;

enum AuthProviderEnum: string
{
    case LOCAL = 'LOCAL';
    case GOOGLE = 'GOOGLE';
    case GITHUB = 'GITHUB';
    case FACEBOOK = 'FACEBOOK';
}
