export type BadgeName = 'Unranked' | 'Bronze' | 'Silver' | 'Gold' | 'Platinum';
export type BadgeSize = 'sm' | 'md' | 'lg';

export interface BadgeConfig {
    gradient: string;
    glow: string;
    text: string;
    ring: string;
    icon: string;
    required: number;
}

export interface BadgeMedalProps {
    name: string;
    size?: BadgeSize;
    animate?: boolean;
}

export interface BadgeProgressBarProps {
    current: string;
    next: string;
    remaining: number;
}

export interface BadgeTimelineProps {
    current: string;
}

export const BADGE_CONFIG: Record<BadgeName, BadgeConfig> = {
    Unranked: {
        gradient: 'from-zinc-500 to-zinc-700',
        glow: 'shadow-zinc-500/30',
        text: 'text-zinc-300',
        ring: 'ring-zinc-400/40',
        icon: '◈',
        required: 0,
    },
    Bronze: {
        gradient: 'from-amber-600 to-amber-900',
        glow: 'shadow-amber-500/40',
        text: 'text-amber-300',
        ring: 'ring-amber-400/40',
        icon: '◈',
        required: 1,
    },
    Silver: {
        gradient: 'from-slate-300 to-slate-500',
        glow: 'shadow-slate-300/40',
        text: 'text-slate-200',
        ring: 'ring-slate-300/40',
        icon: '◈',
        required: 2,
    },
    Gold: {
        gradient: 'from-yellow-400 to-yellow-600',
        glow: 'shadow-yellow-400/50',
        text: 'text-yellow-100',
        ring: 'ring-yellow-400/50',
        icon: '◈',
        required: 3,
    },
    Platinum: {
        gradient: 'from-cyan-300 via-violet-400 to-fuchsia-400',
        glow: 'shadow-violet-400/50',
        text: 'text-white',
        ring: 'ring-violet-300/50',
        icon: '◈',
        required: 5,
    },
};

export const ALL_BADGES: BadgeName[] = [
    'Unranked',
    'Bronze',
    'Silver',
    'Gold',
    'Platinum',
];