<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType
{
    case CREDIT;
    case DEBIT;
    case TRANSFER;
}
