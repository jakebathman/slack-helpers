<?php

namespace App;

use Illuminate\Support\Arr;

class UserChecker
{
    public static function callingUserAllowed($user)
    {
        return static::isNormalUser($user);
    }

    public static function isSingleChannelGuest($user)
    {
        return Arr::get($user, 'is_ultra_restricted', false) && Arr::get($user, 'is_restricted', false);
    }

    public static function isMultiChannelGuest($user)
    {
        return Arr::get($user, 'is_restricted', false) && ! Arr::get($user, 'is_ultra_restricted', false);
    }

    public static function isNormalUser($user)
    {
        return ! static::isSingleChannelGuest($user) && ! static::isMultiChannelGuest($user);
    }
}
