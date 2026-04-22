<?php

namespace App\Enums;

enum EventType: string
{
    case USER_REGISTERED = 'user_registered';
    case USER_LOGGED_IN = 'user_logged_in';
    case MAP_SEARCH = 'map_search';
    case RECORD_VIEWED = 'record_viewed';
    case PROFILE_VIEWED = 'profile_viewed';
    case RECORD_CREATED = 'record_created';
    case OBSERVATION_CREATED = 'observation_created';
    case RECORD_VERIFIED = 'record_verified';
    case FLAG_CREATED = 'flag_created';
}
