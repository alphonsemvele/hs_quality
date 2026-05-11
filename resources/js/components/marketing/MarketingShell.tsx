import { Link } from '@inertiajs/react';
import { type ReactNode, useEffect, useState } from 'react';

// Shared global styles — keyframes, gradient text, card hovers, etc.
// All marketing pages share this stylesheet so visual identity stays consistent.
export function MarketingStyles() {
    return (
        <style>{`
            html { scroll-behavior: smooth; }

            @keyframes fadeIn { from { opacity:0; transform:translateY(20px) } to { opacity:1; transform:translateY(0) } }
            @keyframes meshMove { 0%,100% { transform:translate(0,0) scale(1) } 50% { transform:translate(30px,-20px) scale(1.05) } }
            @keyframes meshMove2 { 0%,100% { transform:translate(0,0) scale(1) } 50% { transform:translate(-20px,30px) scale(1.08) } }
            @keyframes pulse { 0%,100% { opacity:1 } 50% { opacity:.4 } }
            @keyframes marquee { from { transform:translateX(0) } to { transform:translateX(-50%) } }
            @keyframes float { 0%,100% { transform:translateY(0) } 50% { transform:translateY(-8px) } }
            @keyframes glowPulse { 0%,100% { box-shadow:0 0 20px 0 rgba(21,101,172,0.15) } 50% { box-shadow:0 0 48px 12px rgba(21,101,172,0.28) } }
            @keyframes textGradientMove { 0% { background-position:0% 50% } 50% { background-position:100% 50% } 100% { background-position:0% 50% } }

            .fade-in { animation: fadeIn .8s cubic-bezier(.22,1,.36,1) both; }
            .delay-1 { animation-delay: .1s; }
            .delay-2 { animation-delay: .2s; }
            .delay-3 { animation-delay: .35s; }
            .delay-4 { animation-delay: .5s; }
            .delay-5 { animation-delay: .65s; }

            .gradient-text {
                background: linear-gradient(135deg, #5996cd 0%, #62ab80 50%, #5996cd 100%);
                background-size: 200% auto;
                background-clip: text;
                -webkit-background-clip: text;
                color: transparent;
                animation: textGradientMove 6s ease-in-out infinite;
            }
            .gradient-text-light {
                background: linear-gradient(135deg, #93bcdf 0%, #95c9a9 50%, #93bcdf 100%);
                background-size: 200% auto;
                background-clip: text;
                -webkit-background-clip: text;
                color: transparent;
                animation: textGradientMove 6s ease-in-out infinite;
            }

            .card-hover {
                transition: transform .35s cubic-bezier(.16,1,.3,1), box-shadow .35s, border-color .35s;
            }
            .card-hover:hover {
                transform: translateY(-3px);
                box-shadow: 0 24px 48px -12px rgba(15,23,42,0.1);
                border-color: rgba(21,101,172,0.2);
            }

            .glow-hover { position: relative; overflow: hidden; }
            .glow-hover::before {
                content: '';
                position: absolute; inset: 0; opacity: 0;
                background: radial-gradient(600px circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(21,101,172,0.06), transparent 60%);
                transition: opacity 0.4s; pointer-events: none; z-index: 1;
            }
            .glow-hover:hover::before { opacity: 1; }

            .btn-glow { position: relative; }
            .btn-glow::after {
                content: ''; position: absolute; inset: -1px; border-radius: inherit;
                background: linear-gradient(135deg, rgba(21,101,172,0.4), rgba(63,150,112,0.4));
                opacity: 0; z-index: -1; filter: blur(14px); transition: opacity 0.4s;
            }
            .btn-glow:hover::after { opacity: 1; }

            .dashboard-glow { animation: glowPulse 4s ease-in-out infinite; }

            .marquee-track { animation: marquee 40s linear infinite; }
            .marquee:hover .marquee-track { animation-play-state: paused; }

            .mono { font-family: 'JetBrains Mono', monospace; }

            .grain {
                position: absolute; inset: 0; pointer-events: none;
                background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.5'/%3E%3C/svg%3E");
                opacity: .04;
            }

            ::-webkit-scrollbar { width: 8px; }
            ::-webkit-scrollbar-track { background: #FAFAFA; }
            ::-webkit-scrollbar-thumb { background: #1565AC; border-radius: 4px; }
            ::-webkit-scrollbar-thumb:hover { background: #0F4C81; }
        `}</style>
    );
}

// Shared marketing links — single source of truth for nav + footer.
const NAV_LINKS: [string, string][] = [
    ['Fonctionnalités', '/fonctionnalites'],
    ['Tarifs', '/#pricing'],
    ['Conformité', '/conformite'],
    ['Contact', '/contact'],
];

export interface MarketingNavProps {
    /**
     * 'translucent' = transparent au top, solidifie au scroll (landing).
     * 'solid'       = blanc opaque dès le top (autres pages).
     */
    mode?: 'translucent' | 'solid';
}

export function MarketingNav({ mode = 'solid' }: MarketingNavProps) {
    const [scrolled, setScrolled] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);

    useEffect(() => {
        if (mode !== 'translucent') return;
        const fn = () => setScrolled(window.scrollY > 32);
        window.addEventListener('scroll', fn, { passive: true });
        return () => window.removeEventListener('scroll', fn);
    }, [mode]);

    const solid = mode === 'solid' || scrolled;

    return (
        <nav
            className={
                'fixed inset-x-0 top-0 z-50 transition-all duration-300 ' +
                (solid ? 'border-b border-ink-200/60 bg-white/80 backdrop-blur-xl shadow-sm' : 'bg-transparent')
            }
        >
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-5 sm:px-8">
                <Link href="/" className="flex items-center gap-2.5">
                    <div className="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-base font-semibold italic text-white">
                        Q
                    </div>
                    <div className="leading-none">
                        <div className={`text-[15px] font-semibold tracking-tight ${solid ? 'text-ink-900' : 'text-white'}`}>
                            HS Quality
                        </div>
                        <div className={`mt-0.5 text-[10px] font-semibold uppercase tracking-widest ${solid ? 'text-brand-600' : 'text-brand-300'}`}>
                            Qualité &amp; QVCT
                        </div>
                    </div>
                </Link>

                <div className="hidden items-center gap-1 lg:flex">
                    {NAV_LINKS.map(([label, href]) => {
                        const isInternal = href.startsWith('/') && !href.startsWith('/#');
                        const className = `rounded-full px-4 py-1.5 text-[13px] font-medium transition-all ${
                            solid ? 'text-ink-600 hover:bg-ink-50 hover:text-ink-900' : 'text-white/70 hover:bg-white/10 hover:text-white'
                        }`;
                        return isInternal ? (
                            <Link key={href} href={href} className={className}>
                                {label}
                            </Link>
                        ) : (
                            <a key={href} href={href} className={className}>
                                {label}
                            </a>
                        );
                    })}
                </div>

                <div className="hidden items-center gap-2 lg:flex">
                    <Link
                        href="/login"
                        className={`rounded-full px-4 py-2 text-sm font-medium transition-colors ${
                            solid ? 'text-ink-600 hover:text-ink-900' : 'text-white/70 hover:text-white'
                        }`}
                    >
                        Connexion
                    </Link>
                    <Link
                        href="/contact"
                        className={`rounded-full px-5 py-2 text-sm font-semibold transition-all hover:-translate-y-0.5 hover:shadow-lg ${
                            solid ? 'bg-brand-600 text-white hover:bg-brand-700' : 'bg-white text-ink-900 hover:bg-ink-50'
                        }`}
                    >
                        Démarrer le pilote 3 mois
                    </Link>
                </div>

                <button
                    type="button"
                    onClick={() => setMobileOpen(!mobileOpen)}
                    className={`rounded-full p-2 lg:hidden ${solid ? 'text-ink-700 hover:bg-ink-50' : 'text-white hover:bg-white/10'}`}
                    aria-label="Menu"
                >
                    <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        {mobileOpen ? (
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        ) : (
                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        )}
                    </svg>
                </button>
            </div>

            {/* Mobile overlay */}
            <div
                className={`fixed inset-0 z-40 bg-ink-900/60 backdrop-blur-sm transition-opacity duration-300 lg:hidden ${
                    mobileOpen ? 'opacity-100' : 'pointer-events-none opacity-0'
                }`}
                onClick={() => setMobileOpen(false)}
                aria-hidden="true"
            />

            {/* Mobile drawer */}
            <div
                className={`fixed inset-y-0 right-0 z-50 w-[280px] transform transition-transform duration-300 ease-out lg:hidden ${
                    mobileOpen ? 'translate-x-0' : 'translate-x-full'
                } ${solid ? 'bg-white' : 'bg-ink-900'}`}
            >
                <div
                    className="flex items-center justify-between border-b px-5 py-4"
                    style={{ borderColor: solid ? 'rgb(241 245 249)' : 'rgba(255,255,255,0.1)' }}
                >
                    <span className={`text-[15px] font-semibold ${solid ? 'text-ink-900' : 'text-white'}`}>Menu</span>
                    <button
                        type="button"
                        onClick={() => setMobileOpen(false)}
                        className={`rounded-full p-1.5 ${solid ? 'text-ink-500 hover:bg-ink-50' : 'text-white/70 hover:bg-white/10'}`}
                        aria-label="Fermer le menu"
                    >
                        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div className="px-5 py-4">
                    {NAV_LINKS.map(([label, href]) => {
                        const isInternal = href.startsWith('/') && !href.startsWith('/#');
                        const className = `block rounded-lg px-3 py-3 text-[15px] font-medium transition-colors ${
                            solid ? 'text-ink-700 hover:bg-ink-50' : 'text-white/85 hover:bg-white/10'
                        }`;
                        return isInternal ? (
                            <Link key={href} href={href} onClick={() => setMobileOpen(false)} className={className}>
                                {label}
                            </Link>
                        ) : (
                            <a key={href} href={href} onClick={() => setMobileOpen(false)} className={className}>
                                {label}
                            </a>
                        );
                    })}
                    <div className="mt-5 flex flex-col gap-2.5">
                        <Link
                            href="/login"
                            className={`rounded-full border px-4 py-3 text-center text-sm font-medium transition-colors ${
                                solid ? 'border-ink-200 text-ink-700 hover:bg-ink-50' : 'border-white/15 text-white/85 hover:bg-white/10'
                            }`}
                        >
                            Connexion
                        </Link>
                        <Link
                            href="/contact"
                            onClick={() => setMobileOpen(false)}
                            className="rounded-full bg-brand-600 px-4 py-3 text-center text-sm font-semibold text-white transition-colors hover:bg-brand-700"
                        >
                            Démarrer le pilote 3 mois
                        </Link>
                    </div>
                </div>
            </div>
        </nav>
    );
}

export function MarketingFooter() {
    const cols: { h: string; links: { label: string; href: string }[] }[] = [
        {
            h: 'Plateforme',
            links: [
                { label: 'Fonctionnalités', href: '/fonctionnalites' },
                { label: 'Tarifs', href: '/#pricing' },
                { label: 'Conformité', href: '/conformite' },
                { label: 'Contact', href: '/contact' },
            ],
        },
        {
            h: 'Conformité',
            links: [
                { label: 'HAS', href: '/conformite#has' },
                { label: 'AFNOR NF X50-056', href: '/conformite#afnor' },
                { label: 'ISO 9001', href: '/conformite#iso' },
                { label: 'Caphandeo', href: '/conformite#caphandeo' },
            ],
        },
        {
            h: 'Légal',
            links: [
                { label: 'Mentions légales', href: '/mentions-legales' },
                { label: 'Confidentialité', href: '/confidentialite' },
                { label: 'CGU', href: '/cgu' },
                { label: 'Accessibilité', href: '/accessibilite' },
            ],
        },
    ];

    return (
        <footer className="border-t border-ink-100 bg-white px-5 py-14 sm:px-8">
            <div className="mx-auto max-w-7xl">
                <div className="grid grid-cols-2 gap-8 border-b border-ink-100 pb-10 lg:grid-cols-5">
                    <div className="col-span-2">
                        <Link href="/" className="flex items-center gap-2.5">
                            <div className="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-semibold italic text-white">
                                Q
                            </div>
                            <span className="text-lg font-semibold tracking-tight text-ink-900">HS Quality</span>
                        </Link>
                        <p className="mt-4 max-w-xs text-sm font-light leading-relaxed text-ink-600">
                            Pilotage qualité et QVCT pour les structures médico-sociales d'aide et de soins à domicile.
                        </p>
                        <div className="mt-5 flex flex-wrap gap-1.5">
                            {['RGPD', 'HDS', 'HAS', 'ISO 9001', 'AFNOR'].map((b) => (
                                <span key={b} className="rounded-md border border-ink-200 px-2 py-0.5 text-[11px] font-semibold text-ink-500">
                                    {b}
                                </span>
                            ))}
                        </div>
                    </div>

                    {cols.map((col) => (
                        <div key={col.h}>
                            <h5 className="text-[13px] font-semibold text-ink-900">{col.h}</h5>
                            <ul className="mt-4 space-y-2.5">
                                {col.links.map((l) => {
                                    const isInternal = l.href.startsWith('/') && !l.href.startsWith('/#');
                                    const className = 'text-sm text-ink-500 transition-colors hover:text-brand-600';
                                    return (
                                        <li key={l.href}>
                                            {isInternal ? (
                                                <Link href={l.href} className={className}>
                                                    {l.label}
                                                </Link>
                                            ) : (
                                                <a href={l.href} className={className}>
                                                    {l.label}
                                                </a>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3 pt-6 text-xs text-ink-400">
                    <p>
                        &copy; {new Date().getFullYear()} HS Quality &middot; CDC-QUALITE-DOM-2024-v2.0
                    </p>
                    <div className="flex gap-5">
                        <Link href="/confidentialite" className="transition-colors hover:text-ink-700">
                            Confidentialité
                        </Link>
                        <Link href="/cgu" className="transition-colors hover:text-ink-700">
                            CGU
                        </Link>
                        <Link href="/mentions-legales" className="transition-colors hover:text-ink-700">
                            Mentions légales
                        </Link>
                        <Link href="/accessibilite" className="transition-colors hover:text-ink-700">
                            Accessibilité
                        </Link>
                    </div>
                </div>
            </div>
        </footer>
    );
}

/**
 * Simple page wrapper for non-landing marketing pages. Includes the global
 * styles, solid nav, the page content, and the footer in a single shell.
 */
export function MarketingPage({ children }: { children: ReactNode }) {
    return (
        <>
            <MarketingStyles />
            <div className="min-h-screen bg-white">
                <a
                    href="#main-content"
                    className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-brand-600 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white"
                >
                    Aller au contenu principal
                </a>
                <MarketingNav mode="solid" />
                <main id="main-content" className="pt-20">
                    {children}
                </main>
                <MarketingFooter />
            </div>
        </>
    );
}
