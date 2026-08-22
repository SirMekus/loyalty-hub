<?php

namespace App\Listeners;

use App\Events\BadgeUnlocked;
use App\Interfaces\MoneyTransfer;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;

class BadgeUnlockedListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(BadgeUnlocked $event): void
    {
        $user = $event->user;

        $amount = config('business.cashback'); // ₦300

        $paymentProvider = app(MoneyTransfer::class);

        $payment = $this->paymentService->startPayment($user, [
            'amount' => $amount,
            'provider' => $paymentProvider->getProvider(),
            'description' => 'Bonus',
        ]);

        // Simulate sending ₦300 to the user's bank account via the payment provider.
        $this->paymentService->disburse($payment);

        /**
         * Ideally, this should be done via a webhook. But to keep it simple, we just assume that any 'successful' payment will always be successful; we won't wait for webhook verification.
         */
        $this->paymentService->markAsComplete($payment);

        // ! todo: Once the payment thingy is completed, we phase out wallet service. Total disbursed should be fetched from the payments model.
        /**
         * Credit the 'wallet' (record the earnings) so cumulative earnings are visible on
         * the dashboard.
         *
         * Note that the "wallet" is just figurative for an actual
         * bank account that must have been
         * credited.
         */
        // $wallet = $this->walletService->getWalletByModelOrId($user);
        // $this->walletService->creditWallet(
        //     $wallet,
        //     $amount,
        //     'Badge unlock cashback – ₦'.$amount,
        // );
    }
}
