<?php

declare(strict_types=1);

namespace App\Enums;
use App\Traits\CreatesArrayFromValues;

/**
 * Achievements defined by number of purchases required.
 */
enum Achievements: int
{
    use CreatesArrayFromValues;
    case First_Purchase = 1;
    case Purchase_Streak = 5;
    case Mid_Tier_Shopper = 10;
    case High_Tier_Shopper = 15;
    case Loyal_Customer = 20;
}
