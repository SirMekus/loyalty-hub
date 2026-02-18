<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AchievementService;
use App\Services\BadgeService;
use Illuminate\Http\JsonResponse;

class UserAchievementController extends Controller
{
    public function __invoke(User $user): JsonResponse
    {
        $achievementProgress = app(AchievementService::class)->getProgress($user);

        // Find next badge tier and remaining achievements needed
        $badgeProgress = app(BadgeService::class)->getBadgeProgress($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'unlocked_achievements' => $achievementProgress['unlocked_achievements'],
            'next_available_achievements' => $achievementProgress['next_achievements'],
            'current_badge' => $badgeProgress['current_badge'],
            'next_badge' => $badgeProgress['next_badge'],
            'remaining_to_unlock_next_badge' => $badgeProgress['remaining'],
            'total_purchases' => $user->orders->count(),
            'wallet_balance' => $user->wallet->balance_formatted_with_currency,
        ]);
    }
}
