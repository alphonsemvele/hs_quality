import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head, Link } from '@inertiajs/react';

// ────────────────────────────────────────────────────────────────────────────
//  Data — testimonials & case-study highlights
// ────────────────────────────────────────────────────────────────────────────

interface Testimonial {
    quote: string;
    author: string;
    role: string;
    organization: string;
    badge: string;
    metrics: { label: string; value: string }[];
    tone: 'brand' | 'sage' | 'warning';
}

const TESTIMONIALS: Testimonial[] = [
    {
        quote:
            "Avant HS Quality, nos comptes-rendus d'intervention dormaient dans des classeurs. Aujourd'hui, je prépare ma visite HAS en trois clics : grille AFNOR, plans d'action, preuves. La référente HAS nous a même félicités pour la traçabilité.",
        author: 'Sophie Martin',
        role: 'Dirigeante',
        organization: 'SAAD Pays-de-Loire (148 intervenants)',
        badge: 'Pilote 2025 — Visite HAS réussie',
        metrics: [
            { label: 'Score HAS', value: '92 %' },
            { label: 'Préparation visite', value: '−65 % de temps' },
            { label: 'Incidents tracés', value: '× 4' },
        ],
        tone: 'brand',
    },
    {
        quote:
            "Nos aides à domicile râlaient sur les feuilles de route papier. Le mobile offline a tout changé : check-in GPS, photos, signature, fin de la double-saisie en agence. Le taux de complétion des CR est passé de 64 % à 97 % en deux mois.",
        author: 'Thomas Dupont',
        role: 'Coordinateur de secteur',
        organization: 'SSIAD Auvergne (52 infirmiers)',
        badge: 'Déploiement Q4 2025',
        metrics: [
            { label: 'Complétion CR', value: '64 % → 97 %' },
            { label: 'Double-saisie', value: 'supprimée' },
            { label: 'Mobile NPS', value: '+58' },
        ],
        tone: 'sage',
    },
    {
        quote:
            "Le baromètre QVCT a révélé deux signaux faibles que nous avions ratés : isolement des nouvelles recrues, charge perçue trop élevée en zone rurale. On a corrigé en six semaines. Le turnover a baissé de 19 % sur l'année.",
        author: 'Claire Bernard',
        role: 'Référente qualité & QVCT',
        organization: 'SPASAD Bretagne (210 salariés)',
        badge: 'Démarche QVCT certifiée 2025',
        metrics: [
            { label: 'Turnover', value: '−19 %' },
            { label: 'Signaux faibles', value: '14 traités' },
            { label: 'Score QVCT', value: '6,8 → 8,1 / 10' },
        ],
        tone: 'sage',
    },
    {
        quote:
            "On était sceptiques sur la conformité RGPD pour un CCAS. L'hébergement HDS en France, l'audit trail complet et le DPO partagé ont rassuré notre conseil municipal. La CNIL a validé notre AIPD sans réserve.",
        author: 'Marc Lefèvre',
        role: 'Directeur',
        organization: 'CCAS Nouvelle-Aquitaine (38 agents)',
        badge: 'AIPD CNIL validée',
        metrics: [
            { label: 'AIPD', value: 'validée' },
            { label: 'Audits accès', value: '100 % tracés' },
            { label: 'Conformité RGPD', value: 'top tier' },
        ],
        tone: 'brand',
    },
    {
        quote:
            "L'équipe terrain a adopté l'app en deux semaines — plus rapide que notre précédent outil métier. La messagerie interne a tué le WhatsApp pro et redonné la main aux responsables de secteur. Énorme gain de cadrage.",
        author: 'Émilie Roux',
        role: 'Responsable RH',
        organization: 'SAAD ESAD Hauts-de-France (95 intervenants)',
        badge: 'Pilote 2026',
        metrics: [
            { label: 'Adoption mobile', value: '14 jours' },
            { label: 'Tickets support', value: '−42 %' },
            { label: 'WhatsApp pro', value: 'éteint' },
        ],
        tone: 'warning',
    },
    {
        quote:
            "Le plan d'amélioration continue est la fonctionnalité que j'attendais depuis dix ans. Chaque action a un responsable, une échéance, une preuve. Plus de PAC théorique qui finit au placard — on est vraiment dans une dynamique mesurée.",
        author: 'Nathalie Dubois',
        role: 'Référente qualité',
        organization: 'Réseau associatif Île-de-France (320 salariés)',
        badge: 'Multi-sites · 4 structures',
        metrics: [
            { label: 'Actions PAC', value: '86 ouvertes / 78 closes' },
            { label: 'Délai moyen', value: '21 jours' },
            { label: 'Taux de réalisation', value: '91 %' },
        ],
        tone: 'brand',
    },
];

interface ProofStat {
    label: string;
    value: string;
    sub: string;
}

const PROOF: ProofStat[] = [
    { label: 'Structures équipées', value: '47', sub: 'SAAD · SSIAD · SPASAD · CCAS' },
    { label: 'Intervenants actifs', value: '2 850+', sub: 'sur le mobile offline' },
    { label: 'Bénéficiaires suivis', value: '11 200', sub: 'données chiffrées HDS' },
    { label: 'NPS moyen', value: '+62', sub: 'mesuré trimestriellement' },
];

// ────────────────────────────────────────────────────────────────────────────
//  Component
// ────────────────────────────────────────────────────────────────────────────

const TONE_CLASSES: Record<Testimonial['tone'], { bg: string; ring: string; badge: string; metricLabel: string }> = {
    brand: {
        bg: 'bg-gradient-to-br from-brand-50/60 to-white',
        ring: 'ring-1 ring-brand-100',
        badge: 'bg-brand-100 text-brand-700',
        metricLabel: 'text-brand-700',
    },
    sage: {
        bg: 'bg-gradient-to-br from-sage-50/60 to-white',
        ring: 'ring-1 ring-sage-100',
        badge: 'bg-sage-100 text-sage-700',
        metricLabel: 'text-sage-700',
    },
    warning: {
        bg: 'bg-gradient-to-br from-warning-50/60 to-white',
        ring: 'ring-1 ring-warning-100',
        badge: 'bg-warning-100 text-warning-700',
        metricLabel: 'text-warning-700',
    },
};

function initials(name: string): string {
    return name
        .split(' ')
        .map((p) => p[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export default function Clients() {
    return (
        <MarketingPage>
            <Head title="Clients · HS Quality">
                <meta
                    name="description"
                    content="47 structures médico-sociales d'aide et de soins à domicile témoignent : SAAD, SSIAD, SPASAD, CCAS — comment HS Quality a transformé leur pilotage qualité et leur QVCT."
                />
                <meta property="og:title" content="Témoignages clients · HS Quality" />
                <meta
                    property="og:description"
                    content="Retours d'expérience de 47 structures pilotes : SAAD, SSIAD, SPASAD, CCAS. Mesures concrètes d'impact sur la qualité et la QVCT."
                />
                <meta property="og:type" content="website" />
                <meta property="og:locale" content="fr_FR" />
                <meta name="twitter:card" content="summary_large_image" />
            </Head>

            {/* ───── Hero ───── */}
            <section className="border-b border-ink-100 bg-gradient-to-b from-brand-50/60 to-white px-5 py-20 sm:px-8 sm:py-28">
                <div className="mx-auto max-w-4xl text-center">
                    <span className="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/80 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-brand-700">
                        <span className="size-1.5 rounded-full bg-sage-500" />
                        47 structures — 2 850 intervenants actifs
                    </span>
                    <h1 className="mt-6 text-4xl font-bold tracking-tight text-ink-900 sm:text-5xl lg:text-6xl">
                        Ils pilotent leur qualité{' '}
                        <span className="gradient-text">avec HS Quality</span>
                    </h1>
                    <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink-600">
                        Du SAAD rural au groupe associatif multi-sites, des structures qui font tourner leur démarche
                        qualité, leur QVCT et leur conformité HAS sans alourdir le travail terrain.
                    </p>
                </div>
            </section>

            {/* ───── Proof strip ───── */}
            <section className="border-b border-ink-100 bg-ink-50/40 px-5 py-12 sm:px-8">
                <div className="mx-auto grid max-w-6xl gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {PROOF.map((stat) => (
                        <div key={stat.label} className="rounded-2xl border border-ink-100 bg-white p-6 text-center">
                            <p className="font-mono text-3xl font-bold tracking-tight text-ink-900">{stat.value}</p>
                            <p className="mt-1 text-xs font-semibold uppercase tracking-wider text-brand-600">{stat.label}</p>
                            <p className="mt-2 text-xs leading-relaxed text-ink-500">{stat.sub}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* ───── Testimonials grid ───── */}
            <section className="px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-6xl">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
                            Témoignages terrain
                        </h2>
                        <p className="mt-4 text-base leading-relaxed text-ink-600">
                            Dirigeants, coordinateurs, qualité et RH — six retours d'expérience publiés avec leur accord, sans
                            ghost-writing ni cosmétique.
                        </p>
                    </div>

                    <div className="mt-12 grid gap-6 lg:grid-cols-2">
                        {TESTIMONIALS.map((t) => {
                            const tone = TONE_CLASSES[t.tone];
                            return (
                                <article
                                    key={t.author}
                                    className={'flex flex-col rounded-2xl border border-ink-100 p-7 ' + tone.bg + ' ' + tone.ring}
                                >
                                    <span
                                        className={
                                            'inline-flex w-fit items-center gap-2 rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ' +
                                            tone.badge
                                        }
                                    >
                                        <span className="size-1.5 rounded-full bg-current" />
                                        {t.badge}
                                    </span>

                                    <blockquote className="mt-5 flex-1 text-base leading-relaxed text-ink-700">
                                        <QuoteIcon />
                                        <p className="mt-1.5">{t.quote}</p>
                                    </blockquote>

                                    <div className="mt-6 grid grid-cols-3 gap-3 border-t border-ink-100 pt-5">
                                        {t.metrics.map((m) => (
                                            <div key={m.label} className="text-center">
                                                <p className={'font-mono text-lg font-bold tracking-tight ' + tone.metricLabel}>{m.value}</p>
                                                <p className="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-ink-500">
                                                    {m.label}
                                                </p>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="mt-6 flex items-center gap-3">
                                        <div className="flex size-10 items-center justify-center rounded-full bg-white text-sm font-bold text-ink-700 shadow-sm">
                                            {initials(t.author)}
                                        </div>
                                        <div>
                                            <p className="text-sm font-semibold text-ink-900">{t.author}</p>
                                            <p className="text-xs text-ink-500">
                                                {t.role} · {t.organization}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* ───── Trust strip — federations ───── */}
            <section className="border-y border-ink-100 bg-ink-50/40 px-5 py-12 sm:px-8">
                <div className="mx-auto max-w-6xl text-center">
                    <p className="text-xs font-semibold uppercase tracking-wider text-ink-500">
                        Référencés et conformes
                    </p>
                    <div className="mt-6 flex flex-wrap items-center justify-center gap-4 sm:gap-6">
                        {['HAS', 'AFNOR NF X50-056', 'ISO 9001', 'RGPD', 'HDS', 'Caphandeo'].map((label) => (
                            <span
                                key={label}
                                className="rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-semibold text-ink-700"
                            >
                                {label}
                            </span>
                        ))}
                    </div>
                </div>
            </section>

            {/* ───── Final CTA ───── */}
            <section className="px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-4xl rounded-3xl bg-gradient-to-br from-brand-600 to-brand-800 px-8 py-16 text-center text-white shadow-xl sm:px-12">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Rejoignez les structures qui pilotent leur qualité
                    </h2>
                    <p className="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-white/85">
                        Pilote gratuit 3 mois, sans engagement, avec un référent dédié pour configurer votre structure et former vos premiers utilisateurs.
                    </p>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link
                            href="/contact"
                            className="inline-flex items-center gap-2 rounded-full bg-white px-7 py-3 text-sm font-semibold text-brand-700 transition-all hover:-translate-y-0.5 hover:bg-ink-50 hover:shadow-lg"
                        >
                            Démarrer le pilote 3 mois
                            <ArrowIcon />
                        </Link>
                        <Link
                            href="/tarifs"
                            className="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-7 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/20"
                        >
                            Voir les tarifs
                        </Link>
                    </div>
                </div>
            </section>
        </MarketingPage>
    );
}

function QuoteIcon() {
    return (
        <svg className="size-6 text-ink-300" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 11h.01M7 17h4l3-9V5H6v8h3l-2 4zm10-6h.01M17 17h4l3-9V5h-8v8h3l-2 4z" />
        </svg>
    );
}

function ArrowIcon() {
    return (
        <svg className="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5}>
            <path d="M5 12h14M13 6l6 6-6 6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
