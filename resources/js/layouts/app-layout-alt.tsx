import { type ReactNode } from "react";

// ─────────────────────────────────────────────────────────────────────────────
// All styles for the loyalty section live here so LoyaltyDashboard stays clean.
// ─────────────────────────────────────────────────────────────────────────────

const LOYALTY_STYLES = `
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500&display=swap');

  /* box-sizing scoped to this tree only — no global reset */
  .lh-root, .lh-root * { box-sizing: border-box; }

  .lh-root {
    font-family: 'DM Sans', sans-serif;
    color: white;
    min-height: 100vh;
    background:
      radial-gradient(ellipse 60% 50% at 20% 20%, rgba(139,92,246,0.12) 0%, transparent 60%),
      radial-gradient(ellipse 40% 40% at 80% 80%, rgba(6,182,212,0.08) 0%, transparent 60%),
      radial-gradient(ellipse 50% 60% at 50% 50%, rgba(16,24,40,0.8) 0%, transparent 100%),
      #080c14;
  }

  /* Centering container — not relying on Tailwind mx-auto */
  .lh-inner {
    width: 100%;
    max-width: 1024px;
    margin-left: auto;
    margin-right: auto;
    padding-left: 20px;
    padding-right: 20px;
  }

  .lh-header {
    background: rgba(255,255,255,0.04);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,0.09);
    position: sticky;
    top: 0;
    z-index: 50;
  }

  .lh-header-inner {
    width: 100%;
    max-width: 1024px;
    margin-left: auto;
    margin-right: auto;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .lh-main {
    width: 100%;
    max-width: 1024px;
    margin-left: auto;
    margin-right: auto;
    padding: 32px 20px 64px;
    display: flex;
    flex-direction: column;
    gap: 24px;
  }

  .lh-root .font-display { font-family: 'Syne', sans-serif; }

  @keyframes lh-shimmer {
    0%   { background-position: -200% 0; }
    100% { background-position:  200% 0; }
  }

  @keyframes lh-float {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-6px); }
  }

  @keyframes lh-fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0);    }
  }

  .lh-root .fade-up  { animation: lh-fadeUp 0.5s ease-out both; }
  .lh-root .lh-float { animation: lh-float  3s ease-in-out infinite; }
  .lh-root .lh-shimmer {
    background-size: 200% 100%;
    animation: lh-shimmer 2s infinite linear;
  }

  .lh-root .glass {
    background: rgba(255,255,255,0.04);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.09);
  }

  .lh-root .user-btn { transition: all 0.2s ease; }
  .lh-root .user-btn:hover  { background: rgba(255,255,255,0.08); }
  .lh-root .user-btn.active {
    background: rgba(255,255,255,0.12);
    border-color: rgba(255,255,255,0.25);
  }
`;

interface LoyaltyLayoutProps {
  children: ReactNode;
  /** Shown in the header's right-hand endpoint badge. Passed down from the page. */
  endpointHint?: string;
}

export default function AppLayout({ children, endpointHint }: LoyaltyLayoutProps) {
  return (
    <>
      <style>{LOYALTY_STYLES}</style>

      <div className="lh-root">
        {/* Sticky header */}
        <header className="lh-header">
          <div className="lh-header-inner">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-lg bg-linear-to-br from-violet-500 to-cyan-500 flex items-center justify-center text-sm font-bold shadow-lg shadow-violet-500/30">
                ✦
              </div>
              <span className="font-display font-bold text-lg tracking-tight">
                Loyalty<span className="text-violet-400">Hub</span>
              </span>
            </div>

            {endpointHint && (
              <div className="text-xs text-white/30 font-mono tracking-widest uppercase">
                {endpointHint}
              </div>
            )}
          </div>
        </header>

        {/* Page content */}
        <main className="lh-main">
          {children}
        </main>
      </div>
    </>
  );
}
