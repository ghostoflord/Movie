<?php

namespace App\Enum;

enum UserRoleEnum: string
{
    case USER = 'USER';
    case ADMIN = 'ADMIN';
    case VIP = 'VIP';
}
