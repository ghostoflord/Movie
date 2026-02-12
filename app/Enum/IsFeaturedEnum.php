<?php

namespace App\Enum;

enum IsFeaturedEnum: string
{
    case HOT = 'HOT';
    case NONE = 'NONE';
    case BESTSELLER = 'BESTSELLER';
}
