<?php

namespace App\Listeners;

use App\Events\PurchaseMade;
use App\Services\AchievementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PurchaseMadeListener implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(public AchievementService $service)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PurchaseMade $event): void
    {
        $user = $event->user;
        
        $this->service->handlePurchase($user);
    }
}
