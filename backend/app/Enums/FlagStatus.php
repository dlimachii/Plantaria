<?php

namespace App\Enums;

enum FlagStatus: string
{
    case OPEN = 'open';
    case REVIEWING = 'reviewing';
    case RESOLVED = 'resolved';
    case REJECTED = 'rejected';
}
