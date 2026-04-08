import { Head, Link } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';

// ─── Types ───────────────────────────────────────────────────────────────────
type Module = { icon: string; code: string; title: string; tag: string; desc: string; kpi: string };
type Profile = { abbr: string; title: string; items: string[]; accent: string };
type RoadmapPhase = { phase: string; period: string; modules: string[]; kpi: string; active: boolean };

export default function Welcome() {
    const [navSolid, setNavSolid]       = useState(false);
    const [mobileOpen, setMobileOpen]   = useState(false);
    const [activeModule, setActiveModule] = useState<number | null>(null);
    const [ticker, setTicker]           = useState(0);
    const heroRef = useRef<HTMLElement>(null);

    // Nav scroll
    useEffect(() => {
        const fn = () => setNavSolid(window.scrollY > 40);
        window.addEventListener('scroll', fn);
        return () => window.removeEventListener('scroll', fn);
    }, []);

    // Ticker animation
    useEffect(() => {
        const id = setInterval(() => setTicker(t => (t + 1) % 4), 2800);
        return () => clearInterval(id);
    }, []);

    // ─── Data ─────────────────────────────────────────────────────────────────
    const ticker_stats = [
        { value: '–15%', label: 'incidents déclarés dès An 1' },
        { value: '>60%', label: 'plans d\'amélioration complétés' },
        { value: '<5j',  label: 'délai traitement incident' },
        { value: '99,9%', label: 'disponibilité garantie' },
    ];

    const modules: Module[] = [
        {
            icon: '◈', code: '01',
            title: 'Traçabilité des interventions',
            tag: 'Cœur opérationnel',
            desc: 'Journal horodaté, pointage géolocalisé, voice-to-text, signature électronique. Chaque intervention est documentée, sécurisée et consultable en temps réel.',
            kpi: '100% des interventions tracées',
        },
        {
            icon: '◎', code: '02',
            title: 'Gestion des incidents',
            tag: 'Conformité HAS',
            desc: 'Déclaration mobile en moins de 2 min, algorithme de gravité automatique, analyse des causes (5 pourquoi / Ishikawa), notification ARS/CD pour les événements graves.',
            kpi: 'Délai traitement < 5 jours',
        },
        {
            icon: '◉', code: '03',
            title: 'Module QVCT',
            tag: 'Prévention RPS',
            desc: 'Baromètre anonyme hebdomadaire, détection automatique des signaux faibles, cartographie des risques par équipe, plan d\'actions QVCT avec suivi d\'impact.',
            kpi: 'Turnover cible –30%',
        },
        {
            icon: '◐', code: '04',
            title: 'Communication interne',
            tag: 'Anti-isolement',
            desc: 'Messagerie sécurisée, groupes d\'équipe, bibliothèque de protocoles, forum de bonnes pratiques, visioconférence intégrée. L\'intervenant n\'est plus seul.',
            kpi: '100% intervenants connectés',
        },
        {
            icon: '◑', code: '05',
            title: 'Compétences & Formation',
            tag: 'RH opérationnel',
            desc: 'Cartographie des habilitations, alertes d\'expiration, plan de formation individualisé, micro-learning, parcours onboarding, tableau de bord RH complet.',
            kpi: 'Taux de formation >80%',
        },
        {
            icon: '◒', code: '06',
            title: 'Audits & Conformité',
            tag: 'HAS · ISO · AFNOR',
            desc: 'Grilles HAS, ISO 9001, AFNOR NF X50-056, Caphandeo. Audit mobile sur site, score automatique, PAC généré depuis les écarts, préparation guidée à l\'évaluation externe.',
            kpi: 'Score conformité > 85%',
        },
        {
            icon: '◓', code: '07',
            title: 'Tableaux de bord & KPIs',
            tag: 'Pilotage temps réel',
            desc: 'Dashboard exécutif, suivi opérationnel coordinateurs, indicateurs qualité et QVCT, alertes sur seuils, exports PDF/Excel, rapport annuel qualité auto-généré.',
            kpi: 'Visibilité 360° en <3 sec',
        },
        {
            icon: '⬡', code: '08',
            title: 'Portail bénéficiaires',
            tag: 'Nouveau v2',
            desc: 'Accès au plan d\'accompagnement, historique des interventions, questionnaire de satisfaction, signalement direct d\'insatisfaction, messagerie avec le coordinateur.',
            kpi: 'NPS bénéficiaires >45',
        },
        {
            icon: '⬢', code: '09',
            title: 'IA & Analyse prédictive',
            tag: 'Premium',
            desc: 'Détection précoce des bénéficiaires à risque, prédiction burnout intervenants, suggestions d\'actions préventives, analyse sémantique des comptes-rendus.',
            kpi: 'Prévention avant réaction',
        },
    ];

    const profiles: Profile[] = [
        {
            abbr: 'INT', title: 'Intervenant à domicile',
            items: ['Pointage mobile géolocalisé', 'Déclaration incidents < 2 min', 'Accès plan d\'accompagnement', 'Mode hors-ligne (zone blanche)'],
            accent: '#F59E0B',
        },
        {
            abbr: 'CDR', title: 'Coordinateur de secteur',
            items: ['Dashboard temps réel', 'Gestion plannings équipe', 'Suivi QVCT intervenants', 'Alertes incidents graves'],
            accent: '#3B82F6',
        },
        {
            abbr: 'DIR', title: 'Dirigeant de structure',
            items: ['KPIs synthétiques exécutifs', 'Rapport annuel qualité auto', 'Vision multi-sites', 'Pilotage conformité HAS'],
            accent: '#8B5CF6',
        },
        {
            abbr: 'RQU', title: 'Référent qualité',
            items: ['Grilles d\'audit Caphandeo', 'Plans d\'amélioration continue', 'Reporting réglementaire', 'Préparation évaluation HAS'],
            accent: '#10B981',
        },
        {
            abbr: 'RH', title: 'Responsable formation',
            items: ['Cartographie compétences', 'Plan de formation', 'Alertes habilitations', 'Tableau de bord RH'],
            accent: '#EF4444',
        },
        {
            abbr: 'BNF', title: 'Bénéficiaire & famille',
            items: ['Plan d\'accompagnement', 'Historique interventions', 'Questionnaire satisfaction', 'Contact coordinateur'],
            accent: '#06B6D4',
        },
    ];

    const roadmap: RoadmapPhase[] = [
        { phase: 'Phase 1', period: 'M1 → M4', modules: ['Traçabilité', 'Incidents', 'Dashboard basique', 'App mobile offline'], kpi: '10 structures · 200 intervenants', active: true },
        { phase: 'Phase 2', period: 'M5 → M8', modules: ['QVCT complet', 'Audits HAS', 'Communication interne', 'Compétences basique'], kpi: '50 structures · 1 000 intervenants', active: false },
        { phase: 'Phase 3', period: 'M9 → M14', modules: ['IA prédictive', 'Portail bénéficiaires', 'Analyse sémantique', 'Benchmark sectoriel'], kpi: '200 structures', active: false },
        { phase: 'Phase 4', period: 'M15 → M24', modules: ['Expansion nationale', 'Connecteurs API', 'Licences CD/ARS', 'Partenariats institutionnels'], kpi: '500 structures', active: false },
        { phase: 'Phase 5', period: 'M25 → M36', modules: ['Belgique · Suisse · Luxembourg', 'Europe du Sud', 'Versions localisées', 'Conformité RGPD par pays'], kpi: '1 000 structures', active: false },
    ];

    const structures = ['SAAD', 'SSIAD', 'SPASAD', 'ESAD', 'CCAS', 'Mandataires'];

    return (
        <>
            <Head title="HS Quality — Pilotage Qualité & QVCT pour les services à domicile" />

            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap');

                * { box-sizing: border-box; }

                :root {
                    --navy:   #0B1628;
                    --navy2:  #0F1E38;
                    --navy3:  #162440;
                    --gold:   #F59E0B;
                    --gold2:  #FCD34D;
                    --gold3:  #FBBF24;
                    --cream:  #FEFCE8;
                    --slate:  #94A3B8;
                    --white:  #FFFFFF;
                    --border: rgba(255,255,255,0.07);
                    --border-light: #E2E8F0;
                }

                body { font-family: 'DM Sans', sans-serif; background: #fff; color: var(--navy); }

                .serif   { font-family: 'DM Serif Display', serif; }
                .mono    { font-family: 'DM Mono', monospace; }

                /* ─── Navbar ───── */
                .nav-solid { background: rgba(11,22,40,0.97) !important; backdrop-filter: blur(16px); border-bottom: 1px solid var(--border); }

                /* ─── Animations ───── */
                @keyframes fadeUp   { from { opacity:0; transform:translateY(28px) } to { opacity:1; transform:translateY(0) } }
                @keyframes fadeIn   { from { opacity:0 } to { opacity:1 } }
                @keyframes lineGrow { from { width:0 } to { width:100% } }
                @keyframes pulse    { 0%,100%{opacity:1} 50%{opacity:.4} }
                @keyframes tickSlide{ from{transform:translateY(100%);opacity:0} to{transform:translateY(0);opacity:1} }
                @keyframes tickOut  { from{transform:translateY(0);opacity:1} to{transform:translateY(-100%);opacity:0} }
                @keyframes float    { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
                @keyframes gridFade { from{opacity:0;transform:scale(.97)} to{opacity:1;transform:scale(1)} }

                .fade-up  { animation: fadeUp .7s ease both; }
                .fade-in  { animation: fadeIn .6s ease both; }
                .au-1     { animation-delay: .1s; }
                .au-2     { animation-delay: .22s; }
                .au-3     { animation-delay: .36s; }
                .au-4     { animation-delay: .50s; }
                .float    { animation: float 4s ease-in-out infinite; }

                /* ─── Diagonal section separator ───── */
                .diagonal-top::before {
                    content: '';
                    display: block;
                    height: 64px;
                    background: var(--navy);
                    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 0);
                    margin-bottom: -1px;
                }

                /* ─── Module card ───── */
                .mod-card {
                    position: relative;
                    background: var(--white);
                    border: 1px solid var(--border-light);
                    border-radius: 16px;
                    padding: 28px;
                    transition: all .22s ease;
                    overflow: hidden;
                    cursor: default;
                }
                .mod-card::before {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background: var(--navy);
                    transform: translateY(100%);
                    transition: transform .3s ease;
                    z-index: 0;
                }
                .mod-card:hover::before { transform: translateY(0); }
                .mod-card:hover { border-color: var(--gold); box-shadow: 0 20px 60px rgba(11,22,40,.15); }
                .mod-card > * { position: relative; z-index: 1; }

                .mod-icon {
                    font-size: 28px;
                    color: var(--gold);
                    transition: color .22s;
                    display: block;
                    margin-bottom: 12px;
                    line-height: 1;
                }

                .mod-code {
                    font-family: 'DM Mono', monospace;
                    font-size: 11px;
                    font-weight: 500;
                    color: #94A3B8;
                    letter-spacing: .08em;
                    transition: color .22s;
                }
                .mod-card:hover .mod-code { color: var(--gold); }

                .mod-title {
                    font-size: 15px;
                    font-weight: 700;
                    color: var(--navy);
                    margin: 6px 0 4px;
                    transition: color .22s;
                }
                .mod-card:hover .mod-title { color: var(--white); }

                .mod-tag {
                    display: inline-block;
                    font-size: 11px;
                    font-weight: 600;
                    background: #F1F5F9;
                    color: #475569;
                    padding: 3px 8px;
                    border-radius: 6px;
                    transition: background .22s, color .22s;
                }
                .mod-card:hover .mod-tag { background: rgba(245,158,11,.15); color: var(--gold3); }

                .mod-desc {
                    font-size: 13px;
                    color: #64748B;
                    line-height: 1.65;
                    margin-top: 12px;
                    transition: color .22s;
                }
                .mod-card:hover .mod-desc { color: rgba(255,255,255,.7); }

                .mod-kpi {
                    margin-top: 16px;
                    font-size: 12px;
                    font-weight: 700;
                    color: var(--gold);
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                .mod-kpi::before { content: '→'; }

                /* ─── Profile card ───── */
                .prof-card {
                    border: 1px solid var(--border-light);
                    border-radius: 16px;
                    padding: 24px;
                    background: white;
                    transition: transform .2s, box-shadow .2s, border-color .2s;
                }
                .prof-card:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 12px 40px rgba(11,22,40,.1);
                }

                /* ─── Ticker ───── */
                .ticker-item { animation: tickSlide .4s ease both; }
                .ticker-out  { animation: tickOut .4s ease both; }

                /* ─── Scrollbar ───── */
                ::-webkit-scrollbar { width: 6px; }
                ::-webkit-scrollbar-track { background: #f1f5f9; }
                ::-webkit-scrollbar-thumb { background: var(--gold); border-radius: 3px; }

                /* ─── Noise overlay ───── */
                .noise {
                    position: absolute; inset: 0; pointer-events: none; z-index: 1;
                    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
                    opacity: .06;
                }
            `}</style>

            <div style={{ minHeight: '100vh', background: '#fff', color: 'var(--navy)', fontFamily: "'DM Sans', sans-serif" }}>

                {/* ══════════════════════════════════════════════════════════
                    NAVBAR
                ══════════════════════════════════════════════════════════ */}
                <nav
                    className={`fixed top-0 left-0 right-0 z-50 transition-all duration-400 ${navSolid ? 'nav-solid' : ''}`}
                    style={{ padding: '0 0', background: navSolid ? undefined : 'transparent' }}
                >
                    <div style={{ maxWidth: 1280, margin: '0 auto', padding: '0 32px', height: 68, display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>

                        {/* Logo */}
                        <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                            <div style={{
                                width: 38, height: 38,
                                background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)',
                                borderRadius: 10,
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                fontFamily: "'DM Serif Display', serif",
                                fontWeight: 700, fontSize: 16, color: 'var(--navy)',
                                letterSpacing: '-0.02em',
                                flexShrink: 0,
                            }}>Q</div>
                            <div>
                                <div style={{ fontWeight: 800, fontSize: 16, letterSpacing: '-0.03em', color: navSolid ? 'white' : 'white', lineHeight: 1 }}>HS Quality</div>
                                <div style={{ fontSize: 10, fontWeight: 600, letterSpacing: '.12em', textTransform: 'uppercase', color: 'var(--gold)', lineHeight: 1, marginTop: 2 }}>Qualité & QVCT</div>
                            </div>
                        </div>

                        {/* Desktop links */}
                        <div style={{ display: 'flex', alignItems: 'center', gap: 36 }} className="hidden lg:flex">
                            {[['Modules', '#modules'], ['Utilisateurs', '#utilisateurs'], ['Feuille de route', '#roadmap'], ['Tarifs', '#tarifs']].map(([l, h]) => (
                                <a key={l} href={h} style={{ fontSize: 14, fontWeight: 500, color: 'rgba(255,255,255,.65)', textDecoration: 'none', transition: 'color .2s' }}
                                    onMouseEnter={e => (e.currentTarget.style.color = 'white')}
                                    onMouseLeave={e => (e.currentTarget.style.color = 'rgba(255,255,255,.65)')}
                                >{l}</a>
                            ))}
                        </div>

                        <div style={{ display: 'flex', gap: 12, alignItems: 'center' }} className="hidden lg:flex">
                            <a href="/login" style={{ fontSize: 13, fontWeight: 600, color: 'rgba(255,255,255,.6)', textDecoration: 'none', padding: '8px 16px' }}>Connexion</a>
                            <a href="/demo" style={{
                                fontSize: 13, fontWeight: 700, textDecoration: 'none',
                                background: 'var(--gold)', color: 'var(--navy)',
                                padding: '10px 22px', borderRadius: 10,
                                transition: 'background .2s, transform .15s',
                                display: 'inline-block',
                            }}
                                onMouseEnter={e => { (e.currentTarget as HTMLAnchorElement).style.background = 'var(--gold2)'; (e.currentTarget as HTMLAnchorElement).style.transform = 'translateY(-1px)'; }}
                                onMouseLeave={e => { (e.currentTarget as HTMLAnchorElement).style.background = 'var(--gold)'; (e.currentTarget as HTMLAnchorElement).style.transform = 'translateY(0)'; }}
                            >Demander une démo →</a>
                        </div>

                        {/* Mobile burger */}
                        <button onClick={() => setMobileOpen(!mobileOpen)} className="lg:hidden" style={{ background: 'transparent', border: 'none', cursor: 'pointer', padding: 8, color: 'white' }}>
                            <svg width={22} height={22} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                {mobileOpen
                                    ? <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    : <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />}
                            </svg>
                        </button>
                    </div>

                    {mobileOpen && (
                        <div style={{ background: 'var(--navy)', borderTop: '1px solid var(--border)', padding: '16px 32px 24px' }}>
                            {[['Modules', '#modules'], ['Utilisateurs', '#utilisateurs'], ['Feuille de route', '#roadmap'], ['Tarifs', '#tarifs']].map(([l, h]) => (
                                <a key={l} href={h} onClick={() => setMobileOpen(false)}
                                    style={{ display: 'block', fontSize: 15, color: 'rgba(255,255,255,.75)', padding: '10px 0', textDecoration: 'none' }}>{l}</a>
                            ))}
                            <a href="/demo" style={{
                                display: 'block', marginTop: 12, textAlign: 'center',
                                background: 'var(--gold)', color: 'var(--navy)',
                                padding: '12px', borderRadius: 10, fontWeight: 700, fontSize: 14, textDecoration: 'none',
                            }}>Demander une démo</a>
                        </div>
                    )}
                </nav>

                {/* ══════════════════════════════════════════════════════════
                    HERO
                ══════════════════════════════════════════════════════════ */}
                <section ref={heroRef} style={{ position: 'relative', background: 'var(--navy)', minHeight: '100vh', display: 'flex', flexDirection: 'column', overflow: 'hidden' }}>
                    <div className="noise" />

                    {/* Grid pattern */}
                    <div style={{
                        position: 'absolute', inset: 0, opacity: .04, zIndex: 0,
                        backgroundImage: 'linear-gradient(var(--border) 1px, transparent 1px), linear-gradient(90deg, var(--border) 1px, transparent 1px)',
                        backgroundSize: '72px 72px',
                    }} />

                    {/* Gold glow */}
                    <div style={{ position: 'absolute', top: '-20%', right: '-10%', width: 700, height: 700, borderRadius: '50%', background: 'radial-gradient(circle, rgba(245,158,11,.12) 0%, transparent 65%)', zIndex: 0 }} />
                    <div style={{ position: 'absolute', bottom: 0, left: '-5%', width: 500, height: 500, borderRadius: '50%', background: 'radial-gradient(circle, rgba(245,158,11,.06) 0%, transparent 65%)', zIndex: 0 }} />

                    {/* Main content */}
                    <div style={{ position: 'relative', zIndex: 2, flex: 1, display: 'flex', alignItems: 'center', maxWidth: 1280, margin: '0 auto', width: '100%', padding: '120px 32px 80px' }}>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: 72, alignItems: 'center', width: '100%' }}>

                            {/* Left column */}
                            <div>
                                {/* Badge */}
                                <div className="fade-up" style={{
                                    display: 'inline-flex', alignItems: 'center', gap: 8,
                                    background: 'rgba(245,158,11,.1)', border: '1px solid rgba(245,158,11,.25)',
                                    color: 'var(--gold)', fontSize: 11, fontWeight: 700, letterSpacing: '.1em',
                                    padding: '7px 14px', borderRadius: 100, marginBottom: 32,
                                    textTransform: 'uppercase',
                                }}>
                                    <span style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--gold)', animation: 'pulse 2s infinite' }} />
                                    SaaS · SAAD · SSIAD · SPASAD · Version 2.0
                                </div>

                                {/* Headline */}
                                <h1 className="serif fade-up au-1" style={{ fontSize: 'clamp(44px, 5.5vw, 72px)', lineHeight: 1.04, color: 'white', marginBottom: 28, fontWeight: 400 }}>
                                    La qualité<br />
                                    <em style={{ color: 'var(--gold)' }}>mesurée.</em><br />
                                    <span style={{ color: 'rgba(255,255,255,.35)', fontStyle: 'normal', fontSize: '.8em' }}>améliorée en continu.</span>
                                </h1>

                                <p className="fade-up au-2" style={{ fontSize: 17, color: 'rgba(255,255,255,.55)', lineHeight: 1.75, maxWidth: 520, fontWeight: 300, marginBottom: 44 }}>
                                    HS Quality structure le pilotage de la qualité et de la QVCT pour les structures d'aide et de soins à domicile — du terrain à la direction, en temps réel.
                                </p>

                                {/* CTAs */}
                                <div className="fade-up au-3" style={{ display: 'flex', flexWrap: 'wrap', gap: 14 }}>
                                    <a href="#modules" style={{
                                        display: 'inline-flex', alignItems: 'center', gap: 10,
                                        background: 'var(--gold)', color: 'var(--navy)',
                                        fontSize: 14, fontWeight: 700, padding: '14px 28px', borderRadius: 12,
                                        textDecoration: 'none', transition: 'all .2s',
                                    }}
                                        onMouseEnter={e => { (e.currentTarget as HTMLAnchorElement).style.background = '#FCD34D'; (e.currentTarget as HTMLAnchorElement).style.transform = 'translateY(-2px)'; }}
                                        onMouseLeave={e => { (e.currentTarget as HTMLAnchorElement).style.background = 'var(--gold)'; (e.currentTarget as HTMLAnchorElement).style.transform = 'translateY(0)'; }}
                                    >
                                        Découvrir les 9 modules
                                        <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                    </a>
                                    <a href="#demo" style={{
                                        display: 'inline-flex', alignItems: 'center', gap: 10,
                                        background: 'transparent', color: 'rgba(255,255,255,.8)',
                                        fontSize: 14, fontWeight: 500, padding: '14px 28px', borderRadius: 12,
                                        textDecoration: 'none', border: '1px solid rgba(255,255,255,.12)',
                                        transition: 'all .2s',
                                    }}
                                        onMouseEnter={e => { (e.currentTarget as HTMLAnchorElement).style.borderColor = 'rgba(255,255,255,.3)'; (e.currentTarget as HTMLAnchorElement).style.color = 'white'; }}
                                        onMouseLeave={e => { (e.currentTarget as HTMLAnchorElement).style.borderColor = 'rgba(255,255,255,.12)'; (e.currentTarget as HTMLAnchorElement).style.color = 'rgba(255,255,255,.8)'; }}
                                    >
                                        Essai gratuit 3 mois
                                    </a>
                                </div>

                                {/* Structures */}
                                <div className="fade-up au-4" style={{ marginTop: 52, paddingTop: 32, borderTop: '1px solid rgba(255,255,255,.07)' }}>
                                    <p style={{ fontSize: 11, fontWeight: 600, letterSpacing: '.1em', textTransform: 'uppercase', color: 'rgba(255,255,255,.3)', marginBottom: 14 }}>Conçu pour</p>
                                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
                                        {structures.map(s => (
                                            <span key={s} style={{
                                                fontSize: 12, fontWeight: 600, color: 'rgba(255,255,255,.5)',
                                                background: 'rgba(255,255,255,.05)', border: '1px solid rgba(255,255,255,.08)',
                                                padding: '5px 12px', borderRadius: 8,
                                            }}>{s}</span>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            {/* Right — Animated dashboard preview */}
                            <div className="float" style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>

                                {/* KPI ticker card */}
                                <div style={{
                                    background: 'rgba(255,255,255,.04)', border: '1px solid rgba(255,255,255,.08)',
                                    borderRadius: 20, padding: '28px 32px',
                                    backdropFilter: 'blur(12px)',
                                }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
                                        <p style={{ fontSize: 11, fontWeight: 700, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--gold)' }}>Indicateurs clés — An 1</p>
                                        <div style={{ width: 8, height: 8, borderRadius: '50%', background: '#4ADE80', animation: 'pulse 2s infinite' }} />
                                    </div>
                                    <div style={{ overflow: 'hidden', height: 64 }}>
                                        <div key={ticker} className="ticker-item" style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
                                            <div className="mono" style={{ fontSize: 44, fontWeight: 500, color: 'white', lineHeight: 1 }}>{ticker_stats[ticker].value}</div>
                                            <div style={{ fontSize: 13, color: 'rgba(255,255,255,.45)' }}>{ticker_stats[ticker].label}</div>
                                        </div>
                                    </div>
                                </div>

                                {/* Progress bars */}
                                <div style={{
                                    background: 'rgba(255,255,255,.04)', border: '1px solid rgba(255,255,255,.08)',
                                    borderRadius: 20, padding: '24px 28px',
                                }}>
                                    <p style={{ fontSize: 11, fontWeight: 700, letterSpacing: '.1em', textTransform: 'uppercase', color: 'rgba(255,255,255,.3)', marginBottom: 20 }}>Objectifs satisfaction</p>
                                    {[
                                        { label: 'NPS utilisateurs cible', pct: 73, color: 'var(--gold)' },
                                        { label: 'Plans d\'amélioration complétés', pct: 60, color: '#60A5FA' },
                                        { label: 'Réduction incidents', pct: 85, color: '#4ADE80' },
                                    ].map(({ label, pct, color }) => (
                                        <div key={label} style={{ marginBottom: 14 }}>
                                            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: 6 }}>
                                                <span style={{ fontSize: 12, color: 'rgba(255,255,255,.5)' }}>{label}</span>
                                                <span className="mono" style={{ fontSize: 12, color, fontWeight: 500 }}>{pct}%</span>
                                            </div>
                                            <div style={{ height: 4, background: 'rgba(255,255,255,.07)', borderRadius: 4, overflow: 'hidden' }}>
                                                <div style={{ height: '100%', width: `${pct}%`, background: color, borderRadius: 4, transition: 'width 1.5s ease' }} />
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Pill tags */}
                                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
                                    {['Offline-first', 'HDS certifié', 'Multi-tenant', 'TLS 1.3 + AES-256', 'RPO < 1h'].map(tag => (
                                        <span key={tag} style={{
                                            fontSize: 11, fontWeight: 600, color: 'rgba(255,255,255,.45)',
                                            background: 'rgba(255,255,255,.04)', border: '1px solid rgba(255,255,255,.08)',
                                            padding: '6px 12px', borderRadius: 100,
                                        }}>{tag}</span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Bottom strip — key numbers */}
                    <div style={{ position: 'relative', zIndex: 2, borderTop: '1px solid rgba(255,255,255,.06)' }}>
                        <div style={{ maxWidth: 1280, margin: '0 auto', padding: '0 32px', display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)' }}>
                            {[
                                { v: '50', u: 'structures', sub: 'cible An 1' },
                                { v: '1 000', u: 'intervenants', sub: 'actifs sur la plateforme' },
                                { v: '180 K', u: 'euros ARR', sub: 'revenu récurrent An 1' },
                                { v: '9', u: 'modules', sub: 'dont IA prédictive' },
                            ].map(({ v, u, sub }, i) => (
                                <div key={i} style={{ padding: '24px 20px', borderRight: i < 3 ? '1px solid rgba(255,255,255,.06)' : 'none' }}>
                                    <div style={{ display: 'flex', alignItems: 'baseline', gap: 6 }}>
                                        <span className="serif" style={{ fontSize: 32, color: 'white', fontWeight: 400 }}>{v}</span>
                                        <span style={{ fontSize: 14, color: 'var(--gold)', fontWeight: 600 }}>{u}</span>
                                    </div>
                                    <div style={{ fontSize: 12, color: 'rgba(255,255,255,.3)', marginTop: 2 }}>{sub}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════════════
                    TRUST BAR
                ══════════════════════════════════════════════════════════ */}
                <div style={{ background: '#FAFAFA', borderBottom: '1px solid #F1F5F9', padding: '18px 32px' }}>
                    <div style={{ maxWidth: 1280, margin: '0 auto', display: 'flex', alignItems: 'center', gap: 32, flexWrap: 'wrap' }}>
                        <span style={{ fontSize: 11, fontWeight: 700, letterSpacing: '.1em', textTransform: 'uppercase', color: '#CBD5E1', whiteSpace: 'nowrap' }}>Référentiels intégrés</span>
                        <div style={{ display: 'flex', gap: 28, flexWrap: 'wrap', alignItems: 'center' }}>
                            {['HAS Évaluation externe', 'AFNOR NF X50-056', 'ISO 9001', 'Caphandeo', 'RGPD', 'HDS France', 'Code du travail — RPS'].map(o => (
                                <span key={o} style={{ fontSize: 13, fontWeight: 600, color: '#94A3B8' }}>{o}</span>
                            ))}
                        </div>
                    </div>
                </div>

                {/* ══════════════════════════════════════════════════════════
                    MODULES — 9 modules grid
                ══════════════════════════════════════════════════════════ */}
                <section id="modules" style={{ padding: '96px 32px', background: '#fff' }}>
                    <div style={{ maxWidth: 1280, margin: '0 auto' }}>

                        {/* Section header */}
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 16, marginBottom: 64, paddingBottom: 48, borderBottom: '1px solid #F1F5F9' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                <div style={{ width: 32, height: 2, background: 'var(--gold)' }} />
                                <span style={{ fontSize: 11, fontWeight: 700, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--gold)' }}>Modules fonctionnels</span>
                            </div>
                            <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'space-between', gap: 24, alignItems: 'flex-end' }}>
                                <h2 className="serif" style={{ fontSize: 'clamp(36px, 4vw, 56px)', lineHeight: 1.08, color: 'var(--navy)', fontWeight: 400 }}>
                                    9 modules.<br /><em style={{ color: 'var(--gold)' }}>Une démarche complète.</em>
                                </h2>
                                <p style={{ fontSize: 16, color: '#64748B', maxWidth: 400, lineHeight: 1.7, fontWeight: 300 }}>
                                    De la traçabilité terrain à l'intelligence artificielle prédictive — tout est pensé pour le secteur des services à domicile.
                                </p>
                            </div>
                        </div>

                        {/* 3-column grid */}
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 16 }}>
                            {modules.map((m, i) => (
                                <div
                                    key={i}
                                    className="mod-card"
                                    onMouseEnter={() => setActiveModule(i)}
                                    onMouseLeave={() => setActiveModule(null)}
                                >
                                    <span className="mod-icon">{m.icon}</span>
                                    <span className="mod-code">{m.code}</span>
                                    <h3 className="mod-title">{m.title}</h3>
                                    <span className="mod-tag">{m.tag}</span>
                                    <p className="mod-desc">{m.desc}</p>
                                    <div className="mod-kpi">{m.kpi}</div>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════════════
                    UTILISATEURS — 6 profiles
                ══════════════════════════════════════════════════════════ */}
                <section id="utilisateurs" style={{ padding: '96px 32px', background: 'var(--navy)' }}>
                    <div style={{ maxWidth: 1280, margin: '0 auto' }}>

                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(340px, 1fr))', gap: 64, alignItems: 'start', marginBottom: 64 }}>
                            <div>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 20 }}>
                                    <div style={{ width: 32, height: 2, background: 'var(--gold)' }} />
                                    <span style={{ fontSize: 11, fontWeight: 700, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--gold)' }}>Qui en bénéficie</span>
                                </div>
                                <h2 className="serif" style={{ fontSize: 'clamp(34px, 3.5vw, 52px)', lineHeight: 1.08, color: 'white', fontWeight: 400, marginBottom: 20 }}>
                                    6 profils.<br /><em style={{ color: 'var(--gold)' }}>Un seul outil.</em>
                                </h2>
                                <p style={{ fontSize: 16, color: 'rgba(255,255,255,.45)', lineHeight: 1.75, fontWeight: 300, maxWidth: 400 }}>
                                    Chaque acteur de la structure dispose d'un espace adapté à ses responsabilités, sur mobile ou sur web.
                                </p>
                            </div>
                            {/* Highlighted stat */}
                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16 }}>
                                {[
                                    { n: '> 40', l: 'NPS utilisateurs cible', u: 'An 1' },
                                    { n: '> 55', l: 'NPS utilisateurs cible', u: 'An 3' },
                                    { n: '< 2s', l: 'Chargement app mobile', u: '4G' },
                                    { n: '99.9%', l: 'Disponibilité garantie', u: 'SLA' },
                                ].map(({ n, l, u }, i) => (
                                    <div key={i} style={{ background: 'rgba(255,255,255,.04)', border: '1px solid rgba(255,255,255,.08)', borderRadius: 16, padding: '20px 22px' }}>
                                        <div className="serif" style={{ fontSize: 30, color: 'var(--gold)', fontWeight: 400 }}>{n}</div>
                                        <div style={{ fontSize: 12, color: 'rgba(255,255,255,.4)', marginTop: 4, lineHeight: 1.4 }}>{l}</div>
                                        <div className="mono" style={{ fontSize: 10, color: 'rgba(255,255,255,.2)', marginTop: 4 }}>{u}</div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Profile cards grid */}
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: 16 }}>
                            {profiles.map((p, i) => (
                                <div key={i} style={{
                                    background: 'rgba(255,255,255,.04)', border: '1px solid rgba(255,255,255,.07)',
                                    borderRadius: 16, padding: '24px',
                                    transition: 'background .2s, border-color .2s',
                                }}
                                    onMouseEnter={e => { (e.currentTarget as HTMLDivElement).style.background = 'rgba(255,255,255,.08)'; (e.currentTarget as HTMLDivElement).style.borderColor = 'rgba(255,255,255,.15)'; }}
                                    onMouseLeave={e => { (e.currentTarget as HTMLDivElement).style.background = 'rgba(255,255,255,.04)'; (e.currentTarget as HTMLDivElement).style.borderColor = 'rgba(255,255,255,.07)'; }}
                                >
                                    <div style={{
                                        display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                                        width: 44, height: 44, borderRadius: 12,
                                        background: p.accent + '22', border: `1px solid ${p.accent}44`,
                                        fontSize: 12, fontWeight: 800, color: p.accent,
                                        fontFamily: "'DM Mono', monospace", letterSpacing: '.03em',
                                        marginBottom: 16,
                                    }}>{p.abbr}</div>
                                    <h4 style={{ fontSize: 14, fontWeight: 700, color: 'white', marginBottom: 14 }}>{p.title}</h4>
                                    <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'flex', flexDirection: 'column', gap: 7 }}>
                                        {p.items.map((item, j) => (
                                            <li key={j} style={{ display: 'flex', alignItems: 'flex-start', gap: 8, fontSize: 12, color: 'rgba(255,255,255,.45)', lineHeight: 1.4 }}>
                                                <span style={{ width: 5, height: 5, borderRadius: '50%', background: p.accent, marginTop: 5, flexShrink: 0 }} />
                                                {item}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════════════
                    ROADMAP
                ══════════════════════════════════════════════════════════ */}
                <section id="roadmap" style={{ padding: '96px 32px', background: '#FAFAFA' }}>
                    <div style={{ maxWidth: 1280, margin: '0 auto' }}>

                        <div style={{ textAlign: 'center', marginBottom: 64 }}>
                            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 10, marginBottom: 16 }}>
                                <div style={{ width: 32, height: 2, background: 'var(--gold)' }} />
                                <span style={{ fontSize: 11, fontWeight: 700, letterSpacing: '.1em', textTransform: 'uppercase', color: 'var(--gold)' }}>Feuille de route</span>
                                <div style={{ width: 32, height: 2, background: 'var(--gold)' }} />
                            </div>
                            <h2 className="serif" style={{ fontSize: 'clamp(34px, 3.5vw, 52px)', color: 'var(--navy)', fontWeight: 400 }}>
                                5 phases.<br /><em>Du MVP à la rentabilité.</em>
                            </h2>
                        </div>

                        {/* Timeline */}
                        <div style={{ position: 'relative' }}>
                            <div style={{ position: 'absolute', top: 24, left: 0, right: 0, height: 2, background: '#E2E8F0', zIndex: 0 }} className="hidden lg:block" />
                            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(220px, 1fr))', gap: 16 }}>
                                {roadmap.map((r, i) => (
                                    <div key={i} style={{ position: 'relative' }}>
                                        {/* Dot */}
                                        <div className="hidden lg:flex" style={{ justifyContent: 'center', marginBottom: 0, position: 'relative' }}>
                                            <div style={{
                                                width: 16, height: 16, borderRadius: '50%',
                                                background: r.active ? 'var(--gold)' : 'white',
                                                border: r.active ? '3px solid var(--gold)' : '2px solid #CBD5E1',
                                                position: 'relative', zIndex: 1,
                                            }} />
                                        </div>
                                        {/* Card */}
                                        <div style={{
                                            marginTop: 20, padding: '20px',
                                            background: r.active ? 'var(--navy)' : 'white',
                                            border: `1px solid ${r.active ? 'var(--gold)' : '#E2E8F0'}`,
                                            borderRadius: 16,
                                            boxShadow: r.active ? '0 8px 32px rgba(245,158,11,.12)' : 'none',
                                            transition: 'all .2s',
                                        }}>
                                            <div style={{ fontSize: 11, fontWeight: 700, letterSpacing: '.08em', textTransform: 'uppercase', color: r.active ? 'var(--gold)' : '#94A3B8', marginBottom: 6 }}>{r.phase}</div>
                                            <div className="mono" style={{ fontSize: 12, color: r.active ? 'rgba(255,255,255,.5)' : '#94A3B8', marginBottom: 12 }}>{r.period}</div>
                                            <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 14px', display: 'flex', flexDirection: 'column', gap: 6 }}>
                                                {r.modules.map((m, j) => (
                                                    <li key={j} style={{ fontSize: 12, color: r.active ? 'rgba(255,255,255,.7)' : '#475569', display: 'flex', alignItems: 'center', gap: 6 }}>
                                                        <span style={{ width: 4, height: 4, borderRadius: '50%', background: r.active ? 'var(--gold)' : '#CBD5E1', flexShrink: 0 }} />
                                                        {m}
                                                    </li>
                                                ))}
                                            </ul>
                                            <div style={{ fontSize: 11, fontWeight: 700, color: r.active ? 'var(--gold)' : '#94A3B8', borderTop: `1px solid ${r.active ? 'rgba(245,158,11,.2)' : '#F1F5F9'}`, paddingTop: 12 }}>{r.kpi}</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════════════
                    PRICING — 3 tiers
                ══════════════════════════════════════════════════════════ */}
                

                {/* ══════════════════════════════════════════════════════════
                    CTA FINAL
                ══════════════════════════════════════════════════════════ */}
                <section id="demo" style={{ padding: '96px 32px', background: '#FAFAFA' }}>
                    <div style={{ maxWidth: 960, margin: '0 auto' }}>
                        <div style={{
                            background: 'var(--navy)', borderRadius: 28,
                            padding: 'clamp(48px, 6vw, 80px)',
                            textAlign: 'center', position: 'relative', overflow: 'hidden',
                        }}>
                            <div className="noise" />
                            <div style={{ position: 'absolute', top: '-30%', left: '50%', transform: 'translateX(-50%)', width: 600, height: 600, borderRadius: '50%', background: 'radial-gradient(circle, rgba(245,158,11,.12) 0%, transparent 60%)', zIndex: 0 }} />
                            <div style={{ position: 'relative', zIndex: 2 }}>
                                <div style={{
                                    display: 'inline-flex', alignItems: 'center', gap: 8,
                                    background: 'rgba(245,158,11,.1)', border: '1px solid rgba(245,158,11,.25)',
                                    color: 'var(--gold)', fontSize: 11, fontWeight: 700, letterSpacing: '.1em',
                                    padding: '7px 14px', borderRadius: 100, marginBottom: 32, textTransform: 'uppercase',
                                }}>
                                    <span style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--gold)' }} />
                                    Essai pilote gratuit · 3 mois · Accompagnement inclus
                                </div>
                                <h2 className="serif" style={{ fontSize: 'clamp(36px, 4.5vw, 60px)', color: 'white', fontWeight: 400, lineHeight: 1.08, marginBottom: 20 }}>
                                    Prêt à piloter<br /><em style={{ color: 'var(--gold)' }}>votre qualité ?</em>
                                </h2>
                                <p style={{ fontSize: 17, color: 'rgba(255,255,255,.45)', maxWidth: 480, margin: '0 auto 44px', lineHeight: 1.7, fontWeight: 300 }}>
                                    Rejoignez les premières structures médico-sociales qui transforment leur démarche qualité avec HS Quality.
                                </p>
                                <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'center', gap: 14 }}>
                                    <a href="#" style={{
                                        display: 'inline-flex', alignItems: 'center', gap: 10,
                                        background: 'var(--gold)', color: 'var(--navy)',
                                        fontSize: 15, fontWeight: 700, padding: '16px 36px', borderRadius: 14,
                                        textDecoration: 'none', transition: 'all .2s',
                                    }}
                                        onMouseEnter={e => { (e.currentTarget as HTMLAnchorElement).style.background = '#FCD34D'; (e.currentTarget as HTMLAnchorElement).style.transform = 'translateY(-2px)'; }}
                                        onMouseLeave={e => { (e.currentTarget as HTMLAnchorElement).style.background = 'var(--gold)'; (e.currentTarget as HTMLAnchorElement).style.transform = 'translateY(0)'; }}
                                    >
                                        Démarrer le pilote gratuit
                                        <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                                    </a>
                                    <a href="#" style={{
                                        display: 'inline-flex', alignItems: 'center', gap: 8,
                                        color: 'rgba(255,255,255,.6)', fontSize: 15, fontWeight: 500,
                                        border: '1px solid rgba(255,255,255,.12)', padding: '16px 32px', borderRadius: 14,
                                        textDecoration: 'none', transition: 'all .2s',
                                    }}
                                        onMouseEnter={e => { (e.currentTarget as HTMLAnchorElement).style.color = 'white'; (e.currentTarget as HTMLAnchorElement).style.borderColor = 'rgba(255,255,255,.3)'; }}
                                        onMouseLeave={e => { (e.currentTarget as HTMLAnchorElement).style.color = 'rgba(255,255,255,.6)'; (e.currentTarget as HTMLAnchorElement).style.borderColor = 'rgba(255,255,255,.12)'; }}
                                    >
                                        Parler à un expert
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════════════
                    FOOTER
                ══════════════════════════════════════════════════════════ */}
                <footer style={{ background: 'white', borderTop: '1px solid #F1F5F9', padding: '64px 32px 32px' }}>
                    <div style={{ maxWidth: 1280, margin: '0 auto' }}>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 48, paddingBottom: 48, borderBottom: '1px solid #F1F5F9' }}>

                            {/* Brand */}
                            <div style={{ gridColumn: 'span 2' }} className="md:col-span-2">
                                <div style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 16 }}>
                                    <div style={{
                                        width: 36, height: 36, background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)',
                                        borderRadius: 10, display: 'flex', alignItems: 'center', justifyContent: 'center',
                                        fontFamily: "'DM Serif Display', serif", fontWeight: 700, fontSize: 16, color: 'var(--navy)',
                                    }}>Q</div>
                                    <div>
                                        <div style={{ fontWeight: 800, fontSize: 15, color: 'var(--navy)', lineHeight: 1 }}>HS Quality</div>
                                        <div style={{ fontSize: 10, fontWeight: 600, letterSpacing: '.12em', textTransform: 'uppercase', color: 'var(--gold)', lineHeight: 1, marginTop: 3 }}>Qualité & QVCT · Services à domicile</div>
                                    </div>
                                </div>
                                <p style={{ fontSize: 13, color: '#94A3B8', maxWidth: 280, lineHeight: 1.7, fontWeight: 300, marginBottom: 20 }}>
                                    Le pilotage de la qualité et de la QVCT pour les structures médico-sociales d'aide et de soins à domicile.
                                </p>
                                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
                                    {['RGPD', 'HDS', 'HAS', 'ISO 9001', 'AFNOR NF X50-056'].map(b => (
                                        <span key={b} style={{ fontSize: 11, fontWeight: 600, color: '#94A3B8', border: '1px solid #E2E8F0', padding: '4px 10px', borderRadius: 6 }}>{b}</span>
                                    ))}
                                </div>
                            </div>

                            {[
                                { h: 'Plateforme', links: ['9 modules fonctionnels', 'Module IA prédictive', 'Application mobile', 'API & connecteurs', 'Mode offline-first'] },
                                { h: 'Conformité', links: ['Grilles HAS', 'AFNOR NF X50-056', 'ISO 9001', 'Caphandeo', 'Évaluation externe'] },
                                { h: 'Ressources', links: ['Documentation', 'Conformité RGPD/HDS', 'Support technique', 'Formations', 'Contact commercial'] },
                            ].map(col => (
                                <div key={col.h}>
                                    <h5 style={{ fontWeight: 700, fontSize: 13, color: 'var(--navy)', marginBottom: 16, letterSpacing: '.02em' }}>{col.h}</h5>
                                    <ul style={{ listStyle: 'none', padding: 0, margin: 0, display: 'flex', flexDirection: 'column', gap: 10 }}>
                                        {col.links.map(l => (
                                            <li key={l}>
                                                <a href="#" style={{ fontSize: 13, color: '#94A3B8', textDecoration: 'none', fontWeight: 300, transition: 'color .15s' }}
                                                    onMouseEnter={e => (e.currentTarget.style.color = 'var(--gold)')}
                                                    onMouseLeave={e => (e.currentTarget.style.color = '#94A3B8')}
                                                >{l}</a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>

                        <div style={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'space-between', alignItems: 'center', gap: 16, paddingTop: 24 }}>
                            <p style={{ fontSize: 12, color: '#CBD5E1' }}>© 2024 HS Quality. Tous droits réservés. · CDC-QUALITE-DOM-2024-v2.0</p>
                            <div style={{ display: 'flex', gap: 24 }}>
                                {['Politique de confidentialité', 'CGU', 'Mentions légales', 'Accessibilité'].map(l => (
                                    <a key={l} href="#" style={{ fontSize: 12, color: '#CBD5E1', textDecoration: 'none', transition: 'color .15s' }}
                                        onMouseEnter={e => (e.currentTarget.style.color = 'var(--gold)')}
                                        onMouseLeave={e => (e.currentTarget.style.color = '#CBD5E1')}
                                    >{l}</a>
                                ))}
                            </div>
                        </div>
                    </div>
                </footer>

            </div>
        </>
    );
}