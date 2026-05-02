import {
    Card,
    CardBody,
    CardHeader,
    EmptyState,
    IncidentGraviteBadge,
    IncidentStatusBadge,
    KpiCard,
} from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from './layout';

interface Stats {
    interventions_ce_mois: number;
    interventions_en_cours: number;
    incidents_declares: number;
    incidents_en_cours: number;
    score_conformite: number;
    taux_completion_pac: number;
    score_qvct_moyen: number;
    formations_expirant_bientot: number;
    intervenants_actifs: number;
    structures_actives: number;
}

interface IncidentRecent {
    id: number | string;
    initials: string;
    declarant: string;
    categorie: string;
    gravite: 'mineur' | 'significatif' | 'grave' | 'critique';
    statut: 'declare' | 'en_analyse' | 'plan_actions' | 'clos';
    structure: string;
    depuis: string;
}

interface AlerteQvct {
    id: number;
    intervenant: string;
    structure: string;
    score: number;
    signal: string;
    depuis: string;
}

interface Props {
    stats: Stats;
    incidents_recents: IncidentRecent[];
    alertes_qvct: AlerteQvct[];
    user_first_name: string;
    structure_name: string;
}

const EMPTY_STATS: Stats = {
    interventions_ce_mois: 0,
    interventions_en_cours: 0,
    incidents_declares: 0,
    incidents_en_cours: 0,
    score_conformite: 0,
    taux_completion_pac: 0,
    score_qvct_moyen: 0,
    formations_expirant_bientot: 0,
    intervenants_actifs: 0,
    structures_actives: 0,
};

function getGreeting(): string {
    const hour = new Date().getHours();
    if (hour < 12) return 'Bonjour';
    if (hour < 18) return 'Bon après-midi';
    return 'Bonsoir';
}

function formatDate(): string {
    return new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function Dashboard({
    stats = EMPTY_STATS,
    incidents_recents = [],
    alertes_qvct = [],
    user_first_name = '',
    structure_name = '',
}: Partial<Props>) {
    const s = stats ?? EMPTY_STATS;
    const incidentsGraves = incidents_recents.filter((i) => i.gravite === 'grave' || i.gravite === 'critique');

    return (
        <DashboardLayout title="Tableau de bord" subtitle="Pilotage qualité & QVCT">
            {/* Welcome header */}
            <div className="mb-8">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-ink-900 dark:text-white sm:text-3xl">
                            {getGreeting()}{user_first_name ? `, ${user_first_name}` : ''}
                        </h1>
                        <p className="mt-1 text-sm text-ink-500 dark:text-ink-400">
                            {formatDate()}
                            {structure_name && (
                                <span className="ml-2 inline-flex items-center gap-1.5">
                                    <span className="text-ink-300 dark:text-ink-600">&middot;</span>
                                    <span>{structure_name}</span>
                                </span>
                            )}
                        </p>
                    </div>
                    <Link
                        href="/incidents/create"
                        className="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-danger-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-danger-700 hover:shadow-md active:scale-[0.98] dark:bg-danger-600 dark:hover:bg-danger-500 md:hidden"
                    >
                        <PlusIcon />
                        Déclarer un incident
                    </Link>
                </div>
            </div>

            {/* Critical incident alert banner */}
            {incidentsGraves.length > 0 && (
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-700/50 dark:bg-danger-900/20">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 shrink-0 animate-pulse items-center justify-center rounded-xl bg-danger-500 text-white">
                            <AlertIcon />
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-danger-700 dark:text-danger-200">
                                {incidentsGraves.length} incident{incidentsGraves.length > 1 ? 's' : ''} grave{incidentsGraves.length > 1 ? 's' : ''}
                                {' / critique'}
                                {incidentsGraves.length > 1 ? 's' : ''} en cours
                            </p>
                            <p className="mt-0.5 text-xs text-danger-700/80 dark:text-danger-300/70">
                                {incidentsGraves.map((i) => i.declarant).join(' · ')}
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/incidents"
                        className="cursor-pointer rounded-lg bg-danger-600 px-4 py-2 text-xs font-semibold text-white transition-all duration-200 hover:bg-danger-700 active:scale-[0.97]"
                    >
                        Traiter &rarr;
                    </Link>
                </div>
            )}

            {/* KPIs — 2 cols mobile, 3 cols tablet, 6 cols desktop */}
            <div className="mb-8 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-6">
                <KpiCard
                    label="Interventions"
                    value={s.interventions_ce_mois > 0 ? s.interventions_ce_mois.toLocaleString('fr-FR') : '0'}
                    sub={`${s.interventions_en_cours || 0} en cours`}
                    icon={<ClipboardIcon />}
                    tone="brand"
                />
                <KpiCard
                    label="Incidents"
                    value={s.incidents_declares ?? 0}
                    sub={`${s.incidents_en_cours || 0} non clôturés`}
                    icon={<AlertIcon />}
                    tone="danger"
                />
                <KpiCard
                    label="Conformité"
                    value={s.score_conformite ? `${s.score_conformite} %` : '—'}
                    sub="vs cible 85 %"
                    icon={<BadgeIcon />}
                    tone="sage"
                    progress={s.score_conformite || 0}
                />
                <KpiCard
                    label="PAC"
                    value={s.taux_completion_pac ? `${s.taux_completion_pac} %` : '—'}
                    sub="cible > 60 %"
                    icon={<CheckListIcon />}
                    tone="brand"
                    progress={s.taux_completion_pac || 0}
                />
                <KpiCard
                    label="QVCT"
                    value={s.score_qvct_moyen ? `${s.score_qvct_moyen}/10` : '—'}
                    sub={`${alertes_qvct.length} alerte(s)`}
                    icon={<HeartIcon />}
                    tone={alertes_qvct.length > 0 ? 'warning' : 'sage'}
                    progress={(s.score_qvct_moyen || 0) * 10}
                />
                <KpiCard
                    label="Intervenants"
                    value={s.intervenants_actifs ?? 0}
                    sub={s.formations_expirant_bientot > 0 ? `${s.formations_expirant_bientot} formations expirent` : 'À jour'}
                    icon={<UsersIcon />}
                    tone={s.formations_expirant_bientot > 0 ? 'warning' : 'neutral'}
                />
            </div>

            {/* Body: incidents feed + right column */}
            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Recent incidents — takes 2/3 on large screens */}
                <Card className="lg:col-span-2">
                    <CardHeader
                        title="Incidents récents"
                        subtitle={`${incidents_recents.length} déclaration(s)`}
                        action={
                            <Link href="/incidents" className="cursor-pointer text-xs font-semibold text-brand-600 transition-colors hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                Voir tout &rarr;
                            </Link>
                        }
                    />
                    {incidents_recents.length > 0 ? (
                        <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                            {incidents_recents.map((inc) => (
                                <li key={inc.id} className="flex items-center gap-3 px-5 py-3.5 transition-colors hover:bg-ink-50/60 dark:hover:bg-ink-700/30">
                                    <IncidentAvatar initials={inc.initials} gravite={inc.gravite} />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-ink-900 dark:text-white">{inc.declarant}</p>
                                        <p className="truncate text-xs text-ink-500 dark:text-ink-400">
                                            {inc.categorie} &middot; {inc.structure}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 flex-col items-end gap-1.5">
                                        <IncidentGraviteBadge gravite={inc.gravite} />
                                        <IncidentStatusBadge statut={inc.statut} />
                                    </div>
                                    <span className="ml-1 hidden shrink-0 text-xs text-ink-400 dark:text-ink-500 sm:block">{inc.depuis}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState icon={<AlertIcon />} title="Aucun incident déclaré" description="Les incidents récents apparaîtront ici." />
                    )}
                </Card>

                {/* Right column */}
                <div className="flex flex-col gap-5">
                    {/* QVCT Alerts */}
                    <Card>
                        <CardHeader
                            title="Alertes QVCT"
                            subtitle={`${alertes_qvct.length} signaux`}
                            action={
                                <Link href="/qvct" className="cursor-pointer text-xs font-semibold text-danger-600 transition-colors hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300">
                                    Gérer &rarr;
                                </Link>
                            }
                        />
                        {alertes_qvct.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {alertes_qvct.map((a) => (
                                    <li key={a.id} className="flex items-center gap-3 px-5 py-3.5">
                                        <QvctScoreIndicator score={a.score} />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-ink-900 dark:text-white">{a.intervenant}</p>
                                            <p className="truncate text-xs text-ink-500 dark:text-ink-400">{a.signal}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-mono text-sm font-bold tabular text-warning-600 dark:text-warning-400">{a.score}/10</p>
                                            <p className="text-[11px] text-ink-400 dark:text-ink-500">{a.depuis}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <CardBody>
                                <div className="flex items-center gap-3 rounded-xl bg-sage-50/60 px-4 py-3 dark:bg-sage-900/20">
                                    <span className="flex size-7 items-center justify-center rounded-full bg-sage-100 text-sage-600 dark:bg-sage-800 dark:text-sage-400">
                                        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <p className="text-sm font-medium text-sage-700 dark:text-sage-300">
                                        Aucune alerte QVCT active
                                    </p>
                                </div>
                            </CardBody>
                        )}
                    </Card>

                    {/* Quick actions */}
                    <Card>
                        <CardHeader title="Actions rapides" />
                        <div className="p-2">
                            <QuickAction href="/incidents/create" icon={<AlertIcon />} label="Déclarer un incident" tone="danger" />
                            <QuickAction href="/interventions/create" icon={<ClipboardIcon />} label="Planifier une intervention" tone="brand" />
                            <QuickAction href="/audits/create" icon={<BadgeIcon />} label="Lancer un audit" tone="sage" />
                            <QuickAction href="/qvct/questionnaire" icon={<HeartIcon />} label="Questionnaire QVCT" tone="warning" />
                        </div>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}

/* ------------------------------------------------------------------ */
/*  Sub-components                                                     */
/* ------------------------------------------------------------------ */

const GRAVITE_AVATAR_TONE: Record<string, string> = {
    critique: 'bg-danger-100 text-danger-700 ring-2 ring-danger-300 dark:bg-danger-900/40 dark:text-danger-300 dark:ring-danger-600',
    grave: 'bg-danger-50 text-danger-600 dark:bg-danger-900/30 dark:text-danger-400',
    significatif: 'bg-warning-50 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400',
    mineur: 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300',
};

function IncidentAvatar({ initials, gravite }: { initials: string; gravite: string }) {
    return (
        <div className={`flex size-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold ${GRAVITE_AVATAR_TONE[gravite] ?? GRAVITE_AVATAR_TONE.mineur}`}>
            {initials || '?'}
        </div>
    );
}

function QvctScoreIndicator({ score }: { score: number }) {
    const tone = score <= 3
        ? 'bg-danger-50 text-danger-600 dark:bg-danger-900/40 dark:text-danger-400'
        : score <= 5
          ? 'bg-warning-50 text-warning-600 dark:bg-warning-900/40 dark:text-warning-400'
          : 'bg-sage-50 text-sage-600 dark:bg-sage-900/40 dark:text-sage-400';
    return (
        <div className={`flex size-10 shrink-0 items-center justify-center rounded-xl ${tone}`}>
            <HeartIcon />
        </div>
    );
}

function QuickAction({
    href,
    icon,
    label,
    tone,
}: {
    href: string;
    icon: React.ReactNode;
    label: string;
    tone: 'brand' | 'sage' | 'warning' | 'danger';
}) {
    const toneClass = {
        brand: 'bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300',
        sage: 'bg-sage-50 text-sage-600 dark:bg-sage-900/40 dark:text-sage-300',
        warning: 'bg-warning-50 text-warning-600 dark:bg-warning-900/40 dark:text-warning-300',
        danger: 'bg-danger-50 text-danger-600 dark:bg-danger-900/40 dark:text-danger-300',
    }[tone];
    return (
        <Link
            href={href}
            className="group flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-150 hover:bg-ink-50 active:scale-[0.98] dark:hover:bg-ink-700/40"
        >
            <span className={`flex size-9 items-center justify-center rounded-xl transition-transform duration-150 group-hover:scale-105 ${toneClass}`}>
                {icon}
            </span>
            <span className="flex-1 text-sm font-medium text-ink-700 dark:text-ink-200">{label}</span>
            <svg className="size-4 text-ink-300 transition-transform duration-150 group-hover:translate-x-0.5 dark:text-ink-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </Link>
    );
}

/* ------------------------------------------------------------------ */
/*  Icons                                                              */
/* ------------------------------------------------------------------ */

function PlusIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}

function ClipboardIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
    );
}
function AlertIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            <line x1="12" y1="9" x2="12" y2="13" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
    );
}
function BadgeIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
        </svg>
    );
}
function CheckListIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            <path d="M9 11l3 3L22 4" />
            <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
        </svg>
    );
}
function HeartIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
        </svg>
    );
}
function UsersIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
        </svg>
    );
}
