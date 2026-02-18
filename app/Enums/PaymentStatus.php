<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: int
{
    case PENDING = 1;
    case VERIFIED = 2;
    case COMPLETED = 3;
    case FAILED = 4;
    case REFUNDED = 5;

    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::VERIFIED => 'verified',
            self::COMPLETED => 'success',
            self::FAILED => 'failed',
            self::REFUNDED => 'refunded',
            default => 'Unknown'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'primary',
            self::VERIFIED => 'info',
            self::COMPLETED => 'success',
            self::REFUNDED => 'secondary',
            self::FAILED => 'danger',
            default => 'primary',
        };
    }

    public static function getFormattedDescription(): array
    {
        $formatted = [];
        $descriptions = self::cases();
        foreach ($descriptions as $description) {
            $formatted[] = $description->getDescription();
        }

        return $formatted;
    }
}
