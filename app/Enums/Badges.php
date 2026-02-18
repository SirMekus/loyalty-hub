<?php

declare(strict_types=1);

namespace App\Enums;
use App\Traits\CreatesArrayFromValues;
use Exception;
/**
 * Badges defined by number of achievements required.
 */
enum Badges: int
{
    use CreatesArrayFromValues;
    case UNRANKED = 0;
    case BRONZE = 4;
    case SILVER = 8;
    case GOLD = 12;
    case PLATINUM = 16;

    public static function normalizeForDatabaseEntry(string $name):self
    {
        return match($name){
            'unranked', "UNRANKED" => static::UNRANKED,
            'BRONZE', "bronze" => static::BRONZE,
            'SILVER', "silver" => static::SILVER,
            'GOLD', "gold" => static::GOLD,
            'PLATINUM', "platinum" => static::PLATINUM,
            default => throw new Exception("Unsupported Enum type")
        };
    }

    public static function get(string|int $badge):self
    {
        if(!is_numeric($badge)){
            return self::getFromStr($badge);
        }
        return self::getFromInt($badge);
    }

    protected static function getFromInt(int $badge):self
    {
        return match($badge){
            static::UNRANKED->value => static::UNRANKED,
            static::BRONZE->value => static::BRONZE,
            static::SILVER->value => static::SILVER,
            static::GOLD->value => static::GOLD,
            static::PLATINUM->value => static::PLATINUM,
            default => throw new Exception("Unsupported Enum type")
        };
    }

    protected static function getFromStr(string $badge):self
    {
        return match($badge){
            static::UNRANKED->name => static::UNRANKED,
            static::BRONZE->name => static::BRONZE,
            static::SILVER->name => static::SILVER,
            static::GOLD->name => static::GOLD,
            static::PLATINUM->name => static::PLATINUM,
            default => throw new Exception("Unsupported Enum type")
        };
    }
}
