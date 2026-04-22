<?php

namespace App\Enums;

enum FlagTargetType: string
{
    case RECORD = 'record';
    case OBSERVATION = 'observation';
    case USER = 'user';
}
