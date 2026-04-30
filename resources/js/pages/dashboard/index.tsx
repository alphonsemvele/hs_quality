import {
    Card,
    CardBody,
    CardHeader,
    EmptyState,
    IncidentGraviteBadge,
    IncidentStatusBadge,
    KpiCard,
    PageHeader,
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

export default function Dashboard({
    stats = EMPTY_STATS,
    incidents_recents = [],
    alertes_qvct = [],
}: Partial<Props>) {
    const s = stats ?? EMPTY_STATS;
    const incidentsGraves = incidents_recents.filter((i) => i.gravite === 'grave' || i.gravite === 'critique');

    return (
        <DashboardLayout title="Tableau de bord" subtitle="Pilotage qualité & QVCT — vue consolidée">
            <PageHeader
                title="Vue consolidée"
                subtitle="Vos indicateurs et alertes du moment"
            />

            {/* Bandeau alerte incidents graves */}
            {incidentsGraves.length > 0 && (
                <div className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-danger-200 bg-danger-50 p-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 shrink-0 animate-pulse items-center justify-center rounded-xl bg-danger-500 text-white">
                            <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p className="text-sm font-semibold text-danger-700">
                                {incidentsGraves.length} incident{incidentsGraves.length > 1 ? 's' : ''} grave{incidentsGraves.length > 1 ? 's' : ''}
                                {' / critique'}
                                {incidentsGraves.length > 1 ? 's' : ''} en cours
                            </p>
                            <p className="mt-0.5 text-xs text-danger-700/80">
                                {incidentsGraves.map((i) => i.declarant).join(' · ')}
                            </p>
                        </div>
                    </div>
                    <Link
                        href="/incidents"
                        className="rounded-lg bg-danger-600 px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-danger-700"
                    >
                        Traiter →
                    </Link>
                </div>
            )}

            {/* KPIs */}
            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
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
                    value={s.score_conformite ? `${s.score_conformite}%` : '—'}
                    sub="vs cible 85%"
                    icon={<BadgeIcon />}
                    tone="sage"
                    progress={s.score_conformite || 0}
                />
                <KpiCard
                    label="PAC"
                    value={s.taux_completion_pac ? `${s.taux_completion_pac}%` : '—'}
                    sub="cible > 60%"
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

            {/* Body */}
            <div className="grid grid-cols-1 gap-5 xl:grid-cols-5">
                {/* Incidents récents */}
                <Card className="xl:col-span-3">
                    <CardHeader
                        title="Incidents récents"
                        subtitle={`${incidents_recents.length} déclaration(s)`}
                        action={
                            <Link href="/incidents" className="text-xs font-medium text-brand-600 hover:text-brand-700">
                                Voir tout →
                            </Link>
                        }
                    />
                    {incidents_recents.length > 0 ? (
                        <ul className="divide-y divide-ink-100">
                            {incidents_recents.map((inc) => (
                                <li key={inc.id} className="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-ink-50/50">
                                    <Avatar initials={inc.initials} />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-ink-900">{inc.declarant}</p>
                                        <p className="truncate text-xs text-ink-500">
                                            {inc.categorie} · {inc.structure}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 flex-col items-end gap-1">
                                        <IncidentGraviteBadge gravite={inc.gravite} />
                                        <IncidentStatusBadge statut={inc.statut} />
                                    </div>
                                    <span className="ml-2 shrink-0 font-mono text-[11px] text-ink-400">{inc.depuis}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState icon={<AlertIcon />} title="Aucun incident déclaré" description="Les incidents récents apparaîtront ici." />
                    )}
                </Card>

                {/* Colonne droite */}
                <div className="flex flex-col gap-5 xl:col-span-2">
                    <Card>
                        <CardHeader
                            title="Alertes QVCT"
                            subtitle={`${alertes_qvct.length} signaux`}
                            action={
                                <Link href="/qvct" className="text-xs font-medium text-danger-600 hover:text-danger-700">
                                    Gérer →
                                </Link>
                            }
                        />
                        {alertes_qvct.length > 0 ? (
                            <ul className="divide-y divide-ink-100">
                                {alertes_qvct.map((a) => (
                                    <li key={a.id} className="flex items-center gap-3 px-5 py-3">
                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-warning-50 text-warning-600">
                                            <HeartIcon />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-ink-900">{a.intervenant}</p>
                                            <p className="truncate text-xs text-ink-500">{a.signal}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-mono text-sm font-semibold text-warning-600">{a.score}/10</p>
                                            <p className="text-[11px] text-ink-400">il y a {a.depuis}</p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <CardBody>
                                <p className="flex items-center gap-2 text-sm text-sage-700">
                                    <span className="flex size-6 items-center justify-center rounded-full bg-sage-100">
                                        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    Aucune alerte QVCT active.
                                </p>
                            </CardBody>
                        )}
                    </Card>

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

function Avatar({ initials }: { initials: string }) {
    return (
        <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700">
            {initials || '?'}
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
        brand: 'bg-brand-50 text-brand-600',
        sage: 'bg-sage-50 text-sage-600',
        warning: 'bg-warning-50 text-warning-600',
        danger: 'bg-danger-50 text-danger-600',
    }[tone];
    return (
        <Link href={href} className="flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors hover:bg-ink-50">
            <span className={`flex size-8 items-center justify-center rounded-lg ${toneClass}`}>{icon}</span>
            <span className="flex-1 text-sm font-medium text-ink-700">{label}</span>
            <svg className="size-3.5 text-ink-300" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
            </svg>
        </Link>
    );
}

// Icons
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
