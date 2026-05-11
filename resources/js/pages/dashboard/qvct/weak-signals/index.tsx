import { WeakSignalCard, type WeakSignalType } from '@/components/qvct';
import { Button, Card, CardBody, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../layout';

interface WeakSignal {
    id: string;
    type: WeakSignalType;
    team: string | null;
    score: number | null;
    detected_at: string;
    severity: 'critical' | 'attention';
    respondents: number;
    acknowledged: boolean;
    campaign: string;
}

interface Stats {
    total: number;
    unacknowledged: number;
    critical: number;
    this_week: number;
}

interface Props {
    signals: WeakSignal[];
    stats: Stats;
}

type Filter = 'all' | 'unack' | 'ack' | 'critical';

const FILTERS: Array<{ id: Filter; label: string }> = [
    { id: 'unack', label: 'À traiter' },
    { id: 'critical', label: 'Critiques' },
    { id: 'ack', label: 'Pris en compte' },
    { id: 'all', label: 'Tous' },
];

const TYPE_LABELS: Record<WeakSignalType, string> = {
    burnout_risk: 'Burnout',
    autonomy_loss: 'Autonomie',
    rps_cluster: 'Cluster RPS',
    engagement_drop: 'Engagement',
    other: 'Autre',
};

export default function WeakSignalsIndex({
    signals = [],
    stats = { total: 0, unacknowledged: 0, critical: 0, this_week: 0 },
}: Partial<Props>) {
    const [filter, setFilter] = useState<Filter>('unack');
    const [typeFilter, setTypeFilter] = useState<WeakSignalType | 'all'>('all');

    const filtered = useMemo(() => {
        return signals.filter((s) => {
            if (filter === 'unack' && s.acknowledged) return false;
            if (filter === 'ack' && !s.acknowledged) return false;
            if (filter === 'critical' && s.severity !== 'critical') return false;
            if (typeFilter !== 'all' && s.type !== typeFilter) return false;
            return true;
        });
    }, [signals, filter, typeFilter]);

    const types: WeakSignalType[] = ['burnout_risk', 'rps_cluster', 'autonomy_loss', 'engagement_drop', 'other'];

    const acknowledge = (id: string) => {
        router.post(`/qvct/weak-signals/${id}/acknowledge`, undefined, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Signaux faibles RPS" subtitle="Triage et suivi">
            <PageHeader
                title="Signaux faibles RPS"
                subtitle="Alertes détectées par l'analyse des baromètres QVCT"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Signaux faibles' },
                ]}
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Total détectés" value={stats.total} icon={<RadarIcon />} tone="neutral" />
                <KpiCard
                    label="À traiter"
                    value={stats.unacknowledged}
                    icon={<AlertIcon />}
                    tone={stats.unacknowledged > 0 ? 'danger' : 'sage'}
                />
                <KpiCard
                    label="Critiques"
                    value={stats.critical}
                    icon={<FlameIcon />}
                    tone={stats.critical > 0 ? 'danger' : 'sage'}
                />
                <KpiCard label="Cette semaine" value={stats.this_week} icon={<CalendarIcon />} tone="brand" />
            </div>

            {/* Filters */}
            <Card className="mb-5">
                <CardBody className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-wrap gap-2">
                        {FILTERS.map((f) => {
                            const count =
                                f.id === 'all'
                                    ? signals.length
                                    : f.id === 'unack'
                                        ? signals.filter((s) => !s.acknowledged).length
                                        : f.id === 'ack'
                                            ? signals.filter((s) => s.acknowledged).length
                                            : signals.filter((s) => s.severity === 'critical').length;
                            return (
                                <button
                                    key={f.id}
                                    type="button"
                                    onClick={() => setFilter(f.id)}
                                    className={cn(
                                        'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors',
                                        filter === f.id
                                            ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-400 dark:bg-brand-900/30 dark:text-brand-200'
                                            : 'border-ink-200 bg-white text-ink-600 hover:border-ink-300 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300 dark:hover:bg-ink-700/40',
                                    )}
                                >
                                    {f.label}
                                    <span
                                        className={cn(
                                            'rounded-full px-1.5 py-0.5 font-mono text-[10px] font-semibold',
                                            filter === f.id
                                                ? 'bg-brand-200 text-brand-800 dark:bg-brand-800/50 dark:text-brand-100'
                                                : 'bg-ink-100 text-ink-600 dark:bg-ink-700 dark:text-ink-300',
                                        )}
                                    >
                                        {count}
                                    </span>
                                </button>
                            );
                        })}
                    </div>
                    <div className="flex items-center gap-2 text-xs">
                        <span className="text-ink-500 dark:text-ink-400">Type :</span>
                        <select
                            value={typeFilter}
                            onChange={(e) => setTypeFilter(e.target.value as WeakSignalType | 'all')}
                            className="h-8 rounded-md border border-ink-200 bg-white px-2 text-xs text-ink-700 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                        >
                            <option value="all">Tous</option>
                            {types.map((t) => (
                                <option key={t} value={t}>
                                    {TYPE_LABELS[t]}
                                </option>
                            ))}
                        </select>
                    </div>
                </CardBody>
            </Card>

            {filtered.length > 0 ? (
                <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                    {filtered.map((s) => (
                        <WeakSignalCard
                            key={s.id}
                            type={s.type}
                            team={s.team}
                            score={s.score}
                            detectedAt={`${s.detected_at} · ${s.respondents} répondants · ${s.campaign}`}
                            acknowledged={s.acknowledged}
                            onAcknowledge={() => acknowledge(s.id)}
                        />
                    ))}
                </div>
            ) : (
                <Card>
                    <EmptyState
                        icon={<RadarIcon />}
                        title="Aucun signal sur ce filtre"
                        description={
                            filter === 'unack'
                                ? 'Tous les signaux ont été pris en compte. Bon travail !'
                                : "Changez de filtre pour voir d'autres signaux."
                        }
                        action={
                            filter !== 'all' ? (
                                <Button variant="secondary" onClick={() => setFilter('all')}>
                                    Voir tous les signaux
                                </Button>
                            ) : undefined
                        }
                    />
                </Card>
            )}
        </DashboardLayout>
    );
}

function RadarIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <circle cx="12" cy="12" r="5" />
            <circle cx="12" cy="12" r="1.5" />
            <line x1="12" y1="3" x2="12" y2="21" />
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
function FlameIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M12 2c1 4 4 5 4 9a4 4 0 11-8 0c0-2 1-3 2-4-1 0-2-2-2-3 2 1 3 0 4-2z" />
        </svg>
    );
}
function CalendarIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
    );
}
