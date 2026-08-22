<?php

namespace App\Services;

use App\Enums\Achievements;
use App\Events\AchievementUnlocked;
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
        $purchaseCount = $user->orders()->count();

        $achievementList = Achievements::cases();

        foreach ($achievementList as $achievement) {
            $required = $achievement->value;
            $name = $achievement->name;
            if ($purchaseCount >= $required && ! in_array($name, $previousUnlocked, true)) {
                // Persist the newly unlocked achievement
                $achievement = $user->achievements()->create(['name' => $name]);
                event(new AchievementUnlocked($achievement->name, $user));
            }
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
     * Return the next achievement the user can unlock — only the immediate next one,
     * not every remaining achievement, since Achievements form a single ordered
     * progression (by required purchase count) rather than independent groups.
     */
    public function getNextAchievements(User $user): array
    {
        $unlocked = $this->getUnlockedAchievements($user);

        foreach (Achievements::cases() as $achievement) {
            if (! in_array($achievement->name, $unlocked, true)) {
                return [$achievement->name];
            }
        }

        return [];
    }

    /**
     * Summary of getProgress
     *
     * @return array{next_achievements: array, unlocked_achievements: array}
     */
    public function getProgress(User $user): array
    {
        $unlockedRaw = $this->getUnlockedAchievements($user);
        $nextRaw = $this->getNextAchievements($user);

        /**
         * Names stored in database use underscore case: First_Purchase, Purchase_Streak, etc.
         *
         * Convert to display format: First_Purchase → First Purchase
         */
        $unlocked = array_map(fn ($n) => str_replace('_', ' ', $n), $unlockedRaw);
        $next = array_map(fn ($n) => str_replace('_', ' ', $n), $nextRaw);

        return [
            'unlocked_achievements' => $unlocked,
            'next_achievements' => $next,
        ];
    }
}
