import { Badge, Sheet } from '@/components/ui';
import { usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface PageProps {
    [key: string]: unknown;
}

interface ContextualHelp {
    title: string;
    description: string;
    quickTips: string[];
    relatedActions?: { label: string; href: string }[];
}

const HELP_BY_PATH: Array<{ match: RegExp; help: ContextualHelp }> = [
    {
        match: /^\/dashboard$/,
        help: {
            title: 'Tableau de bord',
            description: 'Vue d\'ensemble synthétique de votre structure — interventions du jour, alertes incidents, indicateurs qualité.',
            quickTips: [
                'Cliquez sur un KPI pour ouvrir la page de détail',
                'La cloche en haut à droite centralise vos notifications',
                'Cmd+K ouvre la recherche transversale instantanée',
            ],
        },
    },
    {
        match: /^\/interventions/,
        help: {
            title: 'Interventions',
            description: "Planification et suivi des visites à domicile. Chaque intervention est tracée et auditable.",
            quickTips: [
                'Le bouton « 👁 Aperçu » sur chaque ligne ouvre le détail sans naviguer',
                'Les filtres avancés permettent de sauvegarder des vues fréquentes',
                'Sélectionnez plusieurs lignes pour les actions en masse',
                'Le statut « offline » indique une visite saisie depuis le mobile en zone blanche',
            ],
            relatedActions: [
                { label: 'Voir les bénéficiaires', href: '/beneficiaries' },
                { label: 'Déclarer un incident', href: '/incidents/create' },
            ],
        },
    },
    {
        match: /^\/incidents/,
        help: {
            title: 'Incidents & événements indésirables',
            description: "Déclaration et suivi des EI conformes à la grille HAS. Les incidents graves/critiques déclenchent une notification ARS automatique sous 24h.",
            quickTips: [
                "Tout incident grave ou critique est notifié à l'ARS automatiquement",
                'Le workflow 5-pourquoi est intégré dans l\'analyse',
                'Les pièces jointes (photos, signatures) sont chiffrées et stockées HDS',
            ],
            relatedActions: [
                { label: 'Registre d\'audit', href: '/audit-log' },
            ],
        },
    },
    {
        match: /^\/beneficiaries/,
        help: {
            title: 'Bénéficiaires',
            description: 'Dossiers des personnes accompagnées. Les données de santé (RGPD Art. 9) sont chiffrées au repos.',
            quickTips: [
                'Le dossier médical (allergies, traitements) est tracé : tout accès est audité',
                'L\'anonymisation RGPD se déclenche automatiquement après la durée légale',
                'Affectez un intervenant pour qu\'il puisse saisir des visites',
            ],
        },
    },
    {
        match: /^\/audits/,
        help: {
            title: 'Audits & conformité',
            description: 'Audits HAS, ISO 9001 et AFNOR — score automatique, génération PDF, déclenchement de plans d\'amélioration.',
            quickTips: [
                'La finalisation est irréversible — le PDF est horodaté',
                'Les écarts identifiés génèrent automatiquement un PAC',
                'Wizard 4 étapes guide la création d\'un nouvel audit',
            ],
            relatedActions: [
                { label: 'Plans d\'amélioration', href: '/plans-amelioration' },
            ],
        },
    },
    {
        match: /^\/qvct/,
        help: {
            title: 'Baromètre QVCT',
            description: 'Suivi de la qualité de vie au travail. Toutes les réponses sont anonymes — seuil de 5 répondants minimum pour publier des résultats par équipe.',
            quickTips: [
                'Aucune réponse individuelle ne sera affichée à la direction',
                'Les signaux faibles RPS sont détectés automatiquement',
                'La cartographie RPS croise équipes × dimensions',
            ],
            relatedActions: [
                { label: 'Signaux faibles', href: '/qvct/weak-signals' },
                { label: 'Cartographie RPS', href: '/qvct/indicators' },
            ],
        },
    },
    {
        match: /^\/plans-amelioration/,
        help: {
            title: "Plans d'amélioration continue",
            description: "Plans d'actions correctifs (PAC) — pilotés en mode kanban ou liste, avec courbe d'épuisement (burndown).",
            quickTips: [
                "Chaque écart d'audit grave/critique génère automatiquement un PAC",
                "Filtrez par responsable pour voir uniquement vos actions",
                "La clôture demande un commentaire — exigence traçabilité qualité",
                "Le burndown projette la date d'atteinte du 100% au rythme actuel",
            ],
            relatedActions: [
                { label: 'Audits liés', href: '/audits' },
                { label: 'Indicateurs qualité', href: '/indicateurs' },
            ],
        },
    },
    {
        match: /^\/care-plans/,
        help: {
            title: "Plans d'accompagnement",
            description: "Plans de soins individualisés : objectifs, tâches planifiées, suivi des intervenants — accessible et auditable.",
            quickTips: [
                "Cocher une tâche déclenche un suivi tracé dans le journal du bénéficiaire",
                "L'archivage demande un motif et conserve l'historique",
                "Le sticky TOC à droite permet de naviguer rapidement sur un plan long",
            ],
        },
    },
    {
        match: /^\/formations/,
        help: {
            title: 'Formations & habilitations',
            description: 'Suivi des certifications obligatoires (gestes & postures, AFGSU, RGPD…) et des plans de formation annuels.',
            quickTips: [
                "Un mail d'alerte est envoyé 60j et 30j avant l'échéance d'une certification",
                "La matrice de compétences croise intervenants × formations obligatoires",
                "Les sessions présentielles ou e-learning émettent un certificat horodaté",
            ],
            relatedActions: [
                { label: 'Mes compétences', href: '/formations/competencies/mine' },
            ],
        },
    },
    {
        match: /^\/communication/,
        help: {
            title: "Fil d'actualité & messages",
            description: "Diffusion d'annonces, célébrations QVCT et notes épinglées à l'échelle de la structure.",
            quickTips: [
                "Les pièces jointes sont stockées HDS et chiffrées au repos",
                "Une publication peut être ciblée sur un groupe (équipe / secteur)",
                "Les notes épinglées restent visibles en haut du fil tant qu'elles ne sont pas détachées",
            ],
        },
    },
    {
        match: /^\/indicateurs/,
        help: {
            title: 'Indicateurs qualité',
            description: "Vue agrégée des KPIs réglementaires HAS / AFNOR et de la conformité de votre structure.",
            quickTips: [
                "Les seuils sont configurables par votre référent qualité",
                "L'export CSV est disponible sur chaque bloc d'indicateurs",
                "Les valeurs anonymisées (n<5) sont marquées d'un cadenas",
            ],
        },
    },
    {
        match: /^\/audit-log/,
        help: {
            title: 'Registre d\'audit',
            description: 'Journal immuable de toutes les actions sur les données — exigence RGPD Article 30.',
            quickTips: [
                'Chaque événement est conservé conformément à votre politique de rétention',
                'Les diff de valeurs sont consultables via le détail',
                'Filtrez par type d\'événement, ressource ou utilisateur',
            ],
        },
    },
    {
        match: /^\/billing/,
        help: {
            title: 'Abonnement & facturation',
            description: 'Gestion du plan, du moyen de paiement et des factures. Changement de tier effectif immédiat.',
            quickTips: [
                'Les factures sont téléchargeables au format PDF',
                'La résiliation prend effet à la fin de la période en cours',
                'Vos données restent consultables 90 jours après résiliation puis sont anonymisées',
            ],
        },
    },
];

const GENERIC_HELP: ContextualHelp = {
    title: 'Aide',
    description: 'Bienvenue sur HS Quality. Voici quelques raccourcis pour démarrer.',
    quickTips: [
        'Cmd+K ouvre la recherche transversale',
        'La cloche en haut à droite affiche vos notifications',
        'Le menu utilisateur donne accès à votre profil et aux paramètres',
    ],
};

const SHORTCUTS: Array<{ keys: string; desc: string }> = [
    { keys: '⌘K', desc: 'Recherche transversale' },
    { keys: '/', desc: 'Recherche (depuis n\'importe où sans focus champ)' },
    { keys: 'Esc', desc: 'Fermer modal/sheet/drawer' },
    { keys: '↑ ↓ ↵', desc: 'Navigation dans la palette de commandes' },
];

export default function HelpDrawer({ open, onClose }: { open: boolean; onClose: () => void }) {
    const { url } = usePage<PageProps>();
    const path = url.split('?')[0];

    const help = useMemo(() => {
        const match = HELP_BY_PATH.find((h) => h.match.test(path));
        return match?.help ?? GENERIC_HELP;
    }, [path]);

    const [tab, setTab] = useState<'context' | 'shortcuts'>('context');

    return (
        <Sheet
            open={open}
            onClose={onClose}
            size="md"
            title={help.title}
            description={path}
            iconTone="brand"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            }
        >
            <div className="mb-4 flex gap-1 rounded-lg border border-ink-200 bg-white p-1 dark:border-ink-700 dark:bg-ink-800">
                <TabButton active={tab === 'context'} onClick={() => setTab('context')}>
                    Cette page
                </TabButton>
                <TabButton active={tab === 'shortcuts'} onClick={() => setTab('shortcuts')}>
                    Raccourcis
                </TabButton>
            </div>

            {tab === 'context' && (
                <div className="space-y-5">
                    <p className="text-sm leading-relaxed text-ink-700 dark:text-ink-200">{help.description}</p>

                    <section>
                        <p className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                            Bon à savoir
                        </p>
                        <ul className="space-y-1.5">
                            {help.quickTips.map((tip, i) => (
                                <li key={i} className="flex items-start gap-2 text-xs text-ink-700 dark:text-ink-200">
                                    <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                                        💡
                                    </span>
                                    {tip}
                                </li>
                            ))}
                        </ul>
                    </section>

                    {help.relatedActions && help.relatedActions.length > 0 && (
                        <section>
                            <p className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                Actions liées
                            </p>
                            <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                {help.relatedActions.map((a) => (
                                    <a
                                        key={a.href}
                                        href={a.href}
                                        onClick={onClose}
                                        className="rounded-lg border border-ink-200 bg-white px-3 py-2 text-center text-xs font-medium text-brand-600 hover:border-brand-300 hover:bg-brand-50 dark:border-ink-700 dark:bg-ink-800 dark:text-brand-400 dark:hover:border-brand-500 dark:hover:bg-brand-900/20"
                                    >
                                        {a.label} →
                                    </a>
                                ))}
                            </div>
                        </section>
                    )}

                    <section className="rounded-xl border border-ink-100 bg-ink-50/40 p-3 dark:border-ink-700/60 dark:bg-ink-900/30">
                        <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                            Support
                        </p>
                        <p className="mt-1 text-xs text-ink-700 dark:text-ink-200">
                            Une question, un blocage ?{' '}
                            <a href="mailto:support@hsquality.fr" className="font-medium text-brand-600 hover:underline dark:text-brand-400">
                                support@hsquality.fr
                            </a>
                        </p>
                    </section>
                </div>
            )}

            {tab === 'shortcuts' && (
                <div className="space-y-3">
                    <p className="text-sm text-ink-700 dark:text-ink-200">Maîtrisez l'application au clavier — utile pour le saisie rapide.</p>
                    <ul className="overflow-hidden rounded-xl border border-ink-100 dark:border-ink-700/60">
                        {SHORTCUTS.map((s, i) => (
                            <li
                                key={i}
                                className="flex items-center justify-between gap-3 border-b border-ink-100 px-3 py-2.5 last:border-b-0 dark:border-ink-700/60"
                            >
                                <span className="text-xs text-ink-700 dark:text-ink-200">{s.desc}</span>
                                <kbd className="rounded border border-ink-200 bg-white px-2 py-0.5 font-mono text-xs text-ink-700 shadow-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-200">
                                    {s.keys}
                                </kbd>
                            </li>
                        ))}
                    </ul>
                    <div className="rounded-xl border border-brand-200 bg-brand-50/40 p-3 dark:border-brand-700/40 dark:bg-brand-900/15">
                        <p className="text-xs text-brand-900 dark:text-brand-200">
                            <Badge tone="brand" size="xs">PRO TIP</Badge>{' '}
                            <span className="ml-1">
                                Tapez « <kbd className="rounded bg-white px-1 font-mono text-[10px] dark:bg-ink-800">⌘K</kbd> » puis quelques lettres pour atteindre n'importe quel bénéficiaire ou intervention en moins de 2 secondes.
                            </span>
                        </p>
                    </div>
                </div>
            )}
        </Sheet>
    );
}

function TabButton({ active, onClick, children }: { active: boolean; onClick: () => void; children: React.ReactNode }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={
                'flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-colors ' +
                (active
                    ? 'bg-brand-600 text-white shadow-sm'
                    : 'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-700/60')
            }
        >
            {children}
        </button>
    );
}
