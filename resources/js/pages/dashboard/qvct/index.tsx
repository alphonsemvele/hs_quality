import {
    Badge,
    Button,
    Card,
    CardBody,
    CardHeader,
    ChartSeriesPoint,
    ChartTone,
    EmptyState,
    KpiCard,
    LineChart,
    PageHeader,
    Sparkline,
} from '@/components/ui';
import { WeakSignalCard, type WeakSignalType } from '@/components/qvct';
import { useCan } from '@/lib/can';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Campagne {
    id: string;
    titre: string;
    date_debut: string;
    date_fin: string | null;
    statut: 'active' | 'terminee' | 'planifiee';
    statut_label: string;
    taux_participation: number;
    score_moyen: number | null;
    nb_reponses: number;
    nb_alertes: number;
}

interface Stats {
    score_moyen: number | null;
    taux_participation: number | null;
    alertes_actives: number;
    derniere_campagne: string | null;
}

interface WeakSignal {
    id: string;
    type: WeakSignalType;
    team: string | null;
    score: number | null;
    detected_at: string;
    acknowledged: boolean;
}

interface TeamRow {
    team: string;
    score: number;
    response_rate: number;
    tone: ChartTone;
}

interface Props {
    campagnes: Campagne[];
    stats: Stats;
    trend: ChartSeriesPoint[];
    weak_signals: WeakSignal[];
    team_breakdown: TeamRow[];
}

const STATUT_TONE: Record<string, 'sage' | 'warning' | 'brand'> = {
    active: 'sage',
    terminee: 'brand',
    planifiee: 'warning',
};

export default function QvctIndex({
    campagnes = [],
    stats = { score_moyen: null, taux_participation: null, alertes_actives: 0, derniere_campagne: null },
    trend = [],
    weak_signals = [],
    team_breakdown = [],
}: Partial<Props>) {
    const canManage = useCan('qvct.manage');
    const activeSignals = weak_signals.filter((w) => !w.acknowledged);

    return (
        <DashboardLayout title="Baromètre QVCT" subtitle="Qualité de Vie et Conditions de Travail">
            <PageHeader
                title="Baromètre QVCT"
                subtitle="Campagnes, signaux faibles RPS et cartographie par équipe"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'QVCT' }]}
                actions={
                    canManage ? (
                        <Link href="/qvct/questionnaire">
                            <Button leadingIcon={<PlusIcon />}>Nouvelle campagne</Button>
                        </Link>
                    ) : null
                }
            />

            {/* KPI grid */}
            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard
                    label="Score moyen"
                    value={stats.score_moyen !== null ? `${stats.score_moyen}/10` : '—'}
                    icon={<HeartIcon />}
                    tone={stats.score_moyen !== null && stats.score_moyen < 5 ? 'warning' : 'sage'}
                    progress={stats.score_moyen !== null ? stats.score_moyen * 10 : 0}
                    sub="Échelle 0-10"
                />
                <KpiCard
                    label="Participation"
                    value={stats.taux_participation !== null ? `${stats.taux_participation} %` : '—'}
                    icon={<UsersIcon />}
                    tone="brand"
                    progress={stats.taux_participation ?? 0}
                />
                <KpiCard
                    label="Alertes actives"
                    value={activeSignals.length}
                    icon={<AlertIcon />}
                    tone={activeSignals.length > 0 ? 'danger' : 'sage'}
                    sub="Signaux faibles non traités"
                />
                <KpiCard
                    label="Dernière campagne"
                    value={stats.derniere_campagne ?? '—'}
                    icon={<CalendarIcon />}
                    tone="neutral"
                />
            </div>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Trend chart */}
                <Card className="lg:col-span-2">
                    <CardHeader
                        title="Évolution du score QVCT"
                        subtitle="7 derniers mois — moyenne tous secteurs"
                        action={
                            trend.length >= 2 && (
                                <Sparkline data={trend.map((p) => p.value)} tone="brand" width={64} height={20} />
                            )
                        }
                    />
                    <CardBody>
                        {trend.length > 0 ? (
                            <LineChart data={trend} tone="brand" height={240} ariaLabel="Évolution du score QVCT" />
                        ) : (
                            <EmptyState icon={<ChartIcon />} title="Pas encore d'historique" description="Lancez votre première campagne pour démarrer le baromètre." />
                        )}
                    </CardBody>
                </Card>

                {/* Weak signals */}
                <Card>
                    <CardHeader
                        title="Signaux faibles RPS"
                        subtitle={`${activeSignals.length} à traiter`}
                        action={
                            <Link
                                href="/qvct"
                                className="text-[11px] font-medium text-brand-600 hover:underline dark:text-brand-400"
                            >
                                Tout voir
                            </Link>
                        }
                    />
                    <CardBody className="space-y-3">
                        {activeSignals.length > 0 ? (
                            activeSignals.slice(0, 3).map((w) => (
                                <WeakSignalCard
                                    key={w.id}
                                    type={w.type}
                                    team={w.team}
                                    score={w.score}
                                    detectedAt={w.detected_at}
                                    acknowledged={w.acknowledged}
                                />
                            ))
                        ) : (
                            <EmptyState
                                icon={<CheckIcon />}
                                title="Aucune alerte active"
                                description="Tous les signaux faibles ont été pris en compte."
                            />
                        )}
                    </CardBody>
                </Card>

                {/* Team breakdown */}
                <Card className="lg:col-span-3">
                    <CardHeader title="Cartographie par équipe" subtitle="Score moyen et taux de réponse de la dernière campagne" />
                    <CardBody className="space-y-2">
                        {team_breakdown.length > 0 ? (
                            team_breakdown.map((t) => (
                                <TeamRowItem key={t.team} row={t} />
                            ))
                        ) : (
                            <EmptyState icon={<UsersIcon />} title="Pas de données" description="Pas encore de résultats par équipe." />
                        )}
                    </CardBody>
                </Card>

                {/* Campaigns */}
                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Campagnes"
                        subtitle={`${campagnes.length} campagne(s)`}
                        action={
                            canManage ? (
                                <Link href="/qvct/questionnaire">
                                    <Button size="sm" variant="secondary" leadingIcon={<PlusIcon />}>Nouvelle</Button>
                                </Link>
                            ) : undefined
                        }
                    />
                    <CardBody>
                        {campagnes.length > 0 ? (
                            <ul className="space-y-2">
                                {campagnes.map((c) => (
                                    <li key={c.id}>
                                        <article className="flex items-start justify-between gap-4 rounded-xl border border-ink-100 p-3.5 transition-colors hover:bg-ink-50 dark:border-ink-700/60 dark:hover:bg-ink-700/30">
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{c.titre}</h3>
                                                    <Badge tone={STATUT_TONE[c.statut] ?? 'neutral'} size="sm" dot>
                                                        {c.statut_label}
                                                    </Badge>
                                                    {c.nb_alertes > 0 && (
                                                        <Badge tone="danger" size="xs">
                                                            {c.nb_alertes} alerte{c.nb_alertes > 1 ? 's' : ''}
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-ink-500 dark:text-ink-400">
                                                    <span className="font-mono">
                                                        {c.date_debut}
                                                        {c.date_fin ? ` → ${c.date_fin}` : ' (en cours)'}
                                                    </span>
                                                    <span>{c.nb_reponses} réponse{c.nb_reponses > 1 ? 's' : ''}</span>
                                                    <span>Participation : {c.taux_participation}%</span>
                                                </div>
                                            </div>
                                            {c.score_moyen !== null && (
                                                <div className="text-right">
                                                    <p className="font-mono text-lg font-bold tabular-nums text-ink-900 dark:text-white">
                                                        {c.score_moyen.toFixed(1)}
                                                        <span className="text-xs text-ink-400">/10</span>
                                                    </p>
                                                </div>
                                            )}
                                        </article>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                icon={<HeartIcon />}
                                title="Aucune campagne QVCT"
                                description="Lancez votre première campagne de baromètre pour mesurer la qualité de vie au travail de vos équipes."
                                action={
                                    canManage ? (
                                        <Link href="/qvct/questionnaire">
                                            <Button>Lancer une campagne</Button>
                                        </Link>
                                    ) : undefined
                                }
                            />
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function TeamRowItem({ row }: { row: TeamRow }) {
    const lowScore = row.score < 6;
    return (
        <div className="grid grid-cols-12 items-center gap-3 rounded-lg px-2 py-2.5 hover:bg-ink-50 dark:hover:bg-ink-700/30">
            <div className="col-span-12 sm:col-span-3">
                <p className="text-sm font-medium text-ink-900 dark:text-white">{row.team}</p>
                <p className="text-[11px] text-ink-500 dark:text-ink-400">{row.response_rate}% de participation</p>
            </div>
            <div className="col-span-9 sm:col-span-7">
                <div className="relative h-2.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                    <div
                        className={cn(
                            'h-full rounded-full transition-all duration-700 ease-out',
                            scoreBar(row.score),
                        )}
                        style={{ width: `${(row.score / 10) * 100}%` }}
                    />
                </div>
            </div>
            <div className="col-span-3 sm:col-span-2 text-right">
                <span className={cn('font-mono text-sm font-bold tabular-nums', lowScore ? 'text-warning-600 dark:text-warning-400' : 'text-ink-900 dark:text-white')}>
                    {row.score.toFixed(1)}
                </span>
                <span className="ml-0.5 text-[11px] text-ink-400">/10</span>
            </div>
        </div>
    );
}

function scoreBar(score: number): string {
    if (score < 5) return 'bg-danger-500';
    if (score < 6.5) return 'bg-warning-500';
    if (score < 8) return 'bg-brand-500';
    return 'bg-sage-500';
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}
function HeartIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
        </svg>
    );
}
function UsersIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
        </svg>
    );
}
function AlertIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            <line x1="12" y1="9" x2="12" y2="13" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
    );
}
function CalendarIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <line x1="16" y1="2" x2="16" y2="6" /><line x1="8" y1="2" x2="8" y2="6" /><line x1="3" y1="10" x2="21" y2="10" />
        </svg>
    );
}
function CheckIcon() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function ChartIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M18 20V10M12 20V4M6 20v-6" />
        </svg>
    );
}
