<?php

declare(strict_types=1);

namespace App\Services;

final class Money
{
    public const DIVIDE_BY = 100;
    /**
     * converts naira to kobo
     *
     * @param mixed $naira
     * @return int
     */
    public static function toBaseDenomination($naira): int
    {
        return (int) (((float) $naira) * self::DIVIDE_BY);
    }
    /**
     * converts kobo to naira
     *
     * @param mixed $kobo
     * @return string
     */
    public static function toOfficialDenomination($kobo): int
    {
        return (int) round((int) $kobo / self::DIVIDE_BY, 2);
    }
}
