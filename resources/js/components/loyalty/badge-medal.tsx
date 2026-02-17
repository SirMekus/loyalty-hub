import {BadgeName, BadgeSize, BadgeConfig, BadgeMedalProps, BADGE_CONFIG} from "@/types/badge";

export default function BadgeMedal({ name, size = 'md', animate = false }: BadgeMedalProps) {
    const cfg = BADGE_CONFIG[name as BadgeName] ?? BADGE_CONFIG.Unranked;
    const sizes: Record<BadgeSize, string> = {
        sm: 'w-10 h-10 text-lg',
        md: 'w-16 h-16 text-2xl',
        lg: 'w-24 h-24 text-4xl',
    };

    return (
        <div
            className={` ${sizes[size]} rounded-full bg-linear-to-br ${cfg.gradient} flex items-center justify-center shadow-xl ${cfg.glow} ring-2 ${cfg.ring} ${animate ? 'animate-pulse' : ''} relative overflow-hidden`}
        >
            <span className="relative z-10">{cfg.icon}</span>
            <div className="absolute inset-0 rounded-full bg-linear-to-tr from-white/20 to-transparent" />
        </div>
    );
}