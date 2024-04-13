<?php

namespace Professionator;

class GlobalRequestCounter
{

    private static int $wowHeadRequestCounter = 0;
    private static int $blizRequestCounter = 0;
    private static array $wowHeadRequestCounterByClass = [];

    public static function incrementWowheadCounter(): void
    {
        self::$wowHeadRequestCounter++;
    }

    public static function incrementBlizCounter(): void
    {
        self::$blizRequestCounter++;
    }

    public static function get(): array
    {
        return [
            'wowhead'  => self::$wowHeadRequestCounter,
            'blizzard' => self::$blizRequestCounter,
        ];
    }

    public static function reset(): void
    {
        self::$wowHeadRequestCounter = 0;
        self::$blizRequestCounter = 0;
        self::$wowHeadRequestCounterByClass = [];
    }

}