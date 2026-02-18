<?php

declare(strict_types=1);

namespace App\Traits;

trait CreatesArrayFromValues
{
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function keys(): array
    {
        return array_column(self::cases(), 'name');
    }

    public function normalizedName()
    {
        return strtolower($this->name);
    }

    public static function toArray():array
    {
        $cases = static::cases();
        $arrayCase = [];

        foreach($cases as $case){
            $arrayCase[$case->name] = $case->value;
        }

        return $arrayCase;

    }
}
