import type {BadgeName, BadgeProgressBarProps} from "@/types/badge";
import { BADGE_CONFIG} from "@/types/badge";
import BadgeMedal from "./badge-medal";

export default function BadgeProgressBar({ current, next, remaining }: BadgeProgressBarProps) {
    const currentCfg =
        BADGE_CONFIG[current as BadgeName] ?? BADGE_CONFIG.Unranked;
    const nextCfg = BADGE_CONFIG[next as BadgeName] ?? null;

    const currentRequired = currentCfg.required;
    const nextRequired = nextCfg ? nextCfg.required : currentRequired;
    const total = nextRequired - currentRequired;
    const done = total - remaining;
    const pct = total > 0 ? Math.round((done / total) * 100) : 100;

    return (
        <div className="space-y-3">
            <div className="flex items-center justify-between text-sm">
                <div className="flex items-center gap-2">
                    <BadgeMedal name={current} size="sm" />
                    <span className={`font-semibold ${currentCfg.text}`}>
                        {current}
                    </span>
                </div>
                {nextCfg ? (
                    <div className="flex items-center gap-2">
                        <span className="text-xs text-white/40">
                            {remaining} to go
                        </span>
                        <BadgeMedal name={next} size="sm" />
                        <span className={`font-semibold ${nextCfg.text}`}>
                            {next}
                        </span>
                    </div>
                ) : (
                    <span className="text-xs text-white/50 italic">
                        Max rank reached
                    </span>
                )}
            </div>

            <div className="relative h-3 overflow-hidden rounded-full bg-white/10">
                <div
                    className={`absolute inset-y-0 left-0 rounded-full bg-linear-to-r ${currentCfg.gradient} transition-all duration-1000 ease-out`}
                    style={{ width: `${pct}%` }}
                />
                <div
                    className="absolute inset-0 bg-linear-to-r from-transparent via-white/20 to-transparent"
                    style={{
                        backgroundSize: '200% 100%',
                        animation: 'shimmer 2s infinite linear',
                    }}
                />
            </div>

            <div className="flex justify-between text-xs text-white/40">
                <span>{done} achievements</span>
                {nextCfg && <span>Goal: {nextRequired}</span>}
            </div>
        </div>
    );
}