<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\CreatesArrayFromValues;

enum Currency: string
{
    use CreatesArrayFromValues;
    case NGN = 'NGN';
    case USD = 'USD';

    public function symbol(): string
    {
        return match($this){
            self::NGN => '₦',
            self::USD => '$',
            default => '₦'
        };
    }
}
