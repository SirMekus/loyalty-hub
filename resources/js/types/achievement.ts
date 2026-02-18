import type {BadgeName} from "@/types/badge";
import type {UserProfile} from "@/types/user";

export interface AchievementData {
    user: UserProfile;
    unlocked_achievements: string[];
    next_available_achievements: string[];
    current_badge: BadgeName;
    next_badge: string;
    remaining_to_unlock_next_badge: number;
    total_purchases: number;
    wallet_balance: number;
    // wallet_currency: string;
}

export interface AchievementCardProps {
    name: string;
    unlocked: boolean;
    index: number;
}

export const ACHIEVEMENT_ICONS: Record<string, string> = {
    'First Purchase': '🛍',
    'Purchase Streak': '🔥',
    'Mid Tier Shopper': '⭐',
    'High Tier Shopper': '💎',
    'Loyal Customer': '👑',
};

export const ALL_ACHIEVEMENT_NAMES: string[] = [
    'First Purchase',
    'Purchase Streak',
    'Mid Tier Shopper',
    'High Tier Shopper',
    'Loyal Customer',
];