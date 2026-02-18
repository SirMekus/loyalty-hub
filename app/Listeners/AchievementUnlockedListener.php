<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Services\AchievementService;
use App\Services\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;

class AchievementUnlockedListener implements ShouldQueue
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
    public function handle(AchievementUnlocked $event): void
    {
        /**
         * Fire BadgeUnlocked if the user's badge tier has changed.
         */
        $achievement = $event->achievement;
        $user = $achievement->user;

        $achievementCount = $user->achievements()->count();
        
        $earnedBadge = app(BadgeService::class)->resolveBadge($achievementCount);
        $currentBadge = $user->current_badge;
        
        if ($earnedBadge !== $currentBadge) {
            $user->update(['current_badge' => $earnedBadge]);

            event(new BadgeUnlocked($user));
        }
    }
}
