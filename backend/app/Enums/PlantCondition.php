<?php

namespace App\Enums;

enum PlantCondition: string
{
    case GOOD = 'good';
    case REGULAR = 'regular';
    case BAD = 'bad';
    case DRY = 'dry';
    case UNKNOWN = 'unknown';
}
