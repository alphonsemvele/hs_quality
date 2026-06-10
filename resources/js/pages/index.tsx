import { MarketingFooter, MarketingNav, MarketingStyles } from '@/components/marketing/MarketingShell';
import { Head, Link } from '@inertiajs/react';
import { type ReactNode, type RefObject, useCallback, useEffect, useRef, useState } from 'react';

// ════════════════════════════════════════════════════════════════════════════
//  HOOKS & ANIMATION PRIMITIVES
// ════════════════════════════════════════════════════════════════════════════

function useInView(options?: IntersectionObserverInit): [RefObject<HTMLDivElement | null>, boolean] {
    const ref = useRef<HTMLDivElement | null>(null);
    const [inView, setInView] = useState(false);
    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        const obs = new IntersectionObserver(
            ([entry]) => { if (entry.isIntersecting) { setInView(true); obs.disconnect(); } },
            { threshold: 0.15, ...options },
        );
        obs.observe(el);
        return () => obs.disconnect();
    }, []);
    return [ref, inView];
}

function useCountUp(target: number, duration = 1800): [RefObject<HTMLSpanElement | null>, string] {
    const ref = useRef<HTMLSpanElement | null>(null);
    const [value, setValue] = useState('0');
    const started = useRef(false);
    useEffect(() => {
        const el = ref.current;
        if (!el) return;
        const obs = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting && !started.current) {
                started.current = true;
                const start = performance.now();
                const step = (now: number) => {
                    const t = Math.min((now - start) / duration, 1);
                    const ease = 1 - Math.pow(1 - t, 3);
                    const current = Math.round(ease * target);
                    setValue(current.toLocaleString('fr-FR'));
                    if (t < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
                obs.disconnect();
            }
        }, { threshold: 0.3 });
        obs.observe(el);
        return () => obs.disconnect();
    }, [target, duration]);
    return [ref, value];
}

function Reveal({ children, className = '', delay = 0, direction = 'up' }: {
    children: ReactNode; className?: string; delay?: number;
    direction?: 'up' | 'left' | 'right';
}) {
    const [ref, inView] = useInView();
    const transforms: Record<string, string> = {
        up: 'translateY(30px)',
        left: 'translateX(40px)',
        right: 'translateX(-40px)',
    };
    return (
        <div ref={ref} className={className} style={{
            opacity: inView ? 1 : 0,
            transform: inView ? 'translate(0,0)' : transforms[direction],
            transition: `opacity 0.8s cubic-bezier(0.22, 1, 0.36, 1) ${delay}s, transform 0.8s cubic-bezier(0.22, 1, 0.36, 1) ${delay}s`,
        }}>
            {children}
        </div>
    );
}

function AnimatedBar({ width, className = '' }: { width: number; className?: string }) {
    const [ref, inView] = useInView();
    return (
        <div ref={ref} className="h-1.5 overflow-hidden rounded-full bg-ink-200">
            <div className={`h-full rounded-full transition-all duration-1000 ease-out ${className}`}
                style={{ width: inView ? `${width}%` : '0%' }} />
        </div>
    );
}

function FeatureCard({ icon, title, desc }: { icon: ReactNode; title: string; desc: string }) {
    const cardRef = useRef<HTMLDivElement | null>(null);
    const handleMouseMove = useCallback((e: React.MouseEvent) => {
        const el = cardRef.current;
        if (!el) return;
        const rect = el.getBoundingClientRect();
        el.style.setProperty('--mouse-x', `${e.clientX - rect.left}px`);
        el.style.setProperty('--mouse-y', `${e.clientY - rect.top}px`);
    }, []);
    return (
        <div ref={cardRef} onMouseMove={handleMouseMove}
            className="glow-hover card-hover group relative rounded-2xl border border-ink-100 bg-white p-6">
            <div className="relative z-10">
                <div className="flex size-11 items-center justify-center rounded-xl bg-ink-50 text-ink-600 transition-all duration-300 group-hover:bg-brand-50 group-hover:text-brand-600 group-hover:shadow-md group-hover:shadow-brand-100">
                    {icon}
                </div>
                <h3 className="mt-4 text-[15px] font-semibold text-ink-900">{title}</h3>
                <p className="mt-2 text-sm leading-relaxed text-ink-500">{desc}</p>
            </div>
        </div>
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

function IconClipboard() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
        </svg>
    );
}

function IconShield() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
    );
}

function IconChart() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
    );
}

function IconHeart() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
    );
}

function IconAcademic() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
        </svg>
    );
}

function IconPhone() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
    );
}

function IconCheck() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
        </svg>
    );
}

function IconChat() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
    );
}

function IconDashboard() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
        </svg>
    );
}

function IconSparkles() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 3v4M3 5h4M6 17v4M4 19h4M13 3l2.5 5L21 10l-5.5 2L13 17l-2.5-5L5 10l5.5-2L13 3z" />
        </svg>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  DATA
// ════════════════════════════════════════════════════════════════════════════

type Faq = { q: string; a: string };

const FEATURES = [
    { icon: <IconClipboard />, title: 'Traçabilité terrain', desc: `Pointage géolocalisé, voice-to-text, signature électronique. Chaque intervention est documentée et consultable en temps réel par l'équipe de coordination.` },
    { icon: <IconShield />, title: 'Gestion des incidents', desc: `Déclaration mobile en moins de 2 minutes, classification automatique par gravité, analyse 5-pourquoi, notification ARS si événement indésirable grave.` },
    { icon: <IconChart />, title: 'Audits & conformité', desc: `Audits HAS, AFNOR NF X50-056, ISO 9001 sur tablette. Scoring automatique, plan d'amélioration continue généré depuis les écarts détectés.` },
    { icon: <IconHeart />, title: 'Baromètre QVCT', desc: `Enquêtes régulières sur la qualité de vie au travail des intervenants. Cartographie des signaux faibles, prévention de l'épuisement professionnel.` },
    { icon: <IconAcademic />, title: 'Formation continue', desc: `Gestion du plan de développement des compétences, suivi des habilitations, rappels automatiques des échéances réglementaires.` },
    { icon: <IconPhone />, title: 'Application mobile', desc: `React Native offline-first pour les intervenants en zones blanches. Synchronisation dès retour réseau, interface pensée pour le terrain.` },
    { icon: <IconChat />, title: 'Communication interne', desc: `Messagerie sécurisée, fil d'actualité, forum d'entraide, bibliothèque de protocoles. Brise l'isolement des intervenants au domicile.` },
    { icon: <IconDashboard />, title: 'Indicateurs & dashboards', desc: `Tableau de bord exécutif et opérationnel, alertes hors-seuil, export PDF/Excel, rapport annuel qualité généré automatiquement pour les autorités.` },
    { icon: <IconSparkles />, title: 'IA prédictive', desc: `Détection précoce du risque de burnout, anticipation des pertes d'autonomie, benchmark anonymisé inter-structures. Disponible sur l'offre Premium.` },
];

const FAQS: Faq[] = [
    {
        q: 'Que comprend exactement HS Quality ?',
        a: `Une plateforme complète : suivi des interventions terrain (mobile + web), gestion des incidents et EI, audits qualité (HAS, AFNOR, ISO 9001, Caphandeo), baromètre QVCT, gestion des compétences et plans de soins. Tout est intégré et conforme HAS / RGPD / HDS.`,
    },
    {
        q: `À quelle fréquence dois-je faire des audits qualité ?`,
        a: `L'évaluation externe HAS doit avoir lieu tous les 5 ans. En interne, un point trimestriel est recommandé. HS Quality vous permet de programmer ces audits, de scorer en temps réel et de générer le PAC depuis les écarts détectés.`,
    },
    {
        q: 'Combien de temps pour mettre en place HS Quality ?',
        a: `Deux semaines en moyenne. Provisioning de votre tenant, import de vos bénéficiaires et intervenants, formation des coordinateurs (2h), puis déploiement progressif. Un référent qualité vous accompagne tout au long du pilote.`,
    },
    {
        q: 'Mes données sont-elles bien sécurisées ?',
        a: `Hébergement HDS en France (AWS Paris ou OVHcloud), chiffrement AES-256 au repos et TLS 1.3 en transit, MFA obligatoire pour les rôles privilégiés. Audit complet de chaque accès aux données de santé. Conformité RGPD et code de la santé publique.`,
    },
    {
        q: `Puis-je essayer avant de m'engager ?`,
        a: `Oui, l'essai pilote est gratuit pendant 3 mois, sans carte bancaire et avec un référent dédié. Si la solution ne correspond pas à vos besoins, vos données sont restituées ou supprimées sur simple demande.`,
    },
];

const PRICING = [
    {
        name: 'Essentiel',
        price: '8',
        unit: '€ / utilisateur / mois',
        desc: 'Pour les petites structures qui démarrent leur démarche qualité.',
        features: [
            'Traçabilité des interventions',
            'Gestion des incidents (basique)',
            'Tableau de bord basique',
            'Application mobile offline',
            'Messagerie interne',
            'Support par email (48h)',
        ],
        highlighted: false,
    },
    {
        name: 'Pro',
        price: '15',
        unit: '€ / utilisateur / mois',
        desc: 'Pour les structures engagées dans une démarche qualité complète.',
        features: [
            'Tout Essentiel +',
            'Gestion des incidents (complet)',
            'Module QVCT (baromètre)',
            'Audits & conformité (grilles standard)',
            'Plan d\'amélioration continue (PAC)',
            'Communication : messagerie + forum',
            'Gestion compétences (basique)',
            'Tableaux de bord avancés',
            'API & connecteurs',
            'Support email (24h)',
        ],
        highlighted: true,
    },
    {
        name: 'Premium',
        price: '25',
        unit: '€ / utilisateur / mois',
        desc: 'Pour les groupes multi-sites et les structures à forte exigence qualité.',
        features: [
            'Tout Pro +',
            'Gestion des incidents complète + IA',
            'Audits personnalisés + préparation HAS',
            'Gestion compétences complète + e-learning',
            'Communication : messagerie + forum + visioconférence',
            'Tableau de bord exécutif + export PDF/Excel',
            'Portail bénéficiaires & familles',
            'IA prédictive (burnout, autonomie, benchmark)',
            'Support téléphone + Customer Success Manager dédié',
        ],
        highlighted: false,
    },
];

// ════════════════════════════════════════════════════════════════════════════
//  MAIN COMPONENT
// ════════════════════════════════════════════════════════════════════════════

export default function Welcome() {
    const [faqOpen, setFaqOpen] = useState<number | null>(0);

    return (
        <>
            <Head title="HS Quality — Pilotez la qualité de votre service à domicile">
                <meta
                    name="description"
                    content="Plateforme SaaS dédiée aux SAAD, SSIAD, SPASAD, ESAD et CCAS pour piloter la démarche qualité, la QVCT et la conformité HAS. Pilote gratuit 3 mois, hébergement HDS en France."
                />
                <meta property="og:title" content="HS Quality — Qualité et QVCT pour l'aide à domicile" />
                <meta
                    property="og:description"
                    content="Du terrain à la direction : déclarations d'incidents, plans de soins, audits HAS, baromètre QVCT. Hébergement HDS France, conformité RGPD."
                />
                <meta property="og:type" content="website" />
                <meta property="og:locale" content="fr_FR" />
                <meta name="twitter:card" content="summary_large_image" />
                <link rel="canonical" href="https://hsquality.fr/" />
            </Head>
            <MarketingStyles />

            <div className="min-h-screen bg-white">
                <a href="#main-content" className="skip-link">Aller au contenu principal</a>
                <MarketingNav mode="translucent" />
                <HeroSection />
                <LogoBar />
                <FeaturesSection />
                <HowItWorksSection />
                <BentoGrid />
                <StructureTypesSection />
                <MetricsSection />
                <TestimonialSection />
                <PricingSection />
                <FaqSection open={faqOpen} setOpen={setFaqOpen} />
                <FinalCta />
                <MarketingFooter />
            </div>
        </>
    );
}


// ════════════════════════════════════════════════════════════════════════════
//  HERO
// ════════════════════════════════════════════════════════════════════════════

const HERO_PHOTO = 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1920&auto=format&fit=crop&q=80';

function HeroSection() {
    return (
        <section id="main-content" className="relative min-h-screen overflow-hidden bg-ink-900 text-white">
            {/* Background photo */}
            <div className="absolute inset-0">
                <img src={HERO_PHOTO} alt="" className="size-full object-cover" loading="eager" />
                <div className="absolute inset-0" style={{
                    background: 'linear-gradient(135deg, rgba(8,36,67,0.92) 0%, rgba(15,23,42,0.82) 40%, rgba(15,23,42,0.70) 100%)',
                }} />
            </div>
            {/* Mesh gradient orbs — on top of photo for color accent */}
            <div aria-hidden className="pointer-events-none absolute -right-40 -top-40 size-[700px] rounded-full opacity-40"
                style={{ background: 'radial-gradient(closest-side, rgba(21,101,172,.35), transparent 70%)', animation: 'meshMove 12s ease-in-out infinite' }} />
            <div aria-hidden className="pointer-events-none absolute -left-32 bottom-[-20%] size-[600px] rounded-full opacity-30"
                style={{ background: 'radial-gradient(closest-side, rgba(63,150,112,.25), transparent 70%)', animation: 'meshMove2 14s ease-in-out infinite' }} />
            <div className="grain" aria-hidden />

            <div className="relative mx-auto max-w-7xl px-5 pt-32 sm:px-8 sm:pt-40 lg:pt-48">
                {/* Badge */}
                <div className="fade-in delay-1 flex justify-center">
                    <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-[12px] font-medium text-white/85 backdrop-blur">
                        <span className="size-1.5 rounded-full bg-sage-400" style={{ animation: 'pulse 2s infinite' }} />
                        Plateforme conforme HAS · RGPD · HDS
                    </span>
                </div>

                {/* Headline */}
                <h1 className="fade-in delay-2 mx-auto mt-8 max-w-4xl text-center text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-7xl" style={{ lineHeight: 1.1 }}>
                    La qualité du soin à domicile,{' '}
                    <span className="gradient-text-light">enfin pilotée.</span>
                </h1>

                {/* Subtitle */}
                <p className="fade-in delay-3 mx-auto mt-6 max-w-xl text-center text-base font-light leading-relaxed text-white/65 sm:text-lg">
                    HS Quality structure le pilotage qualité et QVCT des services à domicile — interventions, incidents, audits, formations. Du terrain à la direction, en temps réel.
                </p>

                {/* CTAs */}
                <div className="fade-in delay-4 mt-10 flex flex-wrap items-center justify-center gap-3">
                    <a href="#cta"
                        className="btn-glow inline-flex items-center gap-2 rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-ink-900 transition-all hover:-translate-y-0.5 hover:shadow-2xl">
                        Démarrer le pilote 3 mois
                        <Arrow />
                    </a>
                    <a href="#modules"
                        className="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/5 px-8 py-3.5 text-sm font-semibold text-white backdrop-blur transition-all hover:border-white/35 hover:bg-white/10">
                        Voir la démo
                    </a>
                </div>

                {/* Social proof */}
                <p className="fade-in delay-4 mt-8 text-center text-sm text-white/45">
                    Hébergement HDS France · Conforme RGPD · Référentiel HAS
                </p>

                {/* Dashboard preview */}
                <div className="fade-in delay-5 mx-auto mt-14 max-w-4xl pb-20">
                    <DashboardPreview />
                </div>
            </div>
        </section>
    );
}

function DashboardPreview() {
    return (
        <div className="dashboard-glow relative">
            <div className="overflow-hidden rounded-t-2xl border border-white/10 bg-ink-800 shadow-2xl">
                {/* Window chrome */}
                <div className="flex items-center gap-2 border-b border-white/10 px-4 py-2.5">
                    <div className="size-2.5 rounded-full bg-danger-500/60" />
                    <div className="size-2.5 rounded-full bg-warning-500/60" />
                    <div className="size-2.5 rounded-full bg-sage-500/60" />
                    <span className="ml-3 text-[11px] text-white/40">app.hsquality.fr/dashboard</span>
                </div>

                {/* KPI grid */}
                <div className="p-4 sm:p-6">
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        {[
                            { label: 'Score qualité', value: '87', sub: '/100', accent: true },
                            { label: 'Interventions', value: '1 248', sub: 'ce mois', accent: false },
                            { label: 'Incidents ouverts', value: '3', sub: 'à traiter', accent: false },
                            { label: 'Traçabilité', value: '94%', sub: '↑ +6%', accent: false },
                        ].map((kpi) => (
                            <div key={kpi.label} className="rounded-xl bg-white/[0.06] p-4 ring-1 ring-white/10">
                                <p className="text-[11px] text-white/50">{kpi.label}</p>
                                <div className="mt-1.5 flex items-baseline gap-1">
                                    <span className={`mono text-2xl font-semibold ${kpi.accent ? 'text-sage-300' : 'text-white'}`}>{kpi.value}</span>
                                    <span className="text-[11px] text-white/40">{kpi.sub}</span>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Chart + incidents list */}
                    <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div className="rounded-xl bg-white/[0.06] p-4 ring-1 ring-white/10">
                            <p className="text-[11px] text-white/50">Interventions / semaine</p>
                            <div className="mt-3 flex items-end gap-1.5" style={{ height: 64 }}>
                                {[40, 55, 35, 70, 60, 85, 75, 90, 65, 80, 72, 88].map((h, i) => (
                                    <div key={i} className="flex-1 rounded-sm bg-gradient-to-t from-brand-700 to-brand-400"
                                        style={{ height: `${h}%`, minHeight: 4 }} />
                                ))}
                            </div>
                        </div>
                        <div className="rounded-xl bg-white/[0.06] p-4 ring-1 ring-white/10">
                            <div className="flex items-center justify-between">
                                <p className="text-[11px] text-white/50">Incidents récents</p>
                                <span className="rounded-full bg-danger-500/20 px-2 py-0.5 text-[10px] font-semibold text-danger-200">2 graves</span>
                            </div>
                            <div className="mt-3 space-y-2">
                                {[
                                    { who: 'Chute · Mme D.', tag: 'Grave', tone: 'danger' as const },
                                    { who: 'Erreur médic. · M. P.', tag: 'En analyse', tone: 'warning' as const },
                                    { who: 'Retard · Mme R.', tag: 'Clos', tone: 'mute' as const },
                                ].map((row) => (
                                    <div key={row.who} className="flex items-center justify-between text-[11px]">
                                        <span className="text-white/70">{row.who}</span>
                                        <span className={`rounded-full px-2 py-0.5 text-[9px] font-semibold ${
                                            row.tone === 'danger' ? 'bg-danger-500/20 text-danger-200'
                                                : row.tone === 'warning' ? 'bg-warning-500/20 text-warning-200'
                                                    : 'bg-white/10 text-white/40'
                                        }`}>{row.tag}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {/* Fade to background */}
            <div className="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-ink-900 to-transparent" />
        </div>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  LOGO BAR (marquee)
// ════════════════════════════════════════════════════════════════════════════

function LogoBar() {
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
                    <span key={i} className="flex shrink-0 items-center gap-3 text-xs font-medium uppercase tracking-widest text-ink-500">
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
//  FEATURES (6 cards, 3x2 grid)
// ════════════════════════════════════════════════════════════════════════════

function FeaturesSection() {
    return (
        <section id="features" className="bg-white px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto max-w-7xl">
                <Reveal className="text-center">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Fonctionnalités
                    </span>
                    <h2 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Tout ce dont vous avez besoin,{' '}
                        <span className="gradient-text">rien de superflu.</span>
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-base font-light leading-relaxed text-ink-600">
                        Neuf modules métier pensés pour le quotidien des structures médico-sociales, du terrain à la direction.
                    </p>
                </Reveal>

                <div className="mt-14 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {FEATURES.map((f, i) => (
                        <Reveal key={f.title} delay={0.06 * i}>
                            <FeatureCard icon={f.icon} title={f.title} desc={f.desc} />
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  HOW IT WORKS (3 steps)
// ════════════════════════════════════════════════════════════════════════════

const STEPS = [
    {
        step: '01',
        title: 'Provisioning en 48h',
        desc: 'Création de votre espace sécurisé HDS, import de vos bénéficiaires et intervenants. Configuration des rôles et permissions adaptée à votre organigramme.',
        icon: (
            <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
            </svg>
        ),
    },
    {
        step: '02',
        title: 'Formation & déploiement',
        desc: 'Formation des coordinateurs (2h), prise en main par les intervenants via l\'app mobile. Un référent qualité dédié vous accompagne pendant tout le pilote.',
        icon: (
            <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
            </svg>
        ),
    },
    {
        step: '03',
        title: 'Pilotage en continu',
        desc: 'Vos indicateurs qualité se remplissent automatiquement. Audits, incidents, QVCT, formations — tout converge vers un tableau de bord actionnable.',
        icon: (
            <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
        ),
    },
];

function HowItWorksSection() {
    return (
        <section className="relative overflow-hidden bg-ink-50/40 px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto max-w-7xl">
                <Reveal className="text-center">
                    <span className="inline-block rounded-full bg-sage-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-sage-700">
                        Mise en place
                    </span>
                    <h2 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Opérationnel en{' '}
                        <span className="gradient-text">2 semaines.</span>
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-base font-light leading-relaxed text-ink-600">
                        Un processus d'intégration pensé pour ne pas perturber votre activité quotidienne.
                    </p>
                </Reveal>

                <div className="mt-16 grid grid-cols-1 gap-6 md:grid-cols-3">
                    {STEPS.map((s, i) => (
                        <Reveal key={s.step} delay={0.1 * i}>
                            <div className="group relative flex h-full flex-col rounded-2xl border border-ink-100 bg-white p-7 transition-all duration-300 hover:border-brand-200 hover:shadow-lg hover:shadow-brand-50">
                                {/* Step number + connector line */}
                                <div className="flex items-center gap-4">
                                    <div className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition-colors duration-300 group-hover:bg-brand-600 group-hover:text-white">
                                        {s.icon}
                                    </div>
                                    <span className="mono text-[11px] font-semibold uppercase tracking-widest text-ink-300">
                                        Étape {s.step}
                                    </span>
                                </div>

                                {/* Content */}
                                <h3 className="mt-5 text-lg font-semibold text-ink-900">{s.title}</h3>
                                <p className="mt-2 flex-1 text-sm leading-relaxed text-ink-500">{s.desc}</p>

                                {/* Connector arrow (hidden on last card) */}
                                {i < STEPS.length - 1 && (
                                    <div className="absolute -right-3 top-1/2 z-10 hidden -translate-y-1/2 md:block">
                                        <div className="flex size-6 items-center justify-center rounded-full bg-white shadow ring-1 ring-ink-100">
                                            <svg className="size-3 text-ink-400" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M8 4l8 8-8 8" />
                                            </svg>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  BENTO GRID
// ════════════════════════════════════════════════════════════════════════════

function BentoGrid() {
    return (
        <section id="modules" className="bg-ink-50/40 px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto max-w-7xl">
                <Reveal className="text-center">
                    <span className="inline-block rounded-full bg-sage-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-sage-700">
                        Modules
                    </span>
                    <h2 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Une vue sur chaque dimension{' '}
                        <span className="gradient-text">qualité.</span>
                    </h2>
                </Reveal>

                {/* Top row: 2+1 */}
                <div className="mt-14 grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <Reveal delay={0} className="lg:col-span-2">
                        <BentoInterventions />
                    </Reveal>
                    <Reveal delay={0.08}>
                        <BentoIncidents />
                    </Reveal>
                </div>

                {/* Bottom row: 1+2 */}
                <div className="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <Reveal delay={0.14}>
                        <BentoAudits />
                    </Reveal>
                    <Reveal delay={0.2} className="lg:col-span-2">
                        <BentoQvct />
                    </Reveal>
                </div>
            </div>
        </section>
    );
}

function BentoInterventions() {
    const interventions = [
        { time: '08:30', name: 'Mme Dupont', type: 'Aide à la toilette', status: 'Terminée' },
        { time: '09:15', name: 'M. Bernard', type: 'Préparation repas', status: 'En cours' },
        { time: '10:00', name: 'Mme Léon', type: 'Accompagnement', status: 'Planifiée' },
        { time: '11:30', name: 'M. Petit', type: 'Aide au ménage', status: 'Planifiée' },
    ];
    return (
        <div className="h-full overflow-hidden rounded-2xl border border-ink-100 bg-white p-6">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-base font-semibold text-ink-900">Interventions du jour</h3>
                    <p className="mt-1 text-sm text-ink-500">Suivi en temps réel</p>
                </div>
                <span className="mono rounded-lg bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-700">12 / 14</span>
            </div>
            <div className="mt-5 space-y-2.5">
                {interventions.map((item) => (
                    <div key={item.time + item.name} className="flex items-center gap-3 rounded-xl bg-ink-50/80 px-4 py-2.5">
                        <span className="mono w-12 text-xs text-ink-400">{item.time}</span>
                        <div className="flex-1">
                            <p className="text-sm font-medium text-ink-900">{item.name}</p>
                            <p className="text-xs text-ink-500">{item.type}</p>
                        </div>
                        <span className={`rounded-full px-2.5 py-0.5 text-[11px] font-semibold ${
                            item.status === 'Terminée' ? 'bg-sage-50 text-sage-700'
                                : item.status === 'En cours' ? 'bg-brand-50 text-brand-700'
                                    : 'bg-ink-100 text-ink-500'
                        }`}>{item.status}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function BentoIncidents() {
    return (
        <div className="h-full overflow-hidden rounded-2xl border border-ink-100 bg-white p-6">
            <h3 className="text-base font-semibold text-ink-900">Incidents</h3>
            <p className="mt-1 text-sm text-ink-500">Ce mois-ci</p>
            <div className="mt-5 grid grid-cols-2 gap-3">
                <div className="rounded-xl bg-danger-50 p-3 text-center">
                    <span className="mono text-2xl font-semibold text-danger-600">2</span>
                    <p className="mt-0.5 text-[11px] text-danger-600">Graves</p>
                </div>
                <div className="rounded-xl bg-warning-50 p-3 text-center">
                    <span className="mono text-2xl font-semibold text-warning-600">5</span>
                    <p className="mt-0.5 text-[11px] text-warning-600">En analyse</p>
                </div>
            </div>
            <div className="mt-4 rounded-xl bg-sage-50 px-4 py-3">
                <div className="flex items-center gap-2">
                    <span className="size-1.5 rounded-full bg-sage-500" style={{ animation: 'pulse 2s infinite' }} />
                    <span className="text-xs font-medium text-sage-700">100% notifiés ARS &lt; 24h</span>
                </div>
            </div>
            <div className="mt-3 space-y-1.5">
                {[
                    { label: 'Déclarés ce mois', val: '12' },
                    { label: 'Délai moyen résolution', val: '3,2 j' },
                ].map((row) => (
                    <div key={row.label} className="flex items-center justify-between text-xs">
                        <span className="text-ink-500">{row.label}</span>
                        <span className="mono font-semibold text-ink-900">{row.val}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

function BentoAudits() {
    return (
        <div className="h-full overflow-hidden rounded-2xl border border-ink-100 bg-white p-6">
            <div className="flex items-center justify-between">
                <h3 className="text-base font-semibold text-ink-900">Audit HAS · 2026</h3>
                <span className="rounded-full bg-sage-50 px-2.5 py-0.5 text-[11px] font-semibold text-sage-700">Finalisé</span>
            </div>
            <div className="mt-5 flex items-baseline gap-1">
                <span className="mono text-4xl font-semibold tracking-tight text-ink-900">87</span>
                <span className="text-sm text-ink-400">/100</span>
            </div>
            <div className="mt-5 space-y-3">
                {[
                    { label: 'Bientraitance', pct: 92 },
                    { label: 'Coordination', pct: 84 },
                    { label: 'Traçabilité', pct: 88 },
                    { label: 'Droits & éthique', pct: 79 },
                ].map((item) => (
                    <div key={item.label}>
                        <div className="flex justify-between text-[11px]">
                            <span className="text-ink-600">{item.label}</span>
                            <span className="mono font-medium text-ink-900">{item.pct}%</span>
                        </div>
                        <div className="mt-1">
                            <AnimatedBar width={item.pct} className="bg-gradient-to-r from-sage-500 to-brand-400" />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function BentoQvct() {
    const dimensions = ['Sens du travail', 'Charge', 'Autonomie', 'Relations', 'Reconnaissance'];
    return (
        <div className="h-full overflow-hidden rounded-2xl border border-ink-100 bg-white p-6">
            <div className="flex items-center justify-between">
                <div>
                    <h3 className="text-base font-semibold text-ink-900">Baromètre QVCT</h3>
                    <p className="mt-1 text-sm text-ink-500">Dernier trimestre</p>
                </div>
                <span className="mono rounded-lg bg-sage-50 px-3 py-1.5 text-sm font-semibold text-sage-700">7.2 / 10</span>
            </div>
            <div className="mt-5 grid grid-cols-1 items-center gap-6 sm:grid-cols-2">
                {/* Radar chart SVG */}
                <div className="flex justify-center">
                    <svg viewBox="0 0 200 200" className="size-48" aria-hidden>
                        {/* Grid */}
                        <g stroke="currentColor" className="text-ink-200" fill="none">
                            <polygon points="100,30 165,65 145,140 55,140 35,65" />
                            <polygon points="100,50 148,75 133,125 67,125 52,75" />
                            <polygon points="100,70 130,85 120,110 80,110 70,85" />
                        </g>
                        {/* Data */}
                        <polygon
                            points="100,38 158,70 138,132 62,125 45,68"
                            fill="rgba(63,150,112,.25)"
                            stroke="rgb(63,150,112)"
                            strokeWidth="2"
                        />
                        {/* Labels */}
                        <g fill="currentColor" className="text-ink-500" fontSize="8" fontFamily="Poppins">
                            <text x="100" y="22" textAnchor="middle">Sens</text>
                            <text x="175" y="68" textAnchor="start">Charge</text>
                            <text x="152" y="148" textAnchor="start">Autonomie</text>
                            <text x="48" y="148" textAnchor="end">Relations</text>
                            <text x="25" y="68" textAnchor="end">Reconn.</text>
                        </g>
                        {/* Dots */}
                        <g fill="rgb(63,150,112)">
                            <circle cx="100" cy="38" r="3" />
                            <circle cx="158" cy="70" r="3" />
                            <circle cx="138" cy="132" r="3" />
                            <circle cx="62" cy="125" r="3" />
                            <circle cx="45" cy="68" r="3" />
                        </g>
                    </svg>
                </div>
                {/* Tags */}
                <div className="space-y-2">
                    {dimensions.map((dim, i) => {
                        const scores = [8.1, 6.4, 7.8, 7.5, 6.8];
                        return (
                            <div key={dim} className="flex items-center justify-between rounded-lg bg-ink-50/80 px-3 py-2">
                                <span className="text-sm text-ink-700">{dim}</span>
                                <span className={`mono text-sm font-semibold ${scores[i] >= 7.5 ? 'text-sage-600' : scores[i] >= 6.5 ? 'text-warning-600' : 'text-danger-600'}`}>
                                    {scores[i]}
                                </span>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  STRUCTURE TYPES (Enterprise Gateway — "Solutions par type")
// ════════════════════════════════════════════════════════════════════════════

const STRUCTURE_TYPES = [
    {
        name: 'SAAD',
        full: 'Service d\'Aide à Domicile',
        desc: 'Traçabilité des interventions d\'aide (toilette, repas, ménage), gestion des incidents terrain, suivi qualité prestataire et mandataire.',
        color: 'brand' as const,
        modules: ['Interventions', 'Incidents', 'QVCT', 'Audits'],
    },
    {
        name: 'SSIAD',
        full: 'Service de Soins Infirmiers à Domicile',
        desc: 'Coordination des soins infirmiers, suivi des plans de soins, traçabilité des actes, notification ARS des événements indésirables graves.',
        color: 'sage' as const,
        modules: ['Plans de soins', 'Incidents EIG', 'Formations', 'Conformité HAS'],
    },
    {
        name: 'ESAD / SPASAD',
        full: 'Service Polyvalent d\'Aide et de Soins',
        desc: 'Pilotage unifié aide + soins, tableau de bord consolidé, audits croisés, indicateurs qualité multi-prestations.',
        color: 'brand' as const,
        modules: ['Dashboard unifié', 'Audits croisés', 'QVCT', 'API'],
    },
    {
        name: 'CCAS / Associations',
        full: 'Centres Communaux & Associations',
        desc: 'Multi-sites, consolidation groupe, benchmark inter-structures, reporting automatique pour les tutelles et financeurs.',
        color: 'sage' as const,
        modules: ['Multi-sites', 'Consolidation', 'Reporting', 'Benchmark'],
    },
    {
        name: 'Mandataires',
        full: 'Mandataires & prestataires indépendants',
        desc: 'Solution allégée pour les structures de petite taille (1 à 10 intervenants) ou les indépendants. Toute la traçabilité réglementaire à un coût adapté.',
        color: 'brand' as const,
        modules: ['Interventions', 'Incidents', 'Mobile offline', 'Conformité'],
    },
];

function StructureTypesSection() {
    const colorClasses = {
        brand: {
            badge: 'bg-brand-50 text-brand-700 ring-1 ring-brand-100',
            icon: 'bg-brand-50 text-brand-600',
            tag: 'bg-brand-50 text-brand-600',
        },
        sage: {
            badge: 'bg-sage-50 text-sage-700 ring-1 ring-sage-100',
            icon: 'bg-sage-50 text-sage-600',
            tag: 'bg-sage-50 text-sage-600',
        },
    };

    return (
        <section className="bg-white px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto max-w-7xl">
                <Reveal className="text-center">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Adapté à votre structure
                    </span>
                    <h2 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Une solution pour chaque{' '}
                        <span className="gradient-text">type de service.</span>
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-base font-light leading-relaxed text-ink-600">
                        Que vous soyez SAAD, SSIAD, ESAD ou CCAS, HS Quality s'adapte à vos obligations réglementaires et vos processus métier.
                    </p>
                </Reveal>

                <div className="mt-14 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    {STRUCTURE_TYPES.map((st, i) => {
                        const colors = colorClasses[st.color];
                        return (
                            <Reveal key={st.name} delay={0.08 * i}>
                                <div className="card-hover group flex h-full flex-col rounded-2xl border border-ink-100 bg-white p-6 sm:p-7">
                                    <div className="flex items-center gap-3">
                                        <span className={`rounded-lg px-3 py-1.5 text-sm font-bold ${colors.badge}`}>
                                            {st.name}
                                        </span>
                                    </div>
                                    <p className="mt-1.5 text-xs font-medium text-ink-400">{st.full}</p>
                                    <p className="mt-4 flex-1 text-sm leading-relaxed text-ink-600">{st.desc}</p>
                                    <div className="mt-5 flex flex-wrap gap-1.5">
                                        {st.modules.map((m) => (
                                            <span key={m} className={`rounded-md px-2 py-0.5 text-[11px] font-medium ${colors.tag}`}>
                                                {m}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </Reveal>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  METRICS (count-up)
// ════════════════════════════════════════════════════════════════════════════

function MetricsSection() {
    const [refModules, valModules] = useCountUp(9);
    const [refPersonas, valPersonas] = useCountUp(6);
    const [refConformite, valConformite] = useCountUp(100);

    return (
        <section className="bg-white px-5 py-20 sm:px-8 sm:py-28">
            <div className="mx-auto max-w-5xl">
                <div className="grid grid-cols-2 gap-8 sm:grid-cols-4">
                    <Reveal className="text-center">
                        <div className="mono text-3xl font-semibold tracking-tight text-ink-900 sm:text-4xl">
                            <span ref={refModules}>{valModules}</span>
                        </div>
                        <p className="mt-2 text-sm text-ink-500">modules métier intégrés</p>
                    </Reveal>
                    <Reveal className="text-center" delay={0.08}>
                        <div className="mono text-3xl font-semibold tracking-tight text-ink-900 sm:text-4xl">
                            <span ref={refPersonas}>{valPersonas}</span>
                        </div>
                        <p className="mt-2 text-sm text-ink-500">personas couverts</p>
                    </Reveal>
                    <Reveal className="text-center" delay={0.16}>
                        <div className="mono text-3xl font-semibold tracking-tight text-ink-900 sm:text-4xl">
                            <span ref={refConformite}>{valConformite}</span>%
                        </div>
                        <p className="mt-2 text-sm text-ink-500">conforme HAS · RGPD · HDS</p>
                    </Reveal>
                    <Reveal className="text-center" delay={0.24}>
                        <div className="mono text-3xl font-semibold tracking-tight text-ink-900 sm:text-4xl">
                            &lt; 2 min
                        </div>
                        <p className="mt-2 text-sm text-ink-500">par incident déclaré</p>
                    </Reveal>
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  TESTIMONIAL
// ════════════════════════════════════════════════════════════════════════════

const TESTIMONIALS = [
    {
        quote: 'Nos coordinatrices déclarent les incidents en moins de 2 minutes. Notre taux de conformité HAS est passé de 61% à 84% en 6 mois.',
        initials: 'MF',
        name: 'Marie-France Essomba',
        role: 'Directrice qualité · SAAD Horizon',
        metric: '+23%',
        metricLabel: 'conformité HAS',
    },
    {
        quote: 'Le baromètre QVCT nous a permis de détecter un risque de turn-over chez nos aides-soignantes avant qu\'il ne devienne critique. On a pu agir à temps.',
        initials: 'AT',
        name: 'Alain Tchoupo',
        role: 'DRH · SSIAD Solidarité',
        metric: '-40%',
        metricLabel: 'turn-over An 1',
    },
    {
        quote: 'Avant HS Quality, nos audits HAS prenaient 3 semaines de préparation. Aujourd\'hui, le scoring est automatique et le PAC se génère depuis les écarts.',
        initials: 'CN',
        name: 'Claire Nguema',
        role: 'Référente qualité · ESAD Lumière',
        metric: '÷3',
        metricLabel: 'temps de préparation',
    },
];

function TestimonialSection() {
    return (
        <section className="bg-ink-50/40 px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto max-w-7xl">
                <Reveal className="text-center">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Témoignages
                    </span>
                    <h2 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Ils pilotent leur qualité{' '}
                        <span className="gradient-text">avec HS Quality.</span>
                    </h2>
                    <p className="mx-auto mt-4 max-w-xl text-xs italic text-ink-400">
                        Profils illustratifs reflétant les cibles d'amélioration du cahier des charges. Les vrais retours pilote seront publiés à l'issue de la Phase 1.
                    </p>
                </Reveal>

                <div className="mt-14 grid grid-cols-1 gap-5 lg:grid-cols-3">
                    {TESTIMONIALS.map((t, i) => (
                        <Reveal key={t.name} delay={0.08 * i}>
                            <div className="card-hover flex h-full flex-col rounded-2xl border border-ink-100 bg-white p-6 sm:p-7">
                                {/* Quote icon */}
                                <svg className="size-8 text-brand-100" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
                                    <path d="M4.583 17.321C3.553 16.227 3 15 3 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311C9.591 11.69 11 13.166 11 15c0 1.933-1.567 3.5-3.5 3.5-1.218 0-2.36-.558-2.917-1.179zm10 0C13.553 16.227 13 15 13 13.011c0-3.5 2.457-6.637 6.03-8.188l.893 1.378c-3.335 1.804-3.987 4.145-4.247 5.621.537-.278 1.24-.375 1.929-.311C19.591 11.69 21 13.166 21 15c0 1.933-1.567 3.5-3.5 3.5-1.218 0-2.36-.558-2.917-1.179z" />
                                </svg>

                                {/* Quote text */}
                                <blockquote className="mt-4 flex-1 text-[15px] font-medium leading-relaxed text-ink-700" style={{ lineHeight: 1.55 }}>
                                    "{t.quote}"
                                </blockquote>

                                {/* Metric badge */}
                                <div className="mt-5 inline-flex self-start rounded-lg bg-sage-50 px-3 py-1.5">
                                    <span className="mono text-sm font-bold text-sage-700">{t.metric}</span>
                                    <span className="ml-1.5 text-xs text-sage-600">{t.metricLabel}</span>
                                </div>

                                {/* Author */}
                                <div className="mt-5 flex items-center gap-3 border-t border-ink-100 pt-5">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-semibold text-white">
                                        {t.initials}
                                    </div>
                                    <div>
                                        <p className="text-sm font-semibold text-ink-900">{t.name}</p>
                                        <p className="text-xs text-ink-500">{t.role}</p>
                                    </div>
                                </div>
                            </div>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  PRICING
// ════════════════════════════════════════════════════════════════════════════

function PricingSection() {
    return (
        <section id="pricing" className="bg-white px-5 py-24 sm:px-8 sm:py-32">
            <div className="mx-auto max-w-7xl">
                <Reveal className="text-center">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700">
                        Tarifs
                    </span>
                    <h2 className="mt-5 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Un plan adapté à{' '}
                        <span className="gradient-text">votre structure.</span>
                    </h2>
                    <p className="mx-auto mt-4 max-w-lg text-base font-light text-ink-600">
                        Essai pilote gratuit 3 mois. Sans engagement, sans carte bancaire.
                    </p>
                    <p className="mx-auto mt-2 max-w-lg text-xs text-ink-400">
                        Tarification à l'utilisateur. Remises volume disponibles à partir de 100 intervenants — nous contacter.
                    </p>
                </Reveal>

                <div className="mt-14 grid grid-cols-1 gap-5 md:grid-cols-3">
                    {PRICING.map((plan, i) => (
                        <Reveal key={plan.name} delay={0.08 * i}>
                            <div className={`card-hover relative flex h-full flex-col rounded-2xl border p-6 sm:p-8 ${
                                plan.highlighted
                                    ? 'border-brand-200 bg-brand-50/30 ring-1 ring-brand-200'
                                    : 'border-ink-100 bg-white'
                            }`}>
                                {plan.highlighted && (
                                    <span className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-600 px-4 py-1 text-[11px] font-semibold text-white">
                                        Recommandé
                                    </span>
                                )}
                                <h3 className="text-lg font-semibold text-ink-900">{plan.name}</h3>
                                <div className="mt-3 flex items-baseline gap-1">
                                    <span className="mono text-4xl font-bold text-ink-900">{plan.price}</span>
                                    <span className="text-sm text-ink-500">{plan.unit}</span>
                                </div>
                                <p className="mt-3 text-sm text-ink-500">{plan.desc}</p>
                                <ul className="mt-6 flex-1 space-y-2.5">
                                    {plan.features.map((f) => (
                                        <li key={f} className="flex items-start gap-2">
                                            <span className={`mt-0.5 ${plan.highlighted ? 'text-brand-600' : 'text-sage-600'}`}>
                                                <IconCheck />
                                            </span>
                                            <span className="text-sm text-ink-700">{f}</span>
                                        </li>
                                    ))}
                                </ul>
                                <a href="#cta" className={`mt-8 block rounded-full py-3 text-center text-sm font-semibold transition-all hover:-translate-y-0.5 ${
                                    plan.highlighted
                                        ? 'bg-brand-600 text-white hover:bg-brand-700 hover:shadow-lg'
                                        : 'bg-ink-900 text-white hover:bg-ink-800 hover:shadow-lg'
                                }`}>
                                    Démarrer le pilote 3 mois
                                </a>
                            </div>
                        </Reveal>
                    ))}
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  FAQ
// ════════════════════════════════════════════════════════════════════════════

function FaqSection({ open, setOpen }: { open: number | null; setOpen: (v: number | null) => void }) {
    return (
        <section id="faq" className="bg-ink-50/40 px-5 py-24 sm:px-8 sm:py-28">
            <div className="mx-auto max-w-3xl">
                <Reveal className="text-center">
                    <span className="inline-block rounded-full bg-brand-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-widest text-brand-700 ring-1 ring-brand-100">
                        Questions fréquentes
                    </span>
                    <h2 className="mt-6 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl lg:text-5xl">
                        Tout ce que vous voulez{' '}
                        <span className="gradient-text">savoir.</span>
                    </h2>
                </Reveal>

                <div className="mt-12 space-y-3">
                    {FAQS.map((f, i) => {
                        const isOpen = open === i;
                        return (
                            <div key={f.q} className={`overflow-hidden rounded-2xl bg-white ring-1 transition-all ${
                                isOpen
                                    ? 'shadow-[0_8px_32px_rgba(15,23,42,.06)] ring-brand-200'
                                    : 'ring-ink-100 hover:ring-ink-200'
                            }`}>
                                <button type="button" onClick={() => setOpen(isOpen ? null : i)}
                                    className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
                                    aria-expanded={isOpen}>
                                    <span className="text-base font-semibold text-ink-900">{f.q}</span>
                                    <span className={`flex size-8 shrink-0 items-center justify-center rounded-full transition-all ${
                                        isOpen ? 'rotate-45 bg-brand-600 text-white' : 'bg-ink-50 text-ink-600'
                                    }`}>
                                        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
                                        </svg>
                                    </span>
                                </button>
                                <div className="grid transition-all duration-300 ease-out"
                                    style={{ gridTemplateRows: isOpen ? '1fr' : '0fr', opacity: isOpen ? 1 : 0 }}>
                                    <div className="overflow-hidden">
                                        <p className="px-6 pb-6 text-sm font-light leading-relaxed text-ink-600">{f.a}</p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

// ════════════════════════════════════════════════════════════════════════════
//  FINAL CTA
// ════════════════════════════════════════════════════════════════════════════

function FinalCta() {
    return (
        <section id="cta" className="bg-white px-5 py-20 sm:px-8 sm:py-24">
            <div className="mx-auto max-w-5xl">
                <div className="relative overflow-hidden rounded-[32px] p-10 sm:p-16"
                    style={{ background: 'linear-gradient(135deg, #082443 0%, #0B385E 50%, #0F4C81 100%)' }}>
                    <div className="grain" aria-hidden />
                    {/* Mesh orbs */}
                    <div aria-hidden className="pointer-events-none absolute -top-24 left-1/2 size-[640px] -translate-x-1/2 rounded-full"
                        style={{ background: 'radial-gradient(closest-side, rgba(21,101,172,.40), transparent 70%)', animation: 'meshMove 12s ease-in-out infinite' }} />
                    <div aria-hidden className="pointer-events-none absolute -bottom-32 -right-32 size-[420px] rounded-full"
                        style={{ background: 'radial-gradient(closest-side, rgba(63,150,112,.28), transparent 70%)', animation: 'meshMove2 14s ease-in-out infinite' }} />

                    <div className="relative text-center text-white">
                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-[11px] font-medium text-white/85 backdrop-blur">
                            <span className="size-1.5 rounded-full bg-sage-300" style={{ animation: 'pulse 2s infinite' }} />
                            Pilote gratuit · 3 mois · accompagnement inclus
                        </span>
                        <h2 className="mx-auto mt-6 max-w-2xl text-3xl font-bold tracking-tight text-white sm:text-4xl lg:text-5xl" style={{ lineHeight: 1.15 }}>
                            Prêt à structurer votre{' '}
                            <span className="gradient-text-light">démarche qualité</span> ?
                        </h2>
                        <p className="mx-auto mt-5 max-w-md text-base font-light leading-relaxed text-white/65">
                            Échangeons sur vos enjeux et ouvrons un environnement pilote pour vos coordinateurs.
                        </p>
                        <div className="mt-9 flex flex-wrap items-center justify-center gap-3">
                            <a href="mailto:contact@hsquality.fr"
                                className="btn-glow inline-flex items-center gap-2 rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-ink-900 transition-all hover:-translate-y-0.5 hover:shadow-2xl">
                                Demander une démo
                                <Arrow />
                            </a>
                            <Link href="/login"
                                className="inline-flex items-center gap-2 rounded-full border border-white/20 px-8 py-3.5 text-sm font-medium text-white/85 transition-colors hover:border-white/40 hover:text-white">
                                Déjà client ? Se connecter
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

