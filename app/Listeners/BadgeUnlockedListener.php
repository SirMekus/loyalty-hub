<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;

class BadgeUnlockedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PaymentService $paymentProvider,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(BadgeUnlocked $event): void
    {
        $user = $event->user;
        $amount = config('business.cashback');

        // Simulate sending ₦300 to the user's bank account via the payment provider.
        $this->paymentProvider->disburse($user, $amount);

        /**
         * Credit the 'wallet' (record the earnings) so cumulative earnings are visible on 
         * the dashboard.
         * 
         * Note that the "wallet" is just figurative for an actual 
         * bank account that must have been 
         * credited. 
         * 
         */
        $wallet = $this->walletService->getWalletByModelOrId($user);
        $this->walletService->creditWallet(
            $wallet,
            $amount,
            'Badge unlock cashback – ₦'.$amount,
        );
    }
}
