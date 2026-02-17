import {AchievementCardProps, ACHIEVEMENT_ICONS} from "@/types/achievement";

export default function AchievementCard({ name, unlocked, index }: AchievementCardProps) {
    const icon = ACHIEVEMENT_ICONS[name] ?? '✦';
    return (
        <div
            className={`relative flex items-center gap-3 rounded-xl border px-4 py-3 transition-all duration-300 ${
                unlocked
                    ? 'border-white/20 bg-white/10 text-white'
                    : 'border-white/8 bg-white/3 text-white/35'
            } `}
            style={{ animationDelay: `${index * 80}ms` }}
        >
            <div
                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-lg ${unlocked ? 'bg-white/15' : 'bg-white/5'} `}
            >
                {unlocked ? (
                    icon
                ) : (
                    <span className="text-xs text-white/20">?</span>
                )}
            </div>
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">
                    {unlocked ? name : 'Locked'}
                </p>
                {unlocked && (
                    <p className="mt-0.5 text-xs text-white/50">
                        Achievement Unlocked
                    </p>
                )}
            </div>
            {unlocked && (
                <div className="h-2 w-2 shrink-0 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50" />
            )}
        </div>
    );
}