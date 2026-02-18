import type {StatCardProps} from "@/types/stats";

export default function StatCard({ label, value, sub }: StatCardProps) {
    return (
        <div className="rounded-xl border border-white/10 bg-white/6 p-4 text-center">
            <div className="mb-1 text-2xl font-bold text-white">{value}</div>
            <div className="text-xs text-white/50">{label}</div>
            {sub && <div className="mt-0.5 text-xs text-white/30">{sub}</div>}
        </div>
    );
}