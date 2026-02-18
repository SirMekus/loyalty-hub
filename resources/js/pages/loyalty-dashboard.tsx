import { useState, useEffect, useCallback } from 'react';
import AppLayout from '@/layouts/app-layout-alt';
import BadgeMedal from '@/components/loyalty/badge-medal';
import AchievementCard from '@/components/loyalty/achievement-card';
import BadgeProgressBar from '@/components/loyalty/badge-progress-bar';
import BadgeTimeline from '@/components/loyalty/badge-timeline';
import StatCard from '@/components/loyalty/stat-card';
import { AchievementData, ACHIEVEMENT_ICONS, ALL_ACHIEVEMENT_NAMES } from "@/types/achievement";
import { BADGE_CONFIG } from "@/types/badge";
import { usePage } from '@inertiajs/react';
import { UserProfile } from '@/types/user';

interface PageProps {
    users: UserProfile[];
    [key: string]: any;
}

async function fetchUserAchievements(userId: number): Promise<AchievementData> {
    const response = await fetch(`/api/users/${userId}/achievements`);
    if (!response.ok) throw new Error(`Request failed: ${response.status} ${response.statusText}`);
    return response.json();
}

export default function LoyaltyDashboard() {
    const { users } = usePage<PageProps>().props;
    const [selectedUserId, setSelectedUserId] = useState<number>(users[0]?.id ?? 0);
    const [data, setData] = useState<AchievementData | null>(null);
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);

    const load = useCallback(async (id: number): Promise<void> => {
        setLoading(true);
        setError(null);
        try {
            const result = await fetchUserAchievements(id);
            setData(result);
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Unknown error');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        load(selectedUserId);
    }, [selectedUserId, load]);

    const currentCfg = data
        ? (BADGE_CONFIG[data.current_badge] ?? BADGE_CONFIG.Unranked)
        : null;

    return (
        <>
            {/* User Switcher */}
            <section className="fade-up" style={{ animationDelay: '0ms' }}>
                <p className="font-display mb-3 text-xs tracking-widest text-white/30 uppercase">
                    Select Customer
                </p>
                <div className="flex flex-wrap gap-2">
                    {users.map((u) => (
                        <button
                            key={u.id}
                            onClick={() => setSelectedUserId(u.id)}
                            className={`user-btn rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-white/70 ${selectedUserId === u.id ? 'active text-white' : ''} `}
                        >
                            {u.name.split(' ')[0]}
                        </button>
                    ))}
                </div>
            </section>

            {loading && (
                <div className="flex items-center justify-center py-24">
                    <div className="h-10 w-10 animate-spin rounded-full border-2 border-violet-500/30 border-t-violet-400" />
                </div>
            )}

            {error && (
                <div className="glass rounded-2xl border-red-500/20 p-6 text-center text-red-400">
                    {error}
                </div>
            )}

            {data && !loading && currentCfg && (
                <>
                    {/* Hero — User + Badge */}
                    <section
                        className="glass fade-up rounded-2xl p-6"
                        style={{ animationDelay: '80ms' }}
                    >
                        <div className="flex flex-col items-start gap-6 sm:flex-row sm:items-center">
                            {/* Avatar */}
                            <div className="relative shrink-0">
                                <div
                                    className={`h-16 w-16 rounded-2xl bg-linear-to-br ${currentCfg.gradient} font-display flex items-center justify-center text-2xl font-bold shadow-xl ${currentCfg.glow} ring-1 ${currentCfg.ring} `}
                                >
                                    {data.user.name[0]}
                                </div>
                                <div
                                    className="absolute -right-1 -bottom-1 h-6 w-6 rounded-full border-2 border-[#080c14] bg-linear-to-br from-emerald-400 to-emerald-600"
                                    title="Online"
                                />
                            </div>

                            {/* Info */}
                            <div className="min-w-0 flex-1">
                                <h1 className="font-display truncate text-2xl font-bold tracking-tight">
                                    {data.user.name}
                                </h1>
                                <p className="mt-0.5 text-sm text-white/40">
                                    {data.user.email}
                                </p>
                            </div>

                            {/* Current Badge */}
                            <div className="flex items-center gap-4 rounded-2xl border border-white/8 bg-white/5 px-5 py-4">
                                <div
                                    style={{
                                        animation:
                                            'float 3s ease-in-out infinite',
                                    }}
                                >
                                    <BadgeMedal
                                        name={data.current_badge}
                                        size="lg"
                                    />
                                </div>
                                <div>
                                    <p className="font-display mb-1 text-xs tracking-widest text-white/40 uppercase">
                                        Current Badge
                                    </p>
                                    <p
                                        className={`font-display text-xl font-bold ${currentCfg.text}`}
                                    >
                                        {data.current_badge}
                                    </p>
                                    {data.next_badge !==
                                        "None – you've reached the top!" && (
                                        <p className="mt-1 text-xs text-white/30">
                                            Next: {data.next_badge}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Stats row */}
                    <div
                        className="fade-up grid grid-cols-3 gap-3"
                        style={{ animationDelay: '120ms' }}
                    >
                        <StatCard
                            label="Total Purchases"
                            value={data.total_purchases}
                            sub="orders placed"
                        />
                        <StatCard
                            label="Achievements"
                            value={`${data.unlocked_achievements.length} / 5`}
                            sub="unlocked"
                        />
                        <StatCard
                            label="To Next Badge"
                            value={
                                data.remaining_to_unlock_next_badge === 0
                                    ? '✓'
                                    : data.remaining_to_unlock_next_badge
                            }
                            sub={
                                data.remaining_to_unlock_next_badge === 0
                                    ? 'max tier'
                                    : 'achievements needed'
                            }
                        />
                    </div>

                    {/* Wallet Balance */}
                    <div
                        className="glass fade-up rounded-2xl p-5"
                        style={{ animationDelay: '140ms' }}
                    >
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="font-display mb-1 text-xs tracking-widest text-white/30 uppercase">
                                    Wallet Balance
                                </p>
                                <p className="font-display text-3xl font-bold text-emerald-400">
                                    {data.wallet_balance}
                                </p>
                                <p className="mt-1 text-xs text-white/30">
                                    cashback from badge unlocks
                                </p>
                            </div>
                            <div className="flex h-12 w-12 items-center justify-center rounded-2xl border border-emerald-500/20 bg-emerald-500/10 text-2xl">
                                💳
                            </div>
                        </div>
                    </div>

                    {/* Badge Journey */}
                    <section
                        className="glass fade-up rounded-2xl p-6"
                        style={{ animationDelay: '160ms' }}
                    >
                        <h2 className="font-display mb-5 text-sm font-semibold tracking-widest text-white/50 uppercase">
                            Badge Journey
                        </h2>
                        <BadgeTimeline current={data.current_badge} />
                        <div className="mt-6 border-t border-white/8 pt-6">
                            <BadgeProgressBar
                                current={data.current_badge}
                                next={data.next_badge}
                                remaining={data.remaining_to_unlock_next_badge}
                            />
                        </div>
                    </section>

                    {/* Achievements */}
                    <section
                        className="glass fade-up rounded-2xl p-6"
                        style={{ animationDelay: '200ms' }}
                    >
                        <div className="mb-5 flex items-center justify-between">
                            <h2 className="font-display text-sm font-semibold tracking-widest text-white/50 uppercase">
                                Achievements
                            </h2>
                            <span className="rounded-full bg-white/8 px-3 py-1 text-xs text-white/50">
                                {data.unlocked_achievements.length} unlocked
                            </span>
                        </div>
                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            {ALL_ACHIEVEMENT_NAMES.map((name, i) => (
                                <AchievementCard
                                    key={name}
                                    name={name}
                                    unlocked={data.unlocked_achievements.includes(
                                        name,
                                    )}
                                    index={i}
                                />
                            ))}
                        </div>
                    </section>

                    {/* Next Available */}
                    {data.next_available_achievements.length > 0 && (
                        <section
                            className="glass fade-up rounded-2xl p-6"
                            style={{ animationDelay: '240ms' }}
                        >
                            <h2 className="font-display mb-4 text-sm font-semibold tracking-widest text-white/50 uppercase">
                                Up Next
                            </h2>
                            <div className="flex flex-wrap gap-2">
                                {data.next_available_achievements.map(
                                    (name) => (
                                        <div
                                            key={name}
                                            className="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2"
                                        >
                                            <span className="text-base text-white/25">
                                                {ACHIEVEMENT_ICONS[name] ?? '✦'}
                                            </span>
                                            <span className="text-sm text-white/40">
                                                {name}
                                            </span>
                                        </div>
                                    ),
                                )}
                            </div>
                        </section>
                    )}

                    {data.next_available_achievements.length === 0 && (
                        <section
                            className="glass fade-up rounded-2xl p-6 text-center"
                            style={{ animationDelay: '240ms' }}
                        >
                            <div className="mb-3 text-4xl">🏆</div>
                            <h3 className="font-display text-lg font-bold text-white">
                                All Achievements Unlocked!
                            </h3>
                            <p className="mt-1 text-sm text-white/40">
                                You've conquered every challenge. Keep shopping
                                for badge upgrades!
                            </p>
                        </section>
                    )}

                    {/* Cashback note */}
                    <div
                        className="fade-up flex items-start gap-3 rounded-xl border border-emerald-500/15 bg-emerald-500/5 px-4 py-3 text-sm text-emerald-400/70"
                        style={{ animationDelay: '280ms' }}
                    >
                        <span className="mt-0.5 text-base">💸</span>
                        <span>
                            A{' '}
                            <strong className="text-emerald-400">
                                ₦300 cashback
                            </strong>{' '}
                            is automatically credited each time you unlock a new
                            badge.
                        </span>
                    </div>
                </>
            )}
        </>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Inertia layout binding
// Tells Inertia to wrap this page in my custom layout (app-alt) instead of the default
// App layout used by the rest of the application (created when I bootstrapped the location).
// ─────────────────────────────────────────────────────────────────────────────
LoyaltyDashboard.layout = (page: React.ReactNode) => (
    <AppLayout>
        {page}
    </AppLayout>
);
