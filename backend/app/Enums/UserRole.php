<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case MOD = 'mod';
    case ADMIN = 'admin';
}
