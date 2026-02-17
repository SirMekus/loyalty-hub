<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Badges defined by number of achievements required.
 */
enum Badges: int
{
    case Unranked = 0;
    case Bronze = 4;
    case Silver = 8;
    case Gold = 12;
    case Platinum = 16;
}
