<?php

namespace App\Services;

use App\Enums\Achievements;
use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Models\User;

class AchievementService
{
    /**
     * Handle a new purchase for the given user.
     * Checks and fires achievement/badge unlock events as appropriate.
     */
    public function handlePurchase(User $user): void
    {
        $previousUnlocked = $this->getUnlockedAchievements($user);
        $purchaseCount    = $user->orders()->count();

        $achievementList = Achievements::cases();

        foreach ($achievementList as $achievement) {
            $required = $achievement->value;
            $name = $achievement->name;
            if ($purchaseCount >= $required && ! in_array($name, $previousUnlocked, true)) {
                // Persist the newly unlocked achievement
                $achievement = $user->achievements()->create(['name' => $name]);
                event(new AchievementUnlocked($achievement));
            }
        }
    }

    /**
     * Fire BadgeUnlocked if the user's badge tier has changed.
     */
    public function checkAndFireBadge(User $user): void
    {
        $achievementCount = $user->achievements()->count();
        $earnedBadge      = app(BadgeService::class)->resolveBadge($achievementCount);
        $currentBadge     = $user->current_badge;

        if ($earnedBadge !== $currentBadge) {
            $user->update(['current_badge' => $earnedBadge]);

            event(new BadgeUnlocked($user));
        }
    }

    /**
     * Return array of achievement names the user has unlocked.
     */
    public function getUnlockedAchievements(User $user): array
    {
        return $user->achievements()->pluck('name')->toArray();
    }

    /**
     * Return achievements not yet unlocked.
     */
    public function getNextAchievements(User $user): array
    {
        $unlocked = $this->getUnlockedAchievements($user);
        $achievementList = Achievements::keys();

        return array_values(
            array_filter(
                $achievementList,
                fn (string $name) => ! in_array($name, $unlocked, true)
            )
        );
    }
}
