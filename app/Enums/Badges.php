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
    case BRONZE = 1;
    case SILVER = 2;
    case GOLD = 3;
    case PLATINUM = 5;

    public static function normalizeForDatabaseEntry(string $name): self
    {
        return match ($name) {
            'unranked', 'UNRANKED' => self::UNRANKED,
            'BRONZE', 'bronze' => self::BRONZE,
            'SILVER', 'silver' => self::SILVER,
            'GOLD', 'gold' => self::GOLD,
            'PLATINUM', 'platinum' => self::PLATINUM,
            default => throw new Exception('Unsupported Enum type')
        };
    }

    public static function get(string|int $badge): self
    {
        if (! is_numeric($badge)) {
            return self::getFromStr($badge);
        }

        return self::getFromInt($badge);
    }

    protected static function getFromInt(int $badge): self
    {
        return match ($badge) {
            self::UNRANKED->value => self::UNRANKED,
            self::BRONZE->value => self::BRONZE,
            self::SILVER->value => self::SILVER,
            self::GOLD->value => self::GOLD,
            self::PLATINUM->value => self::PLATINUM,
            default => throw new Exception('Unsupported Enum type')
        };
    }

    protected static function getFromStr(string $badge): self
    {
        return match ($badge) {
            self::UNRANKED->name => self::UNRANKED,
            self::BRONZE->name => self::BRONZE,
            self::SILVER->name => self::SILVER,
            self::GOLD->name => self::GOLD,
            self::PLATINUM->name => self::PLATINUM,
            default => throw new Exception('Unsupported Enum type')
        };
    }
}
