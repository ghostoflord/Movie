<?php

namespace App;

enum UserRole: string
{
    case USER = 'USER';
    case ADMIN = 'ADMIN';
    case VIP = 'VIP';
}
