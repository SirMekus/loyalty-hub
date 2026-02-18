<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class BadgeUnlockedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly WalletService $walletService)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BadgeUnlocked $event): void
    {
        $user   = $event->user;
        $wallet = $this->walletService->getWalletByModelOrId($user);

        $this->walletService->creditWallet(
            $wallet,
            config('business.cashback'),
            'Badge unlock cashback – ₦' . config('business.cashback'),
        );

        Log::info('Badge unlock cashback credited', [
            'user_id'  => $user->id,
            'amount'   => config('business.cashback'),
            'currency' => 'NGN',
        ]);
    }
}
