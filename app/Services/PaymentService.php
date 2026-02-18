<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Simulates disbursing a cashback payment to a user's bank account.
 */
class PaymentService
{
    /**
     * @return array{reference: string, status: string, amount: int, currency: string}
     */
    public function disburse(User $user, int $amountInNaira, string $currency = 'NGN'): array
    {
        $reference = 'MOCK-'.strtoupper(Str::random(12));

        Log::info('[MockPaymentProvider] Cashback disbursed', [
            'reference' => $reference,
            'user_id' => $user->id,
            'amount' => $amountInNaira,
            'currency' => $currency,
            'status' => 'success',
            'note' => 'Enjoy your weekend. Na you dey reign, boss!',
        ]);

        return [
            'reference' => $reference,
            'status' => 'success',
            'amount' => $amountInNaira,
            'currency' => $currency,
        ];
    }
}
