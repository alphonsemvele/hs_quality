import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

// ────────────────────────────────────────────────────────────────────────────
//  Data
// ────────────────────────────────────────────────────────────────────────────

type Category = 'nouveau' | 'amelioration' | 'correctif' | 'securite';

interface ChangelogEntry {
    category: Category;
    title: string;
    description: string;
}

interface Release {
    version: string;
    date: string;
    /** Headline that summarises the release. */
    headline: string;
    entries: ChangelogEntry[];
}

const RELEASES: Release[] = [
    {
        version: '2.4.0',
        date: '2026-05-13',
        headline: 'Glossaire intégré + tooltips pédagogiques',
        entries: [
            {
                category: 'nouveau',
                title: 'Glossaire métier consultable depuis le menu utilisateur',
                description:
                    "27 termes de l'aide à domicile (GIR, AGGIR, APA, HAS, ARS, QVCT, PAC, RGPD, HDS…) classés en 7 catégories, avec recherche full-text et filtres.",
            },
            {
                category: 'nouveau',
                title: 'Tooltips pédagogiques sur les en-têtes, KPIs et badges',
                description:
                    "Survoler une colonne, une carte indicateur ou un badge de statut affiche désormais sa définition. Plus besoin de chercher dans la doc — l'aide est là où vous travaillez.",
            },
            {
                category: 'amelioration',
                title: 'Badge GIR avec barème AGGIR intégré',
                description:
                    'Chaque niveau GIR (1 à 6) affiche sa définition officielle au survol — utile pour les nouveaux coordinateurs.',
            },
        ],
    },
    {
        version: '2.3.0',
        date: '2026-05-08',
        headline: 'Sprint 7 — Audits, HAS et formations',
        entries: [
            {
                category: 'nouveau',
                title: 'Bibliothèque de référentiels d\'audit',
                description:
                    'Grilles HAS, ISO 9001 et AFNOR NF X50-056 livrées en lecture seule. Cloning vers vos audits internes en un clic.',
            },
            {
                category: 'nouveau',
                title: 'Guide de préparation à la visite HAS',
                description:
                    "Checklist priorisée + classeur de preuves auto-généré à partir de vos audits internes et de votre PAC.",
            },
            {
                category: 'nouveau',
                title: 'Détail des sessions de formation + émargements',
                description:
                    'Édition des sessions, gestion des présences/absences, vue intervenant des compétences détenues.',
            },
            {
                category: 'amelioration',
                title: 'Préférences de notifications fines',
                description:
                    'Opt-in/opt-out par type d\'événement et par canal (email, push mobile, in-app).',
            },
        ],
    },
    {
        version: '2.2.0',
        date: '2026-05-04',
        headline: 'Sprint 6 — Tests, Wayfinder, QVCT builder, Echo client',
        entries: [
            {
                category: 'nouveau',
                title: 'Constructeur de questionnaire QVCT en drag-and-drop',
                description:
                    "Ajout/réordonnancement/duplication de questions, validation côté serveur, prévisualisation responsive.",
            },
            {
                category: 'nouveau',
                title: 'Client Echo branché (WebSocket Reverb)',
                description:
                    'Les changements de statut d\'intervention et les nouveaux incidents apparaissent en temps réel sans rafraîchir.',
            },
            {
                category: 'amelioration',
                title: 'Routes typées (Wayfinder)',
                description:
                    "Toutes les routes contrôleur sont désormais générées en TypeScript. Plus de liens hardcodés cassés à la compilation.",
            },
            {
                category: 'amelioration',
                title: 'Couverture de tests Pest portée à 868 définitions',
                description:
                    'Tests bulkCancel, reorder, updateContact, audit-log, MFA setup — exigence CLAUDE.md respectée.',
            },
        ],
    },
    {
        version: '2.1.0',
        date: '2026-04-29',
        headline: 'Sprint 5 — Drag & drop, bannières système, bulk endpoints',
        entries: [
            {
                category: 'nouveau',
                title: 'Réordonnancement des tâches planifiées par drag & drop',
                description:
                    'Endpoint REST optimisé + bannière de confirmation. Idempotent côté API mobile.',
            },
            {
                category: 'nouveau',
                title: 'Actions en masse sur les interventions',
                description:
                    'Annuler, réassigner, dupliquer plusieurs interventions en une seule requête authentifiée et auditée.',
            },
            {
                category: 'amelioration',
                title: 'États vides illustrés',
                description:
                    'Toutes les listes sans données proposent une action contextuelle et 3 suggestions pour démarrer.',
            },
        ],
    },
    {
        version: '2.0.0',
        date: '2026-04-22',
        headline: 'Sprint 4 — Filtres, hover cards, drawer d\'aide',
        entries: [
            {
                category: 'nouveau',
                title: 'Filtres persistants par URL sur toutes les listes',
                description:
                    'Statut, période, intervenant, gravité — partageables par lien.',
            },
            {
                category: 'nouveau',
                title: 'Hover cards riches sur les noms d\'intervenants',
                description:
                    'Aperçu compétences, planning du jour, photo, contact direct.',
            },
            {
                category: 'amelioration',
                title: "Drawer d'aide contextuelle",
                description:
                    "Accessible via le raccourci ⌘? ou l'icône en bas à droite — contenu adapté à la page courante.",
            },
        ],
    },
    {
        version: '1.9.0',
        date: '2026-04-14',
        headline: 'Sprint 3 — Wizards & onboarding',
        entries: [
            {
                category: 'nouveau',
                title: 'Wizard de plan de soins individualisé',
                description:
                    'Création guidée en 4 étapes : type, tâches récurrentes, intervenants, signatures. Modèles HAS/AFNOR pré-remplis.',
            },
            {
                category: 'nouveau',
                title: 'Wizard de campagne QVCT',
                description:
                    'Sélection du questionnaire, ciblage des cohortes, calendrier de relance, anonymisation des réponses.',
            },
            {
                category: 'nouveau',
                title: "Parcours d'onboarding nouveaux dirigeants",
                description:
                    'Tour guidé en 6 étapes — première structure, premier utilisateur, premier bénéficiaire, premier audit.',
            },
        ],
    },
    {
        version: '1.7.0',
        date: '2026-04-04',
        headline: 'Sprint 2 — Sheets, modals, MFA setup',
        entries: [
            {
                category: 'securite',
                title: 'Configuration MFA TOTP (RFC 6238)',
                description:
                    "Activation OBLIGATOIRE pour les rôles bureau (dirigeants, coordinateurs, qualité, RH). QR code + codes de récupération chiffrés. SMS et email OTP explicitement rejetés (recommandation ANSSI).",
            },
            {
                category: 'nouveau',
                title: "Sheets d'aperçu rapide bénéficiaire & intervention",
                description:
                    'Survol de la fiche complète sans quitter la liste — ouverture en panneau latéral.',
            },
            {
                category: 'nouveau',
                title: 'Modal de confirmation contextuel',
                description:
                    'Avertissement explicite avant chaque action destructive (suppression, annulation, archivage).',
            },
        ],
    },
    {
        version: '1.5.0',
        date: '2026-03-21',
        headline: 'API mobile + offline + idempotency',
        entries: [
            {
                category: 'nouveau',
                title: 'API REST `/api/v1/*` complète (Sanctum + OpenAPI Scramble)',
                description:
                    "Auth, interventions, incidents, bénéficiaires, sync batch. Throttle 300 req/min. Cross-tenant 404 (jamais 403).",
            },
            {
                category: 'nouveau',
                title: 'Synchronisation offline avec UUID client et marqueurs de conflit',
                description:
                    "Les zones blanches ne bloquent plus l'application. Réconciliation Last-Write-Wins avec narratifs préservés en cas de course.",
            },
            {
                category: 'securite',
                title: "Idempotency-Key — replay sécurisé sur les écritures",
                description:
                    'Tout POST/PUT/PATCH/DELETE peut être relancé avec la même clé : le serveur renvoie la réponse mise en cache 24 h, aucun double effet de bord.',
            },
        ],
    },
];

const CATEGORY_META: Record<Category, { label: string; tone: string; dot: string }> = {
    nouveau: {
        label: 'Nouveau',
        tone: 'bg-brand-50 text-brand-700 ring-1 ring-brand-100',
        dot: 'bg-brand-500',
    },
    amelioration: {
        label: 'Amélioration',
        tone: 'bg-sage-50 text-sage-700 ring-1 ring-sage-100',
        dot: 'bg-sage-500',
    },
    correctif: {
        label: 'Correctif',
        tone: 'bg-ink-100 text-ink-700 ring-1 ring-ink-200',
        dot: 'bg-ink-400',
    },
    securite: {
        label: 'Sécurité',
        tone: 'bg-warning-50 text-warning-700 ring-1 ring-warning-200',
        dot: 'bg-warning-500',
    },
};

// ────────────────────────────────────────────────────────────────────────────
//  Component
// ────────────────────────────────────────────────────────────────────────────

export default function Changelog() {
    const [filter, setFilter] = useState<Category | 'all'>('all');

    const filteredReleases = RELEASES.map((release) => ({
        ...release,
        entries: filter === 'all' ? release.entries : release.entries.filter((e) => e.category === filter),
    })).filter((release) => release.entries.length > 0);

    const counts: Record<Category | 'all', number> = {
        all: RELEASES.reduce((sum, r) => sum + r.entries.length, 0),
        nouveau: 0,
        amelioration: 0,
        correctif: 0,
        securite: 0,
    };
    for (const release of RELEASES) {
        for (const entry of release.entries) {
            counts[entry.category]++;
        }
    }

    return (
        <MarketingPage>
            <Head title="Changelog · HS Quality">
                <meta
                    name="description"
                    content="Toutes les évolutions de la plateforme HS Quality — nouveautés, améliorations, correctifs et mises à jour de sécurité, classées par version."
                />
                <meta property="og:title" content="Changelog · HS Quality" />
                <meta
                    property="og:description"
                    content="Toutes les évolutions produit : nouveautés, améliorations, correctifs et mises à jour de sécurité, classées par version."
                />
                <meta property="og:type" content="website" />
                <meta property="og:locale" content="fr_FR" />
            </Head>

            {/* ───── Hero ───── */}
            <section className="border-b border-ink-100 bg-gradient-to-b from-brand-50/60 to-white px-5 py-20 sm:px-8 sm:py-28">
                <div className="mx-auto max-w-4xl text-center">
                    <span className="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/80 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-brand-700">
                        <span className="size-1.5 rounded-full bg-sage-500" />
                        Dernière mise à jour {RELEASES[0].date}
                    </span>
                    <h1 className="mt-6 text-4xl font-bold tracking-tight text-ink-900 sm:text-5xl lg:text-6xl">
                        Journal des{' '}
                        <span className="gradient-text">évolutions produit</span>
                    </h1>
                    <p className="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-ink-600">
                        Tout ce qui change sur la plateforme, classé par version et par catégorie. Pas de demi-vérités —
                        les correctifs et alertes de sécurité sont publiés aussi.
                    </p>
                </div>
            </section>

            {/* ───── Filters ───── */}
            <section className="border-b border-ink-100 bg-white px-5 py-6 sm:px-8">
                <div className="mx-auto flex max-w-5xl flex-wrap items-center justify-center gap-2">
                    <FilterChip active={filter === 'all'} onClick={() => setFilter('all')} label={`Toutes (${counts.all})`} />
                    {(Object.keys(CATEGORY_META) as Category[]).map((cat) => (
                        <FilterChip
                            key={cat}
                            active={filter === cat}
                            onClick={() => setFilter(cat)}
                            label={`${CATEGORY_META[cat].label} (${counts[cat]})`}
                            dotClass={CATEGORY_META[cat].dot}
                        />
                    ))}
                </div>
            </section>

            {/* ───── Releases timeline ───── */}
            <section className="px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-4xl">
                    {filteredReleases.length === 0 ? (
                        <div className="rounded-2xl border border-ink-100 bg-white p-12 text-center">
                            <p className="text-base font-semibold text-ink-700">Aucune entrée dans cette catégorie.</p>
                            <p className="mt-2 text-sm text-ink-500">Essayez « Toutes » pour voir tout l'historique.</p>
                        </div>
                    ) : (
                        <ol className="space-y-12">
                            {filteredReleases.map((release) => (
                                <li key={release.version} className="relative">
                                    <div className="flex items-baseline gap-3 border-b border-ink-100 pb-4">
                                        <span className="font-mono text-2xl font-bold tracking-tight text-ink-900">
                                            v{release.version}
                                        </span>
                                        <span className="text-sm text-ink-500">{release.date}</span>
                                    </div>
                                    <h2 className="mt-4 text-xl font-semibold text-ink-900">{release.headline}</h2>

                                    <ul className="mt-6 space-y-4">
                                        {release.entries.map((entry, idx) => {
                                            const cat = CATEGORY_META[entry.category];
                                            return (
                                                <li
                                                    key={idx}
                                                    className="flex flex-col gap-2 rounded-2xl border border-ink-100 bg-white p-5 transition-shadow hover:shadow-[0_4px_24px_rgba(15,23,42,0.04)] sm:flex-row sm:items-start sm:gap-4"
                                                >
                                                    <span
                                                        className={
                                                            'inline-flex w-fit shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-wider ' +
                                                            cat.tone
                                                        }
                                                    >
                                                        <span className={'size-1.5 rounded-full ' + cat.dot} />
                                                        {cat.label}
                                                    </span>
                                                    <div className="min-w-0 flex-1">
                                                        <p className="text-sm font-semibold text-ink-900">{entry.title}</p>
                                                        <p className="mt-1 text-sm leading-relaxed text-ink-600">{entry.description}</p>
                                                    </div>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </li>
                            ))}
                        </ol>
                    )}
                </div>
            </section>

            {/* ───── Subscribe CTA ───── */}
            <section className="border-t border-ink-100 bg-ink-50/40 px-5 py-20 sm:px-8 sm:py-24">
                <div className="mx-auto max-w-3xl text-center">
                    <h2 className="text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">
                        Recevez les mises à jour dans votre boîte mail
                    </h2>
                    <p className="mt-3 text-base leading-relaxed text-ink-600">
                        Une newsletter mensuelle avec les nouveautés produit, les correctifs et les alertes de sécurité.
                        Pas plus, et désinscription en un clic.
                    </p>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Link
                            href="/contact?subject=newsletter"
                            className="inline-flex items-center gap-2 rounded-full bg-brand-600 px-7 py-3 text-sm font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg"
                        >
                            S'abonner à la newsletter produit
                        </Link>
                    </div>
                </div>
            </section>
        </MarketingPage>
    );
}

function FilterChip({
    active,
    onClick,
    label,
    dotClass,
}: {
    active: boolean;
    onClick: () => void;
    label: string;
    dotClass?: string;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                active
                    ? 'inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-brand-700'
                    : 'inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-1.5 text-xs font-semibold text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700'
            }
        >
            {dotClass && <span className={'size-1.5 rounded-full ' + dotClass} />}
            {label}
        </button>
    );
}
