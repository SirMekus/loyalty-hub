<?php

namespace App\Services;

use App\Enums\Badges;
use App\Models\User;

class BadgeService
{
    /**
     * Return the highest badge the user qualifies for.
     */
    public function resolveBadge(int $achievementCount): Badges
    {
        $earned = Badges::UNRANKED->value;

        $badges = Badges::cases();

        foreach ($badges as $badge) {
            $badgeName = $badge->normalizedName();
            $required = $badge->value;
            
            if ($achievementCount >= $required) {
                $earned = $badgeName;
            }
        }
        return Badges::normalizeForDatabaseEntry($earned);
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

        $nameOfCurrentBadge = $currentBadge->name;

        $badges = Badges::toArray();
        $badgeList = array_keys($badges);
        
        $badgeIdx  = array_search($nameOfCurrentBadge, $badgeList, true);

        if ($badgeIdx !== false && isset($badgeList[$badgeIdx + 1])) {
            $nextBadge = $badgeList[$badgeIdx + 1];
            
            $required = Badges::get($nextBadge)->value;
            
            $remaining = max(0, $required - $achievementCount);
        }

        return [
            'current_badge' => $nameOfCurrentBadge,
            'next_badge' => $nextBadge ?? 'None – you\'ve reached the top!',
            'remaining' => $remaining,
        ];
    }
}
