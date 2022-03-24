<?php

namespace App;

class StatusMatcher
{
    const PREG_IN = '([@!\+](in|ingrid|​ingrid|innie|iinne)([^\w]|$)|^in$)';
    const PREG_BREAK = '([@!\+](brb|break|relo|walk)([^\w]|$)|^brb$|^:(coffee|latte):$|^(:tea:|:tea_cat:)(\s*?:timer_clock:)?$|^(:coffee_cat:)|^(:diet-?coke:)$)';
    const PREG_LUNCH = '([@!\+](lunch(ito)?|breakfast|brunch|dinner|lunching|snack(ing)?)([^\w]|$)|^lunch( time)?$)';
    const PREG_BACK = '([@!\+]back([^\w]|$)|^back$)';
    const PREG_OUT = '([@!\+](out|ofnbl|ofn|oot|notin|vote|voting|therapy|errands?|nap)([^\w]|$)|^out$)';
    const PREG_SUBTEAM_MENTION = '/\<\!subteam\^(?:[A-Z0-9]+)(?:\|(.*?))?\>/i';
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
