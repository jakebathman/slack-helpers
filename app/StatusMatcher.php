<?php

namespace App;

class StatusMatcher
{
    const PREG_IN = '([@!\+](in|ingrid|​ingrid|innie|iinne|inward|ihavearrived)([^\w]|$)|^in$|SJJ4NPRNU)';
    const PREG_BREAK = '([@!\+](brb|break|relo|walk|lilbreakybreak)([^\w]|$)|^brb$|^:(coffee|latte|coffin):$|^(:tea:|:tea_cat:)(\s*?:timer_clock:)?$|^(:coffee_cat:)|^(:diet-?coke:)$)';
    const PREG_LUNCH = '([@!\+](lunch(ito|eon)?|breakfast|brunch|dinner|lunching|snack(ing)?|banquet)([^\w]|$)|^lunch( time)?$)';
    const PREG_BACK = '([@!\+]back([^\w]|$)|^back$)';
    const PREG_OUT = '([@!\+](out|ofnbl|ofn|oot|notin|vote|voting|therapy|errands?|nap|outties?|outage|outward|farewellminions|workout)([^\w]|$)|^out$|S013Y6JHHAM)';
    const PREG_SUBTEAM_MENTION = '/\<\!subteam\^([A-Z0-9]+)(?:\|(.*?))?\>/i';
    const PREG_SPECIAL_MENTION = '/\<\!(here|channel|everyone)\>/i';

    public static function mergedPattern()
    {
        return self::pregPattern(
            self::PREG_IN,
            self::PREG_BREAK,
            self::PREG_OUT,
            self::PREG_LUNCH,
            self::PREG_BACK
        );
    }

    public static function hasIn($text)
    {
        return preg_match(self::pregPattern(self::PREG_IN), $text);
    }

    public static function hasBreak($text)
    {
        return preg_match(self::pregPattern(self::PREG_BREAK), $text);
    }

    public static function hasLunch($text)
    {
        return preg_match(self::pregPattern(self::PREG_LUNCH), $text);
    }

    public static function hasBack($text)
    {
        return preg_match(self::pregPattern(self::PREG_BACK), $text);
    }

    public static function hasOut($text)
    {
        return preg_match(self::pregPattern(self::PREG_OUT), $text);
    }

    public static function pregPattern(...$patterns)
    {
        return '/(' . implode('|', $patterns) . ')/i';
    }
}
