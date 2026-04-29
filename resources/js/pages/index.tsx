import { Head, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

type Faq = { q: string; a: string };

const PHOTO_HERO =
    'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1900&auto=format&fit=crop&q=85';
const PHOTO_JOURNEY =
    'https://images.unsplash.com/photo-1581579186913-45ac3e6efe93?w=1400&auto=format&fit=crop&q=85';
const PHOTO_TEAM =
    'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=1400&auto=format&fit=crop&q=85';

export default function Welcome() {
    const [navSolid, setNavSolid] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [faqOpen, setFaqOpen] = useState<number | null>(0);
    const [scrollY, setScrollY] = useState(0);

    useEffect(() => {
        const fn = () => {
            const y = window.scrollY;
            setScrollY(y);
            setNavSolid(y > 32);
        };
        window.addEventListener('scroll', fn, { passive: true });
        return () => window.removeEventListener('scroll', fn);
    }, []);

    return (
        <>
            <Head title="HS Quality — Pilotez la qualité de votre service à domicile" />

            <style>{`
                html { scroll-behavior: smooth; }

                /* ── Animations ── */
                @keyframes fadeUp     { from { opacity:0; transform: translateY(28px) } to { opacity:1; transform: translateY(0) } }
                @keyframes slideLeft  { from { opacity:0; transform: translateX(36px) } to { opacity:1; transform: translateX(0) } }
                @keyframes slideRight { from { opacity:0; transform: translateX(-36px) } to { opacity:1; transform: translateX(0) } }
                @keyframes pulseDot   { 0%,100% { opacity:1 } 50% { opacity:.4 } }
                @keyframes glowPulse  { 0%,100% { box-shadow: 0 0 0 0 rgba(21,101,172,.35) } 50% { box-shadow: 0 0 0 14px rgba(21,101,172,0) } }
                @keyframes float      { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-10px) } }
                @keyframes marquee    { from { transform: translateX(0) } to { transform: translateX(-50%) } }
                @keyframes spinSlow   { from { transform: rotate(0) } to { transform: rotate(360deg) } }
                @keyframes brandShine { 0% { background-position: 0% 50% } 50% { background-position: 100% 50% } 100% { background-position: 0% 50% } }

                .fade-up      { animation: fadeUp .9s cubic-bezier(.16,1,.3,1) both; }
                .slide-left   { animation: slideLeft .9s cubic-bezier(.16,1,.3,1) both; }
                .slide-right  { animation: slideRight .9s cubic-bezier(.16,1,.3,1) both; }
                .au-1 { animation-delay: .12s; }
                .au-2 { animation-delay: .24s; }
                .au-3 { animation-delay: .36s; }
                .au-4 { animation-delay: .48s; }
                .au-5 { animation-delay: .60s; }
                .float        { animation: float 5s ease-in-out infinite; }
                .glow-cta     { animation: glowPulse 3s ease-out infinite; }
                .spin-slow    { animation: spinSlow 30s linear infinite; }

                /* Brand-sage gradient text with shimmer (replaces gold) */
                .accent-text {
                    background: linear-gradient(110deg, #1565AC 0%, #3F9670 35%, #1565AC 70%, #3F9670 100%);
                    background-size: 250% 100%;
                    background-clip: text;
                    -webkit-background-clip: text;
                    color: transparent;
                    animation: brandShine 8s ease-in-out infinite;
                    font-style: italic;
                    font-weight: 600;
                }

                /* Subtle grain overlay */
                .grain {
                    position: absolute; inset: 0; pointer-events: none;
                    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.5'/%3E%3C/svg%3E");
                    opacity: .04;
                }

                /* Marquee */
                .marquee-track  { animation: marquee 40s linear infinite; }
                .marquee:hover .marquee-track { animation-play-state: paused; }

                /* Reveal-on-scroll */
                @supports (animation-timeline: view()) {
                    .reveal {
                        animation: fadeUp 1s cubic-bezier(.16,1,.3,1) both;
                        animation-timeline: view();
                        animation-range: entry 0% cover 30%;
                    }
                }

                /* Card hover */
                .lift { transition: transform .35s cubic-bezier(.16,1,.3,1), box-shadow .35s; }
                .lift:hover { transform: translateY(-4px); box-shadow: 0 24px 60px rgba(15,23,42,.10); }
                .img-zoom { transition: transform 1.4s cubic-bezier(.16,1,.3,1); }
                .group:hover .img-zoom { transform: scale(1.06); }

                /* Display heading: Poppins light + tight tracking, italique sur les accents */
                .h-display {
                    font-family: 'Poppins', sans-serif;
                    font-weight: 300;
                    letter-spacing: -0.025em;
                    line-height: 1.04;
                }
                .h-display em {
                    font-style: italic;
                    font-weight: 600;
                }

                ::-webkit-scrollbar { width: 8px; }
                ::-webkit-scrollbar-track { background: #FAFAFA; }
                ::-webkit-scrollbar-thumb { background: #1565AC; border-radius: 4px; }
                ::-webkit-scrollbar-thumb:hover { background: #0F4C81; }

                body { font-family: 'Poppins', sans-serif; color: #0F172A; }
                .mono { font-family: 'JetBrains Mono', monospace; }
            `}</style>

            <div className="min-h-screen bg-white">
                <Nav solid={navSolid} mobileOpen={mobileOpen} setMobileOpen={setMobileOpen} />
                <Hero scrollY={scrollY} />
                <TrustBar />
                <Intro />
                <HowItWorks />
                <Coverage />
                <Lifetime />
                <DecisionsSection />
                <Testimonial />
                <BeginJourney />
                <FaqSection open={faqOpen} setOpen={setFaqOpen} />
                <FinalCta />
                <Footer />
            </div>
        </>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  NAV
// ════════════════════════════════════════════════════════════════════════════

function Nav({
    solid,
    mobileOpen,
    setMobileOpen,
}: {
    solid: boolean;
    mobileOpen: boolean;
    setMobileOpen: (v: boolean) => void;
}) {
    const links: [string, string][] = [
        ['Accueil', '#hero'],
        ['Modules', '#modules'],
        ['Approche', '#approach'],
        ['Tarifs', '#pricing'],
        ['À propos', '#about'],
    ];
    return (
        <nav
            className={
                'fixed inset-x-0 top-0 z-50 transition-all duration-300 ' +
                (solid ? 'border-b border-white/5 bg-ink-900/95 backdrop-blur' : 'bg-transparent')
            }
        >
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-5 sm:px-8">
                <Link href="/" className="flex items-center gap-2.5">
                    <div className="flex size-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-base font-semibold italic text-white">
                        Q
                    </div>
                    <div className="leading-none">
                        <div className="text-[15px] font-semibold tracking-tight text-white">HS Quality</div>
                        <div className="mt-0.5 text-[10px] font-semibold uppercase tracking-widest text-brand-300">
                            Qualité & QVCT
                        </div>
                    </div>
                </Link>

                <div className="hidden items-center gap-1 rounded-full border border-white/10 bg-white/5 p-1 backdrop-blur lg:flex">
                    {links.map(([label, href], i) => (
                        <a
                            key={href}
                            href={href}
                            className={
                                'rounded-full px-4 py-1.5 text-[13px] font-medium transition-all ' +
                                (i === 0
                                    ? 'bg-brand-600 text-white'
                                    : 'text-white/70 hover:bg-white/10 hover:text-white')
                            }
                        >
                            {label}
                        </a>
                    ))}
                </div>

                <div className="hidden items-center gap-2 lg:flex">
                    <Link
                        href="/login"
                        className="rounded-full px-4 py-2 text-sm font-medium text-white/70 transition-colors hover:text-white"
                    >
                        Connexion
                    </Link>
                    <a
                        href="#cta"
                        className="rounded-full bg-white px-5 py-2 text-sm font-semibold text-ink-900 transition-all hover:-translate-y-0.5 hover:bg-ink-50 hover:shadow-lg"
                    >
                        Démarrer gratuit
                    </a>
                </div>

                <button
                    type="button"
                    onClick={() => setMobileOpen(!mobileOpen)}
                    className="rounded-full p-2 text-white hover:bg-white/10 lg:hidden"
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

            {mobileOpen && (
                <div className="border-t border-white/10 bg-ink-900 px-5 pb-5 pt-3 lg:hidden">
                    {links.map(([label, href]) => (
                        <a
                            key={href}
                            href={href}
                            onClick={() => setMobileOpen(false)}
                            className="block py-2.5 text-[15px] font-medium text-white/85"
                        >
                            {label}
                        </a>
                    ))}
                    <div className="mt-3 flex gap-2">
                        <Link
                            href="/login"
                            className="flex-1 rounded-full border border-white/15 px-4 py-2.5 text-center text-sm font-medium text-white/85"
                        >
                            Connexion
                        </Link>
                        <a
                            href="#cta"
                            className="flex-1 rounded-full bg-white px-4 py-2.5 text-center text-sm font-semibold text-ink-900"
                        >
                            Démarrer
                        </a>
                    </div>
                </div>
            )}
        </nav>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  HERO
// ════════════════════════════════════════════════════════════════════════════

function Hero({ scrollY }: { scrollY: number }) {
    return (
        <section id="hero" className="relative min-h-screen overflow-hidden bg-ink-900 text-white">
            <div
                className="absolute inset-0 -z-10"
                style={{
                    transform: `translateY(${scrollY * 0.25}px) scale(${1 + scrollY * 0.0001})`,
                    transition: 'transform 0.05s linear',
                }}
            >
                <div className="size-full bg-cover bg-center" style={{ backgroundImage: `url(${PHOTO_HERO})` }} />
            </div>

            <div
                aria-hidden
                className="absolute inset-0"
                style={{
                    background:
                        'linear-gradient(110deg, rgba(15,23,42,0.93) 0%, rgba(15,23,42,0.78) 45%, rgba(15,23,42,0.5) 100%)',
                }}
            />
            <div className="grain" aria-hidden />

            <div
                aria-hidden
                className="pointer-events-none absolute -right-32 -top-40 size-[700px] rounded-full"
                style={{ background: 'radial-gradient(closest-side, rgba(21,101,172,.30), transparent 70%)' }}
            />
            <div
                aria-hidden
                className="pointer-events-none absolute -left-32 bottom-[-30%] size-[600px] rounded-full"
                style={{ background: 'radial-gradient(closest-side, rgba(63,150,112,.22), transparent 70%)' }}
            />

            <div className="relative mx-auto max-w-7xl px-5 pb-32 pt-32 sm:px-8 sm:pt-44">
                <div className="fade-up au-4 mb-12 flex flex-wrap justify-end gap-10 lg:absolute lg:right-8 lg:top-32 lg:mb-0">
                    <Stat n="10+" label="années d'expertise" />
                    <Stat n="50+" label="structures pilotes" />
                </div>

                <div className="max-w-4xl">
                    <span className="fade-up inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-[12px] font-medium text-white/85 backdrop-blur">
                        <span
                            className="size-1.5 rounded-full bg-brand-300"
                            style={{ animation: 'pulseDot 2s infinite' }}
                        />
                        Bâtir une démarche qualité durable
                    </span>

                    <h1 className="h-display fade-up au-1 mt-7 text-5xl text-white sm:text-6xl lg:text-[80px]">
                        Pilotez la <em className="accent-text">qualité</em>
                        <br />
                        de votre service
                        <br />
                        <em className="text-white/70">à domicile.</em>
                    </h1>

                    <p className="fade-up au-2 mt-8 max-w-xl text-base font-light leading-relaxed text-white/75 sm:text-lg">
                        HS Quality structure le pilotage qualité et QVCT des structures médico-sociales —
                        interventions, incidents, audits, formations. Du terrain à la direction, en temps réel,
                        en conformité <span className="font-medium text-white">HAS / RGPD / HDS</span>.
                    </p>

                    <div className="fade-up au-3 mt-10 flex flex-wrap gap-3">
                        <a
                            href="#cta"
                            className="glow-cta inline-flex items-center gap-2 rounded-full bg-brand-600 px-7 py-3.5 text-sm font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-2xl"
                        >
                            Démarrer gratuit
                            <Arrow />
                        </a>
                        <a
                            href="#approach"
                            className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur transition-all hover:border-white/35 hover:bg-white/10"
                        >
                            En savoir plus
                        </a>
                    </div>
                </div>

                <div className="fade-up au-5 mt-20 flex items-center gap-3 text-xs text-white/50">
                    <span className="block h-px w-10 bg-white/30" />
                    Faites défiler
                </div>
            </div>
        </section>
    );
}

function Stat({ n, label }: { n: string; label: string }) {
    return (
        <div className="text-right">
            <div className="text-3xl font-light text-white sm:text-4xl">{n}</div>
            <div className="mt-1 text-[11px] uppercase tracking-widest text-white/55">{label}</div>
        </div>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  TRUST BAR
// ════════════════════════════════════════════════════════════════════════════

function TrustBar() {
    const items = [
        'HAS · Évaluation externe',
        'AFNOR NF X50-056',
        'ISO 9001',
        'Caphandeo',
        'RGPD',
        'HDS France',
        'Code du travail (RPS)',
        'CNIL',
        'Code de la santé publique',
    ];
    return (
        <div className="marquee relative overflow-hidden border-y border-ink-100 bg-ink-50/60 py-5">
            <div className="marquee-track flex gap-12 whitespace-nowrap">
                {[...items, ...items].map((it, i) => (
                    <span
                        key={i}
                        className="flex shrink-0 items-center gap-3 text-xs font-medium uppercase tracking-widest text-ink-500"
                    >
                        <span className="size-1 rounded-full bg-brand-500" />
                        {it}
                    </span>
                ))}
            </div>
            <div className="pointer-events-none absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-ink-50 to-transparent" />
            <div className="pointer-events-none absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-ink-50 to-transparent" />
        </div>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  INTRO
// ════════════════════════════════════════════════════════════════════════════

function Intro() {
    return (
        <section className="relative bg-white px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto grid max-w-7xl grid-cols-1 items-center gap-16 lg:grid-cols-3">
                <div className="reveal slide-right">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Notre approche
                    </span>
                    <p className="mt-6 max-w-sm text-sm leading-relaxed text-ink-600">
                        Résolvez vos enjeux qualité avec des outils éprouvés —{' '}
                        <span className="font-medium text-ink-900">déployés en deux semaines</span>, sans
                        bouleverser vos équipes.
                    </p>
                    <a
                        href="#approach"
                        className="mt-8 inline-flex items-center gap-2 rounded-full bg-ink-900 px-6 py-3 text-sm font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-ink-800 hover:shadow-lg"
                    >
                        Découvrir
                        <Arrow />
                    </a>
                </div>

                <div className="reveal text-center lg:col-span-2">
                    <span className="inline-block rounded-full bg-sage-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-sage-700">
                        Métriques
                    </span>
                    <h2 className="h-display mt-6 text-3xl sm:text-5xl">
                        Transformer la qualité du soin pour
                        <br />
                        <em className="accent-text">un avenir plus serein.</em>
                    </h2>
                    <p className="mx-auto mt-5 max-w-xl text-base font-light leading-relaxed text-ink-600">
                        Un outil pensé pour les coordinateurs, dirigeants et référents qualité — qui mesure ce qui
                        compte vraiment et accompagne la décision au quotidien.
                    </p>

                    <div className="float mt-12">
                        <DashboardMockup />
                    </div>
                </div>
            </div>
        </section>
    );
}

function DashboardMockup() {
    return (
        <div className="mx-auto max-w-2xl">
            <div
                className="relative overflow-hidden rounded-3xl p-6 shadow-2xl ring-1 ring-brand-500/20 sm:p-8"
                style={{
                    background:
                        'linear-gradient(135deg, #082443 0%, #0B385E 60%, #0F4C81 100%)',
                }}
            >
                <div className="grain" aria-hidden />
                <div className="relative grid grid-cols-2 gap-4">
                    <div className="rounded-2xl bg-white/[0.06] p-5 backdrop-blur ring-1 ring-white/10">
                        <p className="text-xs font-medium text-white/70">Score qualité</p>
                        <div className="mt-2 flex items-baseline gap-1">
                            <span className="mono text-4xl font-semibold text-white">87</span>
                            <span className="text-xs font-medium text-brand-200">/100</span>
                        </div>
                        <p className="mt-1 text-[11px] text-sage-300">↑ +6 vs trimestre</p>
                        <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-white/10">
                            <div className="h-full w-[87%] rounded-full bg-gradient-to-r from-brand-400 to-sage-400" />
                        </div>
                    </div>
                    <div className="rounded-2xl bg-white/[0.06] p-5 backdrop-blur ring-1 ring-white/10">
                        <p className="text-xs font-medium text-white/70">Interventions</p>
                        <div className="mono mt-2 text-4xl font-semibold text-white">1 248</div>
                        <p className="mt-1 text-[11px] text-white/55">ce mois-ci</p>
                        <div className="mt-3 flex items-end gap-1">
                            {[40, 55, 35, 70, 60, 85, 75].map((h, i) => (
                                <div
                                    key={i}
                                    className="flex-1 rounded-sm bg-gradient-to-t from-brand-700 to-brand-300"
                                    style={{ height: `${h}%`, minHeight: 6 }}
                                />
                            ))}
                        </div>
                    </div>
                </div>
                <div className="relative mt-4 rounded-2xl bg-white/[0.06] p-5 backdrop-blur ring-1 ring-white/10">
                    <div className="flex items-center justify-between">
                        <p className="text-xs font-medium text-white/70">Incidents récents</p>
                        <span className="rounded-full bg-rose-400/20 px-2 py-0.5 text-[10px] font-semibold text-rose-200">
                            2 graves à traiter
                        </span>
                    </div>
                    <div className="mt-3 space-y-2">
                        {[
                            { who: 'Chute · Mme D.', tag: 'Grave', tone: 'rose' },
                            { who: 'Erreur médic. · M. P.', tag: 'En analyse', tone: 'amber' },
                            { who: 'Situation danger · Mme R.', tag: 'Clos', tone: 'mute' },
                        ].map((row) => (
                            <div key={row.who} className="flex items-center justify-between text-xs">
                                <span className="text-white/85">{row.who}</span>
                                <span
                                    className={
                                        'rounded-full px-2 py-0.5 text-[10px] font-semibold ' +
                                        (row.tone === 'rose'
                                            ? 'bg-rose-400/20 text-rose-200'
                                            : row.tone === 'amber'
                                              ? 'bg-amber-400/20 text-amber-200'
                                              : 'bg-white/10 text-white/55')
                                    }
                                >
                                    {row.tag}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  HOW IT WORKS
// ════════════════════════════════════════════════════════════════════════════

function HowItWorks() {
    return (
        <section id="approach" className="bg-ink-50/40 px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto max-w-7xl">
                <div className="grid grid-cols-1 items-end gap-8 lg:grid-cols-2 lg:gap-16">
                    <div className="reveal">
                        <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                            Notre méthode
                        </span>
                        <h2 className="h-display mt-5 text-4xl sm:text-5xl">
                            Mesurer ce qui se passe
                            <br />
                            <em className="accent-text">vraiment sur le terrain.</em>
                        </h2>
                    </div>
                    <p className="reveal text-base font-light leading-relaxed text-ink-600 lg:max-w-md">
                        De la traçabilité de chaque intervention à l'analyse des incidents et des audits, HS Quality
                        donne aux équipes des outils simples, conformes, et qui s'utilisent réellement au quotidien.
                    </p>
                </div>

                <div id="modules" className="mt-14 grid grid-cols-1 gap-5 md:grid-cols-3">
                    <HowCard
                        n="1"
                        title="Tracer chaque intervention"
                        desc="Pointage géolocalisé, voice-to-text, signature électronique. Chaque visite est documentée et consultable en temps réel."
                        mock={<MockInterventions />}
                    />
                    <HowCard
                        n="2"
                        title="Analyser les incidents"
                        desc="Déclaration mobile en moins de 2 minutes, classification automatique, analyse 5-pourquoi, notification ARS si grave."
                        mock={<MockIncidents />}
                    />
                    <HowCard
                        n="3"
                        title="Piloter la conformité"
                        desc="Audits HAS / AFNOR / ISO 9001 sur tablette, scoring automatique, plan d'amélioration généré depuis les écarts."
                        mock={<MockAudit />}
                    />
                </div>
            </div>
        </section>
    );
}

function HowCard({ n, title, desc, mock }: { n: string; title: string; desc: string; mock: React.ReactNode }) {
    return (
        <article className="lift group flex flex-col overflow-hidden rounded-3xl border border-ink-100 bg-white p-6">
            <div className="flex items-start justify-between">
                <h3 className="text-base font-semibold text-ink-900">{title}</h3>
                <span className="mono text-[11px] text-ink-400">[{n}]</span>
            </div>
            <p className="mt-3 text-sm font-light leading-relaxed text-ink-600">{desc}</p>
            <div className="mt-6 -mx-1 -mb-1 overflow-hidden rounded-2xl">{mock}</div>
        </article>
    );
}

function MockInterventions() {
    return (
        <div
            className="relative overflow-hidden rounded-2xl p-5 ring-1 ring-brand-500/20"
            style={{ background: 'linear-gradient(135deg, #082443, #0F4C81)' }}
        >
            <div className="grain" aria-hidden />
            <div className="relative">
                <div className="mono text-5xl font-semibold tracking-tight text-white">94%</div>
                <p className="mt-1 text-xs text-brand-200">Interventions tracées</p>
                <div className="mt-5 space-y-1.5 text-[11px]">
                    {[
                        ['Lun', 'Mme Dupont', 95],
                        ['Mar', 'M. Bernard', 70],
                        ['Mer', 'Mme Léon', 100],
                    ].map(([d, n, p]) => (
                        <div key={String(d)} className="flex items-center gap-2">
                            <span className="w-8 text-white/45">{d}</span>
                            <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-white/10">
                                <div
                                    className="h-full rounded-full bg-gradient-to-r from-brand-500 to-sage-400"
                                    style={{ width: `${p}%` }}
                                />
                            </div>
                            <span className="mono w-8 text-right text-white/70">{p}%</span>
                            <span className="hidden flex-1 truncate text-white/55 sm:block">{n}</span>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

function MockIncidents() {
    return (
        <div
            className="relative overflow-hidden rounded-2xl p-5 ring-1 ring-brand-500/20"
            style={{ background: 'linear-gradient(135deg, #0F4C81, #082443)' }}
        >
            <div className="grain" aria-hidden />
            <div className="relative">
                <p className="text-[11px] uppercase tracking-widest text-brand-200">Données incidents</p>
                <div className="mt-3 space-y-2">
                    {[
                        ['Déclarés ce mois', '12'],
                        ['Délai moyen', '3,2 j'],
                        ['Critiques', '0'],
                    ].map(([k, v]) => (
                        <div
                            key={k}
                            className="flex items-center justify-between rounded-lg bg-white/[0.06] px-3 py-1.5 ring-1 ring-white/10"
                        >
                            <span className="text-xs text-white/70">{k}</span>
                            <span className="mono text-xs font-semibold text-white">{v}</span>
                        </div>
                    ))}
                </div>
                <div className="mt-3 rounded-lg bg-sage-400/15 px-3 py-2 text-xs text-sage-200 ring-1 ring-sage-400/20">
                    ✓ 100% des graves notifiés ARS sous 24h
                </div>
            </div>
        </div>
    );
}

function MockAudit() {
    return (
        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-sage-50 to-white p-5 ring-1 ring-sage-100">
            <div className="flex items-center justify-between">
                <p className="text-[11px] uppercase tracking-widest text-ink-500">Audit HAS · 2026</p>
                <span className="rounded-full bg-sage-100 px-2 py-0.5 text-[10px] font-semibold text-sage-700">
                    Finalisé
                </span>
            </div>
            <div className="mt-4 flex items-baseline gap-1">
                <span className="mono text-5xl font-semibold tracking-tight text-ink-900">87</span>
                <span className="text-sm text-ink-500">/100</span>
            </div>
            <div className="mt-4 space-y-1.5">
                {[
                    ['Bientraitance', 92],
                    ['Coordination', 84],
                    ['Traçabilité', 88],
                ].map(([k, p]) => (
                    <div key={String(k)}>
                        <div className="flex justify-between text-[11px] text-ink-600">
                            <span>{k}</span>
                            <span className="mono">{p}%</span>
                        </div>
                        <div className="mt-0.5 h-1 overflow-hidden rounded-full bg-ink-100">
                            <div
                                className="h-full rounded-full bg-gradient-to-r from-sage-500 to-brand-500"
                                style={{ width: `${p}%` }}
                            />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  COVERAGE
// ════════════════════════════════════════════════════════════════════════════

function Coverage() {
    const types = [
        'SAAD',
        'SSIAD',
        'SPASAD',
        'ESAD',
        'CCAS',
        'Mandataires',
        'EHPAD',
        'Foyers de vie',
        'CSI',
        'ITEP',
    ];
    return (
        <section className="bg-white px-5 py-20 sm:px-8 sm:py-24">
            <div className="mx-auto max-w-5xl text-center">
                <h2 className="h-display text-3xl sm:text-4xl">
                    Pensé pour toutes les structures,
                    <br />
                    <em className="accent-text">avec l'aide de nos référents qualité.</em>
                </h2>
                <p className="mx-auto mt-5 max-w-xl text-base font-light text-ink-600">
                    Nos référents qualité accompagnent chaque type de structure médico-sociale dans la mise en
                    place.
                </p>

                <div className="mt-10 flex flex-wrap items-center justify-center gap-2.5">
                    {types.map((t, i) => (
                        <span
                            key={t}
                            className={
                                'rounded-full border px-5 py-2 text-sm font-medium transition-all hover:-translate-y-0.5 hover:scale-105 ' +
                                (i % 4 === 1
                                    ? 'border-ink-200 bg-white text-ink-900 shadow-sm'
                                    : i % 4 === 2
                                      ? 'border-sage-200 bg-sage-50 text-sage-800'
                                      : 'border-ink-100 bg-ink-50 text-ink-500')
                            }
                        >
                            {t}
                        </span>
                    ))}
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  LIFETIME
// ════════════════════════════════════════════════════════════════════════════

function Lifetime() {
    const items = ['Interventions', 'Incidents & EI', 'Plans de soins', 'Audits HAS', 'QVCT'];
    return (
        <section className="relative overflow-hidden bg-white px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 lg:grid-cols-2">
                <ul className="reveal slide-right space-y-3.5">
                    {items.map((label, i) => (
                        <li key={label} className="flex items-center gap-3">
                            <div
                                className={
                                    'mono flex size-9 items-center justify-center rounded-xl text-xs font-semibold ' +
                                    (i === 2 ? 'bg-brand-700 text-white' : 'bg-ink-100 text-ink-600')
                                }
                            >
                                {String(i + 1).padStart(2, '0')}
                            </div>
                            <span
                                className={
                                    'text-sm font-medium ' + (i === 2 ? 'text-ink-900' : 'text-ink-500')
                                }
                            >
                                {label}
                            </span>
                        </li>
                    ))}
                </ul>

                <div className="reveal slide-left relative mx-auto aspect-square w-full max-w-md">
                    <svg className="spin-slow absolute inset-0 size-full" viewBox="0 0 360 360" aria-hidden>
                        <g stroke="currentColor" className="text-ink-300">
                            {Array.from({ length: 60 }).map((_, i) => {
                                const angle = (i * 6 * Math.PI) / 180;
                                const x1 = 180 + Math.cos(angle) * 168;
                                const y1 = 180 + Math.sin(angle) * 168;
                                const x2 = 180 + Math.cos(angle) * 178;
                                const y2 = 180 + Math.sin(angle) * 178;
                                return <line key={i} x1={x1} y1={y1} x2={x2} y2={y2} strokeWidth={1} />;
                            })}
                        </g>
                    </svg>

                    <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span className="rounded-full bg-sage-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-sage-700">
                            Suivi qualité
                        </span>
                        <h3 className="h-display mt-4 max-w-[10ch] text-3xl sm:text-4xl">
                            Un suivi qui s'inscrit dans <em className="accent-text">la durée.</em>
                        </h3>
                        <p className="mt-3 max-w-[18ch] text-sm font-light leading-relaxed text-ink-500">
                            Toutes vos données qualité conservées 10 ans en France, accessibles instantanément.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  DECISIONS — dark brand-sage
// ════════════════════════════════════════════════════════════════════════════

function DecisionsSection() {
    return (
        <section
            className="relative overflow-hidden px-5 py-24 text-white sm:px-8 sm:py-32"
            style={{
                background:
                    'linear-gradient(135deg, #082443 0%, #0B385E 50%, #0F4C81 100%)',
            }}
        >
            <div className="grain" aria-hidden />
            <div
                aria-hidden
                className="pointer-events-none absolute -right-20 top-20 size-[600px] rounded-full"
                style={{ background: 'radial-gradient(closest-side, rgba(63,150,112,.28), transparent 70%)' }}
            />

            <div className="relative mx-auto max-w-7xl text-center">
                <span className="reveal inline-block rounded-full border border-white/15 bg-white/10 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-widest text-white/85 backdrop-blur">
                    Système de décision
                </span>

                <h2 className="reveal h-display mx-auto mt-7 max-w-3xl text-4xl text-white sm:text-5xl lg:text-6xl">
                    Transformer la donnée en
                    <br />
                    <em className="accent-text">décisions de qualité.</em>
                </h2>

                <p className="reveal mx-auto mt-6 max-w-xl text-base font-light leading-relaxed text-white/70">
                    Du suivi temps réel à l'analyse longitudinale — les bonnes informations, au bon moment, pour
                    les bons acteurs.
                </p>

                <div className="mt-16 grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <DecisionCard
                        kicker="Anticipation"
                        title="Détecter avant que ça arrive."
                        desc="Analyse des tendances, signaux faibles QVCT, prédiction des risques par bénéficiaire et intervenant."
                        mock={<MockPredictive />}
                    />
                    <DecisionCard
                        kicker="Équilibre"
                        title="Mettre en lumière l'invisible."
                        desc="Cartographie des dimensions qualité, comparaison sectorielle anonymisée, vision multi-sites."
                        mock={<MockRadar />}
                    />
                    <DecisionCard
                        kicker="Continuité"
                        title="Petites actions, grands effets."
                        desc="Plan d'amélioration suivi au quotidien, rappels intelligents, célébration des progrès."
                        mock={<MockCalendar />}
                    />
                </div>
            </div>
        </section>
    );
}

function DecisionCard({
    kicker,
    title,
    desc,
    mock,
}: {
    kicker: string;
    title: string;
    desc: string;
    mock: React.ReactNode;
}) {
    return (
        <article className="lift group rounded-3xl bg-white/[0.05] p-6 text-left ring-1 ring-white/10 backdrop-blur transition-all hover:bg-white/[0.08] hover:ring-sage-400/30">
            <div className="overflow-hidden rounded-2xl">{mock}</div>
            <span className="mt-6 inline-block rounded-full bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-sage-200">
                {kicker}
            </span>
            <h3 className="mt-4 text-2xl font-light text-white">{title}</h3>
            <p className="mt-2 text-sm font-light leading-relaxed text-white/65">{desc}</p>
        </article>
    );
}

function MockPredictive() {
    return (
        <div
            className="relative aspect-[3/2] overflow-hidden p-4"
            style={{ background: 'linear-gradient(135deg, #0F4C81, #082443)' }}
        >
            <div className="absolute inset-x-4 bottom-4 flex gap-1.5">
                {['LU', 'MA', 'ME', 'JE', 'VE', 'SA'].map((d, i) => (
                    <div
                        key={d}
                        className={
                            'flex flex-1 flex-col items-center rounded-md py-2 ring-1 ring-inset transition-all ' +
                            (i === 3 ? 'bg-sage-400/20 ring-sage-400/40' : 'bg-white/5 ring-white/10')
                        }
                    >
                        <span className="text-[10px] uppercase text-white/60">{d}</span>
                        <span className="mono mt-1 text-xs font-semibold text-white">{15 + i}</span>
                    </div>
                ))}
            </div>
            <span className="absolute left-4 top-4 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-medium text-white/85 backdrop-blur">
                Prédictif
            </span>
        </div>
    );
}

function MockRadar() {
    return (
        <div
            className="relative aspect-[3/2] overflow-hidden p-4"
            style={{ background: 'linear-gradient(135deg, #0B385E, #082443)' }}
        >
            <svg viewBox="0 0 200 130" className="absolute inset-0 size-full" aria-hidden>
                <g stroke="white" strokeOpacity=".25" fill="none">
                    <polygon points="100,30 160,65 140,110 60,110 40,65" />
                    <polygon points="100,45 145,70 130,100 70,100 55,70" />
                    <polygon points="100,60 130,75 120,90 80,90 70,75" />
                </g>
                <polygon
                    points="100,40 150,68 125,105 70,100 55,70"
                    fill="rgba(63,150,112,.45)"
                    stroke="rgb(98,171,128)"
                    strokeWidth="1.5"
                />
                <g fill="white" fontSize="8" fontFamily="Poppins">
                    <text x="100" y="22" textAnchor="middle" opacity=".7">
                        Énergie
                    </text>
                    <text x="170" y="68" textAnchor="middle" opacity=".7">
                        Santé
                    </text>
                    <text x="22" y="68" textAnchor="middle" opacity=".7">
                        Stress
                    </text>
                </g>
            </svg>
            <span className="absolute right-4 top-4 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-medium text-white/85 backdrop-blur">
                Cartographie
            </span>
        </div>
    );
}

function MockCalendar() {
    return (
        <div
            className="relative aspect-[3/2] overflow-hidden p-4"
            style={{ background: 'linear-gradient(135deg, #0F4C81, #082443)' }}
        >
            <span className="absolute left-4 top-4 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-medium text-white/85 backdrop-blur">
                Janvier
            </span>
            <div className="absolute inset-x-4 bottom-4 grid grid-cols-7 gap-1">
                {Array.from({ length: 14 }).map((_, i) => (
                    <div
                        key={i}
                        className={
                            'mono aspect-square rounded-md text-[10px] ' +
                            (i === 5 || i === 9
                                ? 'bg-sage-400 text-ink-900'
                                : 'bg-white/[0.08] text-white/65 ring-1 ring-inset ring-white/10')
                        }
                    >
                        <span className="flex h-full items-center justify-center">{i + 1}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  TESTIMONIAL
// ════════════════════════════════════════════════════════════════════════════

function Testimonial() {
    return (
        <section className="bg-white px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 lg:grid-cols-2">
                <div className="reveal group relative aspect-[4/5] overflow-hidden rounded-3xl">
                    <img
                        src={PHOTO_TEAM}
                        alt="Équipe en visite à domicile"
                        className="img-zoom absolute inset-0 size-full object-cover"
                        loading="lazy"
                    />
                    <div className="absolute inset-0 bg-gradient-to-t from-ink-900/85 via-transparent to-transparent" />
                    <div className="absolute inset-x-6 bottom-6 text-white">
                        <p className="text-[11px] font-semibold uppercase tracking-widest text-sage-300">Pilote</p>
                        <p className="mt-1 text-2xl font-light italic">SAAD Horizon · Douala</p>
                    </div>
                </div>

                <div className="reveal slide-left">
                    <span className="inline-block rounded-full bg-sage-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-sage-700">
                        Témoignage
                    </span>
                    <h3 className="h-display mt-5 text-3xl sm:text-4xl">
                        <em className="accent-text">«&nbsp;</em>Nos coordinatrices déclarent les incidents en
                        moins de 2 minutes. Notre taux de conformité HAS est passé de 61% à 84% en 6 mois.
                        <em className="accent-text">&nbsp;»</em>
                    </h3>
                    <div className="mt-8 flex items-center gap-4 border-t border-ink-100 pt-6">
                        <div className="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-base font-semibold text-white">
                            MF
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-ink-900">Marie-France Essomba</p>
                            <p className="text-xs text-ink-500">Directrice qualité · SAAD Horizon</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  BEGIN JOURNEY
// ════════════════════════════════════════════════════════════════════════════

function BeginJourney() {
    return (
        <section id="about" className="bg-ink-50/40 px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto grid max-w-7xl grid-cols-1 items-stretch gap-6 lg:grid-cols-2">
                <div className="lift group relative overflow-hidden rounded-3xl">
                    <img
                        src={PHOTO_JOURNEY}
                        alt="Soin à domicile chaleureux"
                        className="img-zoom absolute inset-0 size-full object-cover"
                        loading="lazy"
                    />
                    <div
                        className="absolute inset-0"
                        style={{
                            background:
                                'linear-gradient(135deg, rgba(8,36,67,.88) 0%, rgba(8,36,67,.55) 100%)',
                        }}
                    />
                    <div className="grain" aria-hidden />
                    <div
                        aria-hidden
                        className="absolute -bottom-32 -right-20 size-[420px] rounded-full"
                        style={{ background: 'radial-gradient(closest-side, rgba(63,150,112,.28), transparent 70%)' }}
                    />
                    <div className="relative p-10 sm:p-12">
                        <h2 className="h-display text-4xl text-white sm:text-5xl">
                            Démarrez votre <em className="accent-text">démarche qualité</em>
                            <br />
                            <em className="text-white/65">en toute sérénité.</em>
                        </h2>
                        <a
                            href="#cta"
                            className="mt-10 inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-ink-900 transition-all hover:-translate-y-0.5 hover:bg-ink-50 hover:shadow-2xl"
                        >
                            Démarrer gratuit
                            <Arrow />
                        </a>
                    </div>
                </div>

                <div className="lift rounded-3xl bg-sage-50 p-10 sm:p-12">
                    <div className="text-2xl font-light tracking-tight text-ink-900">
                        HS<em className="accent-text">Q</em>uality
                    </div>
                    <p className="mt-6 max-w-md text-sm font-light leading-relaxed text-ink-700">
                        Couvrez la traçabilité, la conformité HAS, la prévention QVCT et la formation continue —
                        sur une seule plateforme, pensée pour les services à domicile.
                    </p>

                    <div className="mt-12 space-y-3 border-t border-sage-200/60 pt-8">
                        {['Conditions générales', 'Politique de confidentialité', 'Conformité HDS / RGPD'].map(
                            (label) => (
                                <a
                                    key={label}
                                    href="#"
                                    className="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm text-ink-800 transition-colors hover:bg-white/60"
                                >
                                    {label}
                                    <Arrow className="size-3.5" />
                                </a>
                            ),
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  FAQ
// ════════════════════════════════════════════════════════════════════════════

const FAQS: Faq[] = [
    {
        q: 'Que comprend exactement HS Quality ?',
        a: "Une plateforme complète : suivi des interventions terrain (mobile + web), gestion des incidents et EI, audits qualité (HAS, AFNOR, ISO 9001, Caphandeo), baromètre QVCT, gestion des compétences et plans de soins. Tout est intégré et conforme HAS / RGPD / HDS.",
    },
    {
        q: "À quelle fréquence dois-je faire des audits qualité ?",
        a: "L'évaluation externe HAS doit avoir lieu tous les 5 ans. En interne, un point trimestriel est recommandé. HS Quality vous permet de programmer ces audits, de scorer en temps réel et de générer le PAC depuis les écarts détectés.",
    },
    {
        q: 'Combien de temps pour mettre en place HS Quality ?',
        a: 'Deux semaines en moyenne. Provisioning de votre tenant, import de vos bénéficiaires et intervenants, formation des coordinateurs (2h), puis déploiement progressif. Un référent qualité vous accompagne tout au long du pilote.',
    },
    {
        q: 'Mes données sont-elles bien sécurisées ?',
        a: "Hébergement HDS en France (AWS Paris ou OVHcloud), chiffrement AES-256 au repos et TLS 1.3 en transit, MFA obligatoire pour les rôles privilégiés. Audit complet de chaque accès aux données de santé. Conformité RGPD et code de la santé publique.",
    },
    {
        q: "Puis-je essayer avant de m'engager ?",
        a: "Oui, l'essai pilote est gratuit pendant 3 mois, sans carte bancaire et avec un référent dédié. Si la solution ne correspond pas à vos besoins, vos données sont restituées ou supprimées sur simple demande.",
    },
];

function FaqSection({ open, setOpen }: { open: number | null; setOpen: (v: number | null) => void }) {
    return (
        <section className="bg-white px-5 py-24 sm:px-8 sm:py-28">
            <div className="mx-auto max-w-3xl">
                <div className="reveal text-center">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700 ring-1 ring-brand-100">
                        Questions fréquentes
                    </span>
                    <h2 className="h-display mt-6 text-4xl sm:text-5xl">
                        Tout ce que vous voulez <em className="accent-text">savoir.</em>
                    </h2>
                </div>

                <div className="mt-12 space-y-3">
                    {FAQS.map((f, i) => {
                        const isOpen = open === i;
                        return (
                            <div
                                key={f.q}
                                className={
                                    'overflow-hidden rounded-2xl bg-white ring-1 transition-all ' +
                                    (isOpen
                                        ? 'shadow-[0_8px_32px_rgba(15,23,42,.06)] ring-brand-200'
                                        : 'ring-ink-100 hover:ring-ink-200')
                                }
                            >
                                <button
                                    type="button"
                                    onClick={() => setOpen(isOpen ? null : i)}
                                    className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                                    aria-expanded={isOpen}
                                >
                                    <span className="text-base font-semibold text-ink-900">{f.q}</span>
                                    <span
                                        className={
                                            'flex size-8 shrink-0 items-center justify-center rounded-full transition-all ' +
                                            (isOpen
                                                ? 'rotate-45 bg-brand-600 text-white'
                                                : 'bg-ink-50 text-ink-600')
                                        }
                                    >
                                        <svg
                                            className="size-4"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                            viewBox="0 0 24 24"
                                        >
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
                                        </svg>
                                    </span>
                                </button>
                                <FaqAnswer open={isOpen} answer={f.a} />
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function FaqAnswer({ open, answer }: { open: boolean; answer: string }) {
    const ref = useRef<HTMLDivElement>(null);
    return (
        <div
            ref={ref}
            className="grid transition-all duration-300 ease-out"
            style={{ gridTemplateRows: open ? '1fr' : '0fr', opacity: open ? 1 : 0 }}
        >
            <div className="overflow-hidden">
                <p className="px-6 pb-6 text-sm font-light leading-relaxed text-ink-600">{answer}</p>
            </div>
        </div>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  FINAL CTA
// ════════════════════════════════════════════════════════════════════════════

function FinalCta() {
    return (
        <section id="cta" className="bg-white px-5 py-20 sm:px-8 sm:py-24">
            <div className="mx-auto max-w-5xl">
                <div
                    className="relative overflow-hidden rounded-[32px] p-10 sm:p-16"
                    style={{
                        background:
                            'linear-gradient(135deg, #082443 0%, #0B385E 50%, #0F4C81 100%)',
                    }}
                >
                    <div className="grain" aria-hidden />
                    <div
                        aria-hidden
                        className="pointer-events-none absolute -top-24 left-1/2 size-[640px] -translate-x-1/2 rounded-full"
                        style={{ background: 'radial-gradient(closest-side, rgba(21,101,172,.40), transparent 70%)' }}
                    />
                    <div
                        aria-hidden
                        className="pointer-events-none absolute -bottom-32 -right-32 size-[420px] rounded-full"
                        style={{ background: 'radial-gradient(closest-side, rgba(63,150,112,.28), transparent 70%)' }}
                    />
                    <div className="relative text-center text-white">
                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-[11px] font-medium text-white/85 backdrop-blur">
                            <span
                                className="size-1.5 rounded-full bg-sage-300"
                                style={{ animation: 'pulseDot 2s infinite' }}
                            />
                            Pilote gratuit · 3 mois · accompagnement inclus
                        </span>
                        <h2 className="h-display mx-auto mt-6 max-w-2xl text-4xl text-white sm:text-5xl lg:text-6xl">
                            Prêt à structurer
                            <br />
                            <em className="accent-text">votre démarche qualité ?</em>
                        </h2>
                        <p className="mx-auto mt-5 max-w-md text-base font-light leading-relaxed text-white/65">
                            Échangeons sur vos enjeux et ouvrons un environnement pilote pour vos coordinateurs.
                        </p>
                        <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
                            <a
                                href="mailto:contact@hsquality.fr"
                                className="inline-flex items-center gap-2 rounded-full bg-white px-7 py-3.5 text-sm font-semibold text-ink-900 transition-all hover:-translate-y-0.5 hover:shadow-2xl"
                            >
                                Demander une démo
                                <Arrow />
                            </a>
                            <Link
                                href="/login"
                                className="inline-flex items-center gap-2 rounded-full border border-white/20 px-7 py-3.5 text-sm font-medium text-white/85 transition-colors hover:border-white/40 hover:text-white"
                            >
                                Déjà client ? Se connecter
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  FOOTER
// ════════════════════════════════════════════════════════════════════════════

function Footer() {
    const cols = [
        { h: 'Plateforme', links: ['Modules', 'Mobile offline', 'API & connecteurs', 'IA prédictive'] },
        { h: 'Conformité', links: ['HAS', 'AFNOR NF X50-056', 'ISO 9001', 'Caphandeo'] },
        { h: 'Ressources', links: ['Documentation', 'Support', 'Formations', 'Contact'] },
    ];
    return (
        <footer className="border-t border-ink-100 bg-white px-5 py-14 sm:px-8">
            <div className="mx-auto max-w-7xl">
                <div className="grid grid-cols-2 gap-8 border-b border-ink-100 pb-10 lg:grid-cols-5">
                    <div className="col-span-2">
                        <div className="text-xl font-light tracking-tight text-ink-900">
                            HS<em className="accent-text">Q</em>uality
                        </div>
                        <p className="mt-4 max-w-xs text-sm font-light leading-relaxed text-ink-600">
                            Pilotage qualité et QVCT pour les structures médico-sociales d'aide et de soins
                            à domicile.
                        </p>
                        <div className="mt-5 flex flex-wrap gap-1.5">
                            {['RGPD', 'HDS', 'HAS', 'ISO 9001', 'AFNOR'].map((b) => (
                                <span
                                    key={b}
                                    className="rounded-md border border-ink-200 px-2 py-0.5 text-[11px] font-semibold text-ink-500"
                                >
                                    {b}
                                </span>
                            ))}
                        </div>
                    </div>

                    {cols.map((col) => (
                        <div key={col.h}>
                            <h5 className="text-[13px] font-semibold text-ink-900">{col.h}</h5>
                            <ul className="mt-4 space-y-2.5">
                                {col.links.map((l) => (
                                    <li key={l}>
                                        <a
                                            href="#"
                                            className="text-sm text-ink-500 transition-colors hover:text-brand-600"
                                        >
                                            {l}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3 pt-6 text-xs text-ink-400">
                    <p>© {new Date().getFullYear()} HS Quality · CDC-QUALITE-DOM-2024-v2.0</p>
                    <div className="flex gap-5">
                        {['Confidentialité', 'CGU', 'Mentions légales', 'Accessibilité'].map((l) => (
                            <a key={l} href="#" className="transition-colors hover:text-ink-700">
                                {l}
                            </a>
                        ))}
                    </div>
                </div>
            </div>
        </footer>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  ICONS
// ════════════════════════════════════════════════════════════════════════════

function Arrow({ className = 'size-4' }: { className?: string }) {
    return (
        <svg className={className} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
        </svg>
    );
}
