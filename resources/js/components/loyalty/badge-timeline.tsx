import {BadgeName, BadgeConfig, BadgeTimelineProps, ALL_BADGES, BADGE_CONFIG} from "@/types/badge";

export default function BadgeTimeline({ current }: BadgeTimelineProps) {

    const currentIdx = ALL_BADGES.indexOf(current as BadgeName);
    return (
        <div className="flex items-center gap-1">
            {ALL_BADGES.map((badge, idx) => {
                const cfg = BADGE_CONFIG[badge];
                const reached = idx <= currentIdx;
                const isCurrent = badge === current;
                return (
                    <div key={badge} className="flex items-center">
                        <div className="flex flex-col items-center gap-1">
                            <div
                                className={`flex h-8 w-8 items-center justify-center rounded-full bg-linear-to-br text-sm transition-all duration-300 ${reached ? `${cfg.gradient} shadow-md ${cfg.glow}` : 'bg-white/5 opacity-30'} ${isCurrent ? `ring-2 ${cfg.ring} scale-110` : ''} `}
                            >
                                {cfg.icon}
                            </div>
                            <span
                                className={`text-[10px] font-medium ${
                                    reached ? cfg.text : 'text-white/20'
                                } ${isCurrent ? 'opacity-100' : 'opacity-60'}`}
                            >
                                {badge}
                            </span>
                        </div>
                        {idx < ALL_BADGES.length - 1 && (
                            <div
                                className={`mx-1 mb-4 h-0.5 w-8 rounded-full ${idx < currentIdx ? 'bg-white/30' : 'bg-white/8'} `}
                            />
                        )}
                    </div>
                );
            })}
        </div>
    );
}