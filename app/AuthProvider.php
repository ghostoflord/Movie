<?php


namespace App;

enum AuthProvider: string
{
    case LOCAL = 'LOCAL';
    case GOOGLE = 'GOOGLE';
    case GITHUB = 'GITHUB';
    case FACEBOOK = 'FACEBOOK';
}
