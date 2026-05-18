import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head, Link } from '@inertiajs/react';
import { Fragment, useState } from 'react';

// ────────────────────────────────────────────────────────────────────────────
//  Data
// ────────────────────────────────────────────────────────────────────────────

type Tier = 'essentiel' | 'pro' | 'premium';

interface PlanCard {
    id: Tier;
    name: string;
    priceMonthly: number;
    description: string;
    targetSize: string;
    highlights: string[];
    cta: string;
    highlighted?: boolean;
}

const PLANS: PlanCard[] = [
    {
        id: 'essentiel',
        name: 'Essentiel',
        priceMonthly: 8,
        description: "Pour les petites structures qui démarrent leur démarche qualité — toute l'opération du quotidien, rien de superflu.",
        targetSize: '< 30 intervenants · 1 site',
        highlights: [
            'Bénéficiaires & dossiers chiffrés HDS',
            'Planning interventions + check-in mobile',
            'Déclaration d\'incidents avec classification automatique',
            'Communication interne (messagerie 1-1 et groupes)',
            'Application mobile offline pour les tournées',
            'Support email · réponse sous 48 h ouvrées',
        ],
        cta: 'Démarrer le pilote',
    },
    {
        id: 'pro',
        name: 'Pro',
        priceMonthly: 15,
        description: "Pour les structures engagées dans une démarche qualité complète, prêtes pour la visite HAS et la démarche QVCT.",
        targetSize: '30 à 150 intervenants · 1 à 3 sites',
        highlights: [
            'Tout Essentiel +',
            'Module QVCT (baromètre + signaux faibles)',
            'Audits HAS, ISO 9001, AFNOR NF X50-056',
            "Plans d'Amélioration Continue (PAC) avec relances automatiques",
            'Gestion des compétences & certifications',
            'API REST + connecteur Excel / paie',
            'Support email · réponse sous 24 h ouvrées',
        ],
        cta: 'Démarrer le pilote',
        highlighted: true,
    },
    {
        id: 'premium',
        name: 'Premium',
        priceMonthly: 25,
        description: 'Pour les groupes multi-sites et les structures à forte exigence qualité, avec accompagnement humain et fonctionnalités IA.',
        targetSize: '> 150 intervenants · multi-sites',
        highlights: [
            'Tout Pro +',
            'Audits personnalisables · classeur de preuves HAS',
            'Gestion des compétences + parcours e-learning',
            'Tableau de bord exécutif (export PDF/Excel)',
            'Portail bénéficiaires & familles',
            'IA prédictive (burnout, perte d\'autonomie, benchmark sectoriel)',
            'Customer Success Manager dédié + support téléphone',
        ],
        cta: 'Parler à un expert',
    },
];

interface MatrixRow {
    label: string;
    /** Cells aligned with PLANS order. `true` = inclus, `false` = non inclus, `string` = précision. */
    cells: [boolean | string, boolean | string, boolean | string];
    sectionTitle?: string;
}

const MATRIX: MatrixRow[] = [
    { label: 'Bénéficiaires illimités', cells: [true, true, true], sectionTitle: 'Opération quotidienne' },
    { label: 'Intervenants illimités', cells: [true, true, true] },
    { label: 'Planning multi-tournées', cells: [true, true, true] },
    { label: 'Application mobile offline', cells: [true, true, true] },
    { label: 'Compte-rendus + photos + signatures', cells: [true, true, true] },
    { label: 'Plans de soins individualisés', cells: ['standard', 'avancé', 'avancé + modèles'] },

    { label: 'Déclaration d\'incidents', cells: [true, true, true], sectionTitle: 'Qualité & conformité' },
    { label: 'Classification automatique (ARS)', cells: [true, true, true] },
    { label: 'Module QVCT (baromètre + alertes)', cells: [false, true, true] },
    { label: 'Audits standard (HAS / ISO / AFNOR)', cells: [false, true, true] },
    { label: 'Audits personnalisables', cells: [false, false, true] },
    { label: 'Préparation visite HAS guidée', cells: [false, true, 'avec consultant'] },
    { label: 'Plans d\'Amélioration Continue', cells: ['basique', 'complet', 'complet + IA'] },

    { label: 'Gestion des compétences', cells: [false, 'basique', 'complet + e-learning'], sectionTitle: 'RH & formation' },
    { label: 'Alertes certifications expirantes', cells: [false, true, true] },
    { label: 'Émargements de session', cells: [false, true, true] },

    { label: 'Messagerie 1-1 & groupes', cells: [true, true, true], sectionTitle: 'Communication' },
    { label: 'Forum & Q&A interne', cells: [false, true, true] },
    { label: 'Visioconférence intégrée', cells: [false, false, true] },
    { label: 'Bibliothèque documentaire', cells: ['10 documents', 'illimité', 'illimité'] },

    { label: 'Tableau de bord temps réel', cells: [true, true, true], sectionTitle: 'Pilotage & data' },
    { label: 'Indicateurs personnalisables', cells: [false, true, true] },
    { label: 'Export PDF / Excel mensuel', cells: [false, true, true] },
    { label: 'Tableau de bord exécutif', cells: [false, false, true] },
    { label: 'Benchmark anonymisé sectoriel', cells: [false, false, true] },

    { label: 'IA prédictive (burnout, autonomie)', cells: [false, false, true], sectionTitle: 'IA & innovations' },
    { label: 'Portail bénéficiaires & familles', cells: [false, false, true] },

    { label: 'Hébergement HDS (France)', cells: [true, true, true], sectionTitle: 'Sécurité & infrastructure' },
    { label: 'Chiffrement AES-256 au repos', cells: [true, true, true] },
    { label: 'TLS 1.3 + MFA TOTP', cells: [true, true, true] },
    { label: 'Audit trail complet (RGPD Art 30)', cells: [true, true, true] },
    { label: 'Sauvegarde quotidienne + DR test annuel', cells: [false, true, true] },
    { label: 'SLA contractuel', cells: [false, '99,5 %', '99,9 %'] },

    { label: 'Support email', cells: ['48 h', '24 h', '4 h'], sectionTitle: 'Support & accompagnement' },
    { label: 'Support téléphone', cells: [false, false, true] },
    { label: 'Customer Success Manager', cells: [false, false, 'dédié'] },
    { label: 'Onboarding personnalisé', cells: [false, '2 h visio', 'sur site'] },
];

interface FAQItem {
    q: string;
    a: string;
}

const FAQ: FAQItem[] = [
    {
        q: 'Comment est calculé le prix ?',
        a: "Le tarif est par utilisateur facturable par mois. Sont comptés tous les comptes actifs (dirigeants, coordinateurs, RH, qualité, intervenants). Les bénéficiaires ne sont jamais facturés. La facturation est mensuelle ou annuelle (–10 % en annuel).",
    },
    {
        q: "Y a-t-il un engagement minimum ?",
        a: "Non. L'abonnement est sans engagement, résiliable à tout moment avec préavis d'un mois. Vos données sont exportables ou supprimées sur simple demande (Article 17 RGPD).",
    },
    {
        q: "Comment fonctionne le pilote 3 mois ?",
        a: "Vous démarrez sur le tier de votre choix avec un référent dédié pendant 3 mois, sans carte bancaire. Si la plateforme correspond à vos besoins, vous basculez sur l'abonnement choisi. Sinon, vos données sont restituées ou supprimées.",
    },
    {
        q: 'Peut-on changer de tier en cours de contrat ?',
        a: "Oui, le passage à un tier supérieur est immédiat avec prorata calculé automatiquement. La descente vers un tier inférieur prend effet à la prochaine échéance mensuelle.",
    },
    {
        q: "Que se passe-t-il en cas de dépassement de quota ?",
        a: "Aucun service n'est interrompu. Les utilisateurs supplémentaires sont facturés au prorata sur la facture suivante. Notre équipe vous alerte par email dès qu'un seuil est franchi.",
    },
    {
        q: 'Quels moyens de paiement acceptez-vous ?',
        a: "Prélèvement SEPA (recommandé) et carte bancaire pour le mensuel. Virement bancaire et mandat administratif acceptés pour l'annuel et les structures publiques (CCAS, hôpitaux).",
    },
    {
        q: "Proposez-vous des tarifs préférentiels ?",
        a: "Oui, sur consultation : structures publiques, fédérations (Adessa A Domicile, UNA, FEHAP, ADMR…), groupements d'achat et associations à but non lucratif. Contactez-nous.",
    },
    {
        q: "Mes données sont-elles vraiment hébergées en France ?",
        a: "Oui. Notre infrastructure tourne exclusivement sur AWS Paris ou OVHcloud Roubaix/Strasbourg, hébergeurs certifiés HDS par l'Agence du Numérique en Santé. Aucune réplication hors UE.",
    },
];

// ────────────────────────────────────────────────────────────────────────────
//  Component
// ────────────────────────────────────────────────────────────────────────────

export default function Tarifs() {
    const [annual, setAnnual] = useState(true);
    const [openFaq, setOpenFaq] = useState<number | null>(0);

    const computePrice = (monthly: number): { display: string; rule: string } => {
        if (!annual) {
            return { display: monthly.toString(), rule: '€ / utilisateur / mois' };
        }
        const yearly = Math.round(monthly * 12 * 0.9);
        const perMonth = Math.round((yearly / 12) * 10) / 10;
        const rounded = Number.isInteger(perMonth) ? perMonth.toString() : perMonth.toFixed(1);
        return { display: rounded, rule: `€ / utilisateur / mois · facturé annuellement` };
    };

    return (
        <MarketingPage>
            <Head title="Tarifs · HS Quality">
                <meta
                    name="description"
                    content="Tarifs simples et transparents — 8, 15 ou 25 € par utilisateur par mois. Sans engagement, hébergé HDS en France, pilote gratuit 3 mois."
                />
                <meta property="og:title" content="Tarifs · HS Quality" />
                <meta
                    property="og:description"
                    content="Trois tiers, paiement par utilisateur, pilote 3 mois gratuit. Hébergement HDS en France, conformité HAS et RGPD."
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
                        Tarification 2026 — sans engagement
                    </span>
                    <h1 className="mt-6 text-4xl font-bold tracking-tight text-ink-900 sm:text-5xl lg:text-6xl">
                        Un prix simple,{' '}
                        <span className="gradient-text">par utilisateur</span>
                    </h1>
                    <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink-600">
                        Aucun frais caché. Vos bénéficiaires ne sont jamais facturés. Vous payez uniquement les comptes actifs qui se connectent à la plateforme.
                    </p>

                    {/* Annual / monthly toggle */}
                    <div className="mt-10 inline-flex items-center gap-1 rounded-full border border-ink-200 bg-white p-1 shadow-sm">
                        <button
                            type="button"
                            onClick={() => setAnnual(false)}
                            className={
                                'rounded-full px-5 py-2 text-sm font-medium transition-colors ' +
                                (!annual ? 'bg-brand-600 text-white shadow-sm' : 'text-ink-600 hover:text-ink-900')
                            }
                        >
                            Mensuel
                        </button>
                        <button
                            type="button"
                            onClick={() => setAnnual(true)}
                            className={
                                'inline-flex items-center gap-2 rounded-full px-5 py-2 text-sm font-medium transition-colors ' +
                                (annual ? 'bg-brand-600 text-white shadow-sm' : 'text-ink-600 hover:text-ink-900')
                            }
                        >
                            Annuel
                            <span
                                className={
                                    'rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ' +
                                    (annual ? 'bg-white/20 text-white' : 'bg-sage-100 text-sage-700')
                                }
                            >
                                −10 %
                            </span>
                        </button>
                    </div>
                </div>
            </section>

            {/* ───── Plan cards ───── */}
            <section className="px-5 py-16 sm:px-8 sm:py-20">
                <div className="mx-auto grid max-w-7xl gap-6 lg:grid-cols-3">
                    {PLANS.map((plan) => {
                        const { display, rule } = computePrice(plan.priceMonthly);
                        return (
                            <div
                                key={plan.id}
                                className={
                                    'relative flex flex-col rounded-2xl border bg-white p-7 transition-all duration-300 ' +
                                    (plan.highlighted
                                        ? 'border-brand-300 ring-2 ring-brand-100 shadow-[0_24px_64px_-16px_rgba(21,101,172,0.18)]'
                                        : 'border-ink-100 hover:border-ink-200 hover:shadow-[0_24px_48px_-12px_rgba(15,23,42,0.08)]')
                                }
                            >
                                {plan.highlighted && (
                                    <span className="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-brand-600 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider text-white shadow-sm">
                                        Le plus choisi
                                    </span>
                                )}
                                <div>
                                    <h2 className="text-xl font-bold tracking-tight text-ink-900">{plan.name}</h2>
                                    <p className="mt-1 text-xs font-medium uppercase tracking-wider text-brand-600">{plan.targetSize}</p>
                                    <p className="mt-4 text-sm leading-relaxed text-ink-600">{plan.description}</p>
                                </div>

                                <div className="mt-7 flex items-baseline gap-2">
                                    <span className="text-5xl font-bold tracking-tight text-ink-900">{display}</span>
                                    <span className="text-sm text-ink-500">{rule}</span>
                                </div>

                                <ul className="mt-7 flex-1 space-y-3">
                                    {plan.highlights.map((h) => (
                                        <li key={h} className="flex items-start gap-2.5 text-sm text-ink-700">
                                            <CheckIcon />
                                            <span>{h}</span>
                                        </li>
                                    ))}
                                </ul>

                                <Link
                                    href={`/contact?tier=${plan.id}`}
                                    className={
                                        'mt-8 inline-flex items-center justify-center rounded-full px-6 py-3 text-sm font-semibold transition-all hover:-translate-y-0.5 ' +
                                        (plan.highlighted
                                            ? 'bg-brand-600 text-white hover:bg-brand-700 hover:shadow-lg'
                                            : 'border border-ink-200 bg-white text-ink-900 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700')
                                    }
                                >
                                    {plan.cta}
                                    <ArrowIcon />
                                </Link>
                            </div>
                        );
                    })}
                </div>
            </section>

            {/* ───── Cost calculator ───── */}
            <PricingCalculator annual={annual} />

            {/* ───── Trust strip ───── */}
            <section className="border-y border-ink-100 bg-ink-50/40 px-5 py-12 sm:px-8">
                <div className="mx-auto grid max-w-6xl gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        { icon: <ShieldIcon />, title: '3 mois pilote gratuits', body: 'Sans carte bancaire, avec référent dédié.' },
                        { icon: <RotateIcon />, title: 'Sans engagement', body: 'Résiliable à tout moment, préavis 1 mois.' },
                        { icon: <DownloadIcon />, title: 'Vos données exportables', body: 'Export PDF/Excel/CSV à toute heure.' },
                        { icon: <FlagIcon />, title: 'Hébergement HDS France', body: 'AWS Paris ou OVHcloud — aucune réplication hors UE.' },
                    ].map((item) => (
                        <div key={item.title} className="flex items-start gap-3">
                            <div className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-white text-brand-600 shadow-sm">
                                {item.icon}
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-ink-900">{item.title}</p>
                                <p className="mt-0.5 text-xs leading-relaxed text-ink-500">{item.body}</p>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            {/* ───── Comparison matrix ───── */}
            <section id="comparison" className="px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-6xl">
                    <div className="mx-auto max-w-2xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
                            Comparatif détaillé
                        </h2>
                        <p className="mt-4 text-base leading-relaxed text-ink-600">
                            Tout ce qui est inclus dans chaque offre, ligne par ligne. Aucun astérisque, aucune option cachée.
                        </p>
                    </div>

                    <div className="mt-12 overflow-x-auto rounded-2xl border border-ink-100 bg-white">
                        <table className="w-full text-left">
                            <thead className="bg-ink-50/60">
                                <tr>
                                    <th className="w-[40%] px-6 py-4 text-xs font-semibold uppercase tracking-wider text-ink-500">
                                        Fonctionnalité
                                    </th>
                                    {PLANS.map((plan) => (
                                        <th key={plan.id} className="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-ink-700">
                                            {plan.name}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-ink-100">
                                {MATRIX.map((row, idx) => (
                                    <Fragment key={`${row.label}-${idx}`}>
                                        {row.sectionTitle && (
                                            <tr>
                                                <td colSpan={4} className="bg-brand-50/40 px-6 py-3 text-xs font-bold uppercase tracking-wider text-brand-700">
                                                    {row.sectionTitle}
                                                </td>
                                            </tr>
                                        )}
                                        <tr>
                                            <td className="px-6 py-3 text-sm font-medium text-ink-700">{row.label}</td>
                                            {row.cells.map((cell, i) => (
                                                <td key={i} className="px-6 py-3 text-center">
                                                    <MatrixCell value={cell} />
                                                </td>
                                            ))}
                                        </tr>
                                    </Fragment>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {/* ───── FAQ ───── */}
            <section className="border-t border-ink-100 bg-ink-50/30 px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-3xl">
                    <div className="text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
                            Questions fréquentes
                        </h2>
                        <p className="mt-4 text-base leading-relaxed text-ink-600">
                            Tout ce que vous voulez savoir sur la facturation, l'engagement et les conditions.
                        </p>
                    </div>

                    <ul className="mt-12 space-y-3">
                        {FAQ.map((item, idx) => {
                            const open = openFaq === idx;
                            return (
                                <li key={item.q} className="rounded-2xl border border-ink-100 bg-white">
                                    <button
                                        type="button"
                                        onClick={() => setOpenFaq(open ? null : idx)}
                                        className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left transition-colors hover:bg-ink-50/60"
                                        aria-expanded={open}
                                    >
                                        <span className="text-base font-semibold text-ink-900">{item.q}</span>
                                        <ChevronIcon open={open} />
                                    </button>
                                    {open && (
                                        <div className="border-t border-ink-100 px-6 py-5 text-sm leading-relaxed text-ink-600">
                                            {item.a}
                                        </div>
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                </div>
            </section>

            {/* ───── Final CTA ───── */}
            <section className="px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-4xl rounded-3xl bg-gradient-to-br from-brand-600 to-brand-800 px-8 py-16 text-center text-white shadow-xl sm:px-12">
                    <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                        Démarrez votre pilote — 3 mois gratuits
                    </h2>
                    <p className="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-white/85">
                        Un référent vous accompagne pour configurer votre structure, importer vos données et former vos premiers utilisateurs. Aucune carte bancaire, aucun engagement.
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
                            href="/fonctionnalites"
                            className="inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/10 px-7 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/20"
                        >
                            Découvrir les fonctionnalités
                        </Link>
                    </div>
                </div>
            </section>
        </MarketingPage>
    );
}

// ────────────────────────────────────────────────────────────────────────────
//  Cells & icons
// ────────────────────────────────────────────────────────────────────────────

function MatrixCell({ value }: { value: boolean | string }) {
    if (value === true) {
        return (
            <span className="inline-flex size-7 items-center justify-center rounded-full bg-sage-50 text-sage-600">
                <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5}>
                    <path d="M5 12l5 5L20 7" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            </span>
        );
    }
    if (value === false) {
        return <span className="text-ink-300">—</span>;
    }
    return <span className="text-xs font-medium text-ink-600">{value}</span>;
}

function CheckIcon() {
    return (
        <svg className="mt-0.5 size-4 shrink-0 text-sage-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5}>
            <path d="M5 12l5 5L20 7" strokeLinecap="round" strokeLinejoin="round" />
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

function ChevronIcon({ open }: { open: boolean }) {
    return (
        <svg
            className={'size-5 shrink-0 text-ink-400 transition-transform ' + (open ? 'rotate-180' : '')}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
        >
            <path d="M6 9l6 6 6-6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

// ────────────────────────────────────────────────────────────────────────────
//  Cost calculator
// ────────────────────────────────────────────────────────────────────────────

function PricingCalculator({ annual }: { annual: boolean }) {
    const [users, setUsers] = useState<number>(30);
    const [tier, setTier] = useState<Tier>('pro');

    const plan = PLANS.find((p) => p.id === tier) ?? PLANS[1];
    const annualDiscount = annual ? 0.9 : 1;
    const perUserPerMonth = plan.priceMonthly * annualDiscount;
    const monthly = users * perUserPerMonth;
    const yearly = monthly * 12;

    const formatEuros = (value: number): string =>
        new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
            maximumFractionDigits: 0,
        }).format(value);

    return (
        <section id="calculateur" className="border-t border-ink-100 px-5 py-20 sm:px-8 sm:py-24">
            <div className="mx-auto max-w-5xl">
                <div className="mx-auto max-w-2xl text-center">
                    <h2 className="text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
                        Estimez votre budget
                    </h2>
                    <p className="mt-4 text-base leading-relaxed text-ink-600">
                        Glissez le curseur sur le nombre d'utilisateurs facturables de votre structure
                        (dirigeants, coordinateurs, qualité, RH, intervenants). Les bénéficiaires ne sont
                        jamais comptés.
                    </p>
                </div>

                <div className="mt-12 grid items-center gap-8 lg:grid-cols-5">
                    {/* Sliders + tier selector */}
                    <div className="space-y-6 lg:col-span-3">
                        {/* Tier selector */}
                        <div>
                            <label className="block text-xs font-semibold uppercase tracking-wider text-ink-500">
                                Niveau d'abonnement
                            </label>
                            <div className="mt-2 grid grid-cols-3 gap-2">
                                {PLANS.map((p) => (
                                    <button
                                        key={p.id}
                                        type="button"
                                        onClick={() => setTier(p.id)}
                                        aria-pressed={tier === p.id}
                                        className={
                                            tier === p.id
                                                ? 'rounded-xl border-2 border-brand-500 bg-brand-50 px-4 py-3 text-left transition-all'
                                                : 'rounded-xl border-2 border-ink-100 bg-white px-4 py-3 text-left transition-all hover:border-ink-200'
                                        }
                                    >
                                        <p className="text-sm font-semibold text-ink-900">{p.name}</p>
                                        <p className="mt-0.5 text-[11px] text-ink-500">{p.targetSize}</p>
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* User slider */}
                        <div>
                            <div className="flex items-baseline justify-between">
                                <label htmlFor="user-count" className="block text-xs font-semibold uppercase tracking-wider text-ink-500">
                                    Utilisateurs facturables
                                </label>
                                <span className="font-mono text-2xl font-bold tracking-tight text-ink-900">{users}</span>
                            </div>
                            <input
                                id="user-count"
                                type="range"
                                min={5}
                                max={300}
                                step={5}
                                value={users}
                                onChange={(e) => setUsers(Number(e.target.value))}
                                className="mt-3 w-full cursor-pointer accent-brand-600"
                            />
                            <div className="mt-1 flex justify-between text-[11px] font-mono text-ink-400">
                                <span>5</span>
                                <span>50</span>
                                <span>100</span>
                                <span>200</span>
                                <span>300+</span>
                            </div>
                        </div>
                    </div>

                    {/* Result card */}
                    <div className="rounded-2xl bg-gradient-to-br from-brand-600 to-brand-800 p-8 text-white shadow-lg lg:col-span-2">
                        <p className="text-xs font-semibold uppercase tracking-wider text-white/70">
                            Estimation {annual ? 'annuelle' : 'mensuelle'}
                        </p>
                        <p className="mt-3 font-mono text-4xl font-bold tracking-tight">
                            {formatEuros(annual ? yearly : monthly)}
                        </p>
                        <p className="mt-1 text-xs text-white/70">
                            {annual ? `≈ ${formatEuros(monthly)} / mois (facturé annuellement)` : `Soit ${formatEuros(yearly)} / an`}
                        </p>

                        <hr className="my-5 border-white/15" />

                        <dl className="space-y-2 text-xs">
                            <div className="flex justify-between text-white/75">
                                <dt>Tier sélectionné</dt>
                                <dd className="font-semibold text-white">{plan.name}</dd>
                            </div>
                            <div className="flex justify-between text-white/75">
                                <dt>Prix unitaire</dt>
                                <dd className="font-mono text-white">
                                    {Number.isInteger(perUserPerMonth) ? perUserPerMonth : perUserPerMonth.toFixed(1)} € / user / mois
                                </dd>
                            </div>
                            <div className="flex justify-between text-white/75">
                                <dt>Cycle</dt>
                                <dd className="font-semibold text-white">{annual ? 'Annuel (−10 %)' : 'Mensuel'}</dd>
                            </div>
                        </dl>

                        <Link
                            href={`/contact?tier=${plan.id}&users=${users}`}
                            className="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-brand-700 transition-all hover:-translate-y-0.5 hover:shadow-lg"
                        >
                            Demander un devis
                        </Link>
                    </div>
                </div>

                <p className="mt-6 text-center text-[11px] text-ink-400">
                    Estimation indicative. Le tarif final dépend des modules retenus et des éventuelles remises (associatif, multi-sites, groupements).
                </p>
            </div>
        </section>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function RotateIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M3 12a9 9 0 0114.5-7M21 12a9 9 0 01-14.5 7M21 3v6h-6M3 21v-6h6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function DownloadIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function FlagIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1zM4 22V15" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
