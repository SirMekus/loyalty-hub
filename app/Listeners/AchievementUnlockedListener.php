<?php

namespace App\Listeners;

use App\Enums\Badges;
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
        $user = $event->user;

        $achievementCount = $user->achievements()->count();

        $earnedBadge = app(BadgeService::class)->resolveBadge($achievementCount);
        // A user's current_badge defaults to UNRANKED at the DB level, but a model
        // instance that hasn't been refetched since creation won't have that default
        // hydrated in memory.
        $currentBadge = $user->current_badge ?? Badges::UNRANKED;

        if ($earnedBadge === $currentBadge) {
            return;
        }

        /**
         * Award every badge tier strictly between the current one and the newly
         * qualified-for one, not just the final tier. Achievements can unlock in a
         * burst — e.g. several orders created before the queue catches up — which
         * can push the achievement count past more than one badge threshold by the
         * time this listener runs. Jumping straight to the highest tier would
         * silently skip crediting the ones in between.
         */
        foreach (Badges::cases() as $badge) {
            if ($badge->value > $currentBadge->value && $badge->value <= $earnedBadge->value) {
                event(new BadgeUnlocked($badge->name, $user));
            }
        }

        $user->update(['current_badge' => $earnedBadge]);
    }
}
