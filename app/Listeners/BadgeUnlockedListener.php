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
    }
}
