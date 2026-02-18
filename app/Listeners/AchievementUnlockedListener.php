<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Services\AchievementService;
use App\Services\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $achievement = $event->achievement;
        $user = $achievement->user;

        $achievementCount = $user->achievements()->count();
        // dd($achievementCount);
        $earnedBadge      = app(BadgeService::class)->resolveBadge($achievementCount);
        $currentBadge     = $user->current_badge;
// dd($earnedBadge, $currentBadge);
        if ($earnedBadge !== $currentBadge) {
            // dd($earnedBadge,$currentBadge);
            // request()->user()->transactionSetting->fill(['pin' => $validatedData['pin']])->save();
            // dd($earnedBadge);
            // $user->current_badge = $earnedBadge;
            // $user->save();
            $user->update(['current_badge' => $earnedBadge]);

            event(new BadgeUnlocked($user));
        }
        
        // $this->service->checkAndFireBadge($achievement->user);
    }
}
