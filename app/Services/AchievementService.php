<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Events\BadgeUnlocked;
use App\Models\User;

class AchievementService
{
    /**
     * Achievements defined by number of purchases required.
     */
    public const ACHIEVEMENTS = [
        'First Purchase'       => 1,
        'Purchase Streak'      => 5,
        'Mid Tier Shopper'     => 10,
        'High Tier Shopper'    => 15,
        'Loyal Customer'       => 20,
    ];

    /**
     * Badges defined by number of achievements required.
     */
    public const BADGES = [
        'Unranked'   => 0,
        'Bronze'     => 4,
        'Silver'     => 8,
        'Gold'       => 12,
        'Platinum'   => 16,
    ];

    /**
     * Handle a new purchase for the given user.
     * Checks and fires achievement/badge unlock events as appropriate.
     */
    public function handlePurchase(User $user): void
    {
        $previousUnlocked = $this->getUnlockedAchievements($user);
        $purchaseCount    = $user->orders()->count();

        foreach (self::ACHIEVEMENTS as $name => $required) {
            if ($purchaseCount >= $required && ! in_array($name, $previousUnlocked, true)) {
                // Persist the newly unlocked achievement
                $user->achievements()->create(['name' => $name]);

                event(new AchievementUnlocked($name, $user));
            }
        }

        // Re-fetch after potentially persisting new achievements
        $this->checkAndFireBadge($user);
    }

    /**
     * Fire BadgeUnlocked if the user's badge tier has changed.
     */
    protected function checkAndFireBadge(User $user): void
    {
        $achievementCount = $user->achievements()->count();
        $earnedBadge      = $this->resolveBadge($achievementCount);
        $currentBadge     = $user->current_badge ?? 'Unranked';

        if ($earnedBadge !== $currentBadge) {
            $user->update(['current_badge' => $earnedBadge]);

            event(new BadgeUnlocked($earnedBadge, $user));
        }
    }

    /**
     * Return the highest badge the user qualifies for.
     */
    public function resolveBadge(int $achievementCount): string
    {
        $earned = 'Unranked';

        foreach (self::BADGES as $badge => $required) {
            if ($achievementCount >= $required) {
                $earned = $badge;
            }
        }

        return $earned;
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
        $unlocked      = $this->getUnlockedAchievements($user);
        $purchaseCount = $user->orders()->count();

        return array_values(
            array_filter(
                array_keys(self::ACHIEVEMENTS),
                fn (string $name) => ! in_array($name, $unlocked, true)
            )
        );
    }

    /**
     * Return current badge info and progress toward the next badge.
     *
     * @return array{current_badge: string, next_badge: string, remaining: int}
     */
    public function getBadgeProgress(User $user): array
    {
        $achievementCount = $user->achievements()->count();
        $currentBadge     = $this->resolveBadge($achievementCount);
        $nextBadge        = null;
        $remaining        = 0;

        $badgeList = array_keys(self::BADGES);
        $badgeIdx  = array_search($currentBadge, $badgeList, true);

        if ($badgeIdx !== false && isset($badgeList[$badgeIdx + 1])) {
            $nextBadge = $badgeList[$badgeIdx + 1];
            $required  = self::BADGES[$nextBadge];
            $remaining = max(0, $required - $achievementCount);
        }

        return [
            'current_badge' => $currentBadge,
            'next_badge'    => $nextBadge ?? 'None – you\'ve reached the top!',
            'remaining'     => $remaining,
        ];
    }
}
