<?php

namespace App\Enum;

enum UserRoleEnum: string
{
    case USER = 'USER';
    case ADMIN = 'ADMIN';
    // legacy
    case VIP = 'VIP';

    // new tiers
    case VIP1 = 'VIP1';
    case VIP2 = 'VIP2';
    case SUPER_ADMIN = 'SUPER_ADMIN';
}
