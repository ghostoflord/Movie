<?php

namespace App;

enum IsFeaturedEnum: string
{
    case HOT = 'HOT';
    case NONE = 'NONE';
    case BESTSELLER = 'BESTSELLER';
}
