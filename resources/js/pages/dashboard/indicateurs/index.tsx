import {
    BarChart,
    Card,
    CardBody,
    CardHeader,
    ChartSeriesPoint,
    ChartTone,
    DonutChart,
    EmptyState,
    KpiCard,
    LineChart,
    PageHeader,
    Sparkline,
} from '@/components/ui';
import { cn } from '@/lib/utils';
import { useMemo, useState } from 'react';
import DashboardLayout from '../layout';

interface Indicateurs {
    conformite: number | null;
    pac_completion: number | null;
    qvct_moyen: number | null;
    formations_a_jour: number | null;
    incidents_ce_mois: number | null;
    interventions_ce_mois: number | null;
}

interface Series {
    key: string;
    label: string;
    tone: ChartTone;
    data: ChartSeriesPoint[];
}

interface Breakdown {
    label: string;
    value: number;
    tone: ChartTone;
}

interface Breakdowns {
    incidents_categorie: Breakdown[];
    audits_referentiel: Breakdown[];
}

interface Props {
    indicateurs: Indicateurs;
    series: Series[];
    breakdowns: Breakdowns;
}

const EMPTY_INDICATEURS: Indicateurs = {
    conformite: null,
    pac_completion: null,
    qvct_moyen: null,
    formations_a_jour: null,
    incidents_ce_mois: null,
    interventions_ce_mois: null,
};

const PERIODS = [
    { id: '7d', label: '7 jours' },
    { id: '30d', label: '30 jours' },
    { id: '3m', label: '3 mois' },
    { id: '12m', label: '12 mois' },
] as const;

type PeriodId = (typeof PERIODS)[number]['id'];

export default function IndicateursIndex({
    indicateurs = EMPTY_INDICATEURS,
    series = [],
    breakdowns = { incidents_categorie: [], audits_referentiel: [] },
}: Partial<Props>) {
    const [period, setPeriod] = useState<PeriodId>('12m');
    const [activeSeries, setActiveSeries] = useState<string | null>(null);

    const hasKpis = Object.values(indicateurs).some((v) => v !== null);
    const hasSeries = series.length > 0;

    const filteredSeries = useMemo(() => {
        if (period === '12m') return series;
        const take = period === '7d' ? 1 : period === '30d' ? 2 : period === '3m' ? 3 : series[0]?.data.length ?? 0;
        return series.map((s) => ({ ...s, data: s.data.slice(-take) }));
    }, [period, series]);

    const focusedSeries = activeSeries
        ? filteredSeries.find((s) => s.key === activeSeries)
        : filteredSeries[0];

    const sparkFor = (key: string): number[] => series.find((s) => s.key === key)?.data.map((p) => p.value) ?? [];

    const trendFor = (key: string): { value: string; positive: boolean } | undefined => {
        const data = sparkFor(key);
        if (data.length < 2) return undefined;
        const first = data[0];
        const last = data[data.length - 1];
        if (first === 0) return undefined;
        const delta = ((last - first) / first) * 100;
        const inverted = key === 'incidents';
        const positive = inverted ? delta < 0 : delta > 0;
        const sign = delta >= 0 ? '+' : '';
        return { value: `${sign}${delta.toFixed(0)}%`, positive };
    };

    return (
        <DashboardLayout title="Indicateurs" subtitle="Tableau de bord qualité & QVCT">
            <PageHeader
                title="Indicateurs & KPIs"
                subtitle="Vue consolidée des métriques qualité, QVCT, conformité et activité"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Indicateurs' }]}
                actions={<PeriodSelector value={period} onChange={setPeriod} />}
            />

            {/* KPIs avec sparklines */}
            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <KpiCard
                    label="Score de conformité"
                    value={indicateurs.conformite !== null ? `${indicateurs.conformite} %` : '—'}
                    sub="Cible : 85 %"
                    icon={<ShieldIcon />}
                    tone="sage"
                    progress={indicateurs.conformite ?? 0}
                    trend={trendFor('conformite')}
                />
                <KpiCard
                    label="Complétion PAC"
                    value={indicateurs.pac_completion !== null ? `${indicateurs.pac_completion} %` : '—'}
                    sub="Plans d'amélioration"
                    icon={<CheckListIcon />}
                    tone="brand"
                    progress={indicateurs.pac_completion ?? 0}
                />
                <KpiCard
                    label="Score QVCT moyen"
                    value={indicateurs.qvct_moyen !== null ? `${indicateurs.qvct_moyen}/10` : '—'}
                    sub="Baromètre équipe"
                    icon={<HeartIcon />}
                    tone={indicateurs.qvct_moyen !== null && indicateurs.qvct_moyen < 5 ? 'warning' : 'sage'}
                    progress={indicateurs.qvct_moyen !== null ? indicateurs.qvct_moyen * 10 : 0}
                    trend={trendFor('qvct')}
                />
                <KpiCard
                    label="Formations à jour"
                    value={indicateurs.formations_a_jour !== null ? `${indicateurs.formations_a_jour} %` : '—'}
                    sub="Certifications valides"
                    icon={<AcademicIcon />}
                    tone="brand"
                    progress={indicateurs.formations_a_jour ?? 0}
                />
                <KpiCard
                    label="Incidents ce mois"
                    value={indicateurs.incidents_ce_mois ?? '—'}
                    sub="Déclarations"
                    icon={<AlertIcon />}
                    tone="danger"
                    trend={trendFor('incidents')}
                />
                <KpiCard
                    label="Interventions ce mois"
                    value={indicateurs.interventions_ce_mois ?? '—'}
                    sub="Visites réalisées"
                    icon={<ClipboardIcon />}
                    tone="neutral"
                    trend={trendFor('interventions')}
                />
            </div>

            {/* Charts grid */}
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Trend chart */}
                <Card className="lg:col-span-2">
                    <CardHeader
                        title="Évolution temporelle"
                        subtitle={focusedSeries ? focusedSeries.label : 'Sélectionnez une série'}
                        action={
                            hasSeries && (
                                <div className="flex flex-wrap gap-1">
                                    {filteredSeries.map((s) => (
                                        <button
                                            key={s.key}
                                            type="button"
                                            onClick={() => setActiveSeries(s.key)}
                                            className={cn(
                                                'flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium transition-colors',
                                                (activeSeries ?? filteredSeries[0]?.key) === s.key
                                                    ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900'
                                                    : 'bg-ink-100 text-ink-600 hover:bg-ink-200 dark:bg-ink-700/60 dark:text-ink-300 dark:hover:bg-ink-700',
                                            )}
                                        >
                                            <span className={cn('size-1.5 rounded-full', dotTone(s.tone))} />
                                            {s.label.replace(/\s*\(.*\)$/, '')}
                                            {sparkFor(s.key).length > 0 && (
                                                <Sparkline data={sparkFor(s.key)} tone={s.tone} width={32} height={10} />
                                            )}
                                        </button>
                                    ))}
                                </div>
                            )
                        }
                    />
                    <CardBody>
                        {focusedSeries && focusedSeries.data.length > 0 ? (
                            <LineChart
                                data={focusedSeries.data}
                                tone={focusedSeries.tone}
                                ariaLabel={`Évolution de ${focusedSeries.label}`}
                                height={260}
                            />
                        ) : (
                            <EmptyState
                                icon={<ChartIcon />}
                                title="Pas encore de données"
                                description="Les indicateurs seront alimentés par les modules Audits, QVCT et Formations une fois activés."
                            />
                        )}
                    </CardBody>
                </Card>

                {/* Donut: incidents by category */}
                <Card>
                    <CardHeader title="Incidents par catégorie" subtitle="Sur la période sélectionnée" />
                    <CardBody>
                        {breakdowns.incidents_categorie.length > 0 ? (
                            <DonutChart
                                data={breakdowns.incidents_categorie}
                                centerLabel="Total"
                                ariaLabel="Répartition des incidents par catégorie"
                            />
                        ) : (
                            <EmptyState icon={<ChartIcon />} title="Pas de données" description="Aucun incident sur la période." />
                        )}
                    </CardBody>
                </Card>

                {/* Bar: audits by referential */}
                <Card>
                    <CardHeader title="Audits par référentiel" subtitle="Nombre par source" />
                    <CardBody>
                        {breakdowns.audits_referentiel.length > 0 ? (
                            <BarChart
                                data={breakdowns.audits_referentiel}
                                tone="sage"
                                height={200}
                                ariaLabel="Nombre d'audits par référentiel"
                            />
                        ) : (
                            <EmptyState icon={<ChartIcon />} title="Pas de données" description="Aucun audit finalisé." />
                        )}
                    </CardBody>
                </Card>

                {/* Multi-series small chart: interventions */}
                <Card className="lg:col-span-2">
                    <CardHeader
                        title="Activité opérationnelle"
                        subtitle="Interventions réalisées par mois"
                    />
                    <CardBody>
                        {(series.find((s) => s.key === 'interventions')?.data.length ?? 0) > 0 ? (
                            <BarChart
                                data={series.find((s) => s.key === 'interventions')?.data ?? []}
                                tone="brand"
                                height={200}
                                ariaLabel="Interventions réalisées"
                            />
                        ) : (
                            <EmptyState icon={<ChartIcon />} title="Pas de données" description="Pas encore d'interventions enregistrées." />
                        )}
                    </CardBody>
                </Card>
            </div>

            {!hasKpis && !hasSeries && (
                <Card className="mt-6">
                    <EmptyState
                        icon={<ChartIcon />}
                        title="Pas encore de données"
                        description="Les indicateurs seront alimentés par les modules Audits, QVCT et Formations dès que ceux-ci seront activés et alimentés."
                    />
                </Card>
            )}
        </DashboardLayout>
    );
}

function PeriodSelector({ value, onChange }: { value: PeriodId; onChange: (v: PeriodId) => void }) {
    return (
        <div className="inline-flex rounded-lg border border-ink-200 bg-white p-0.5 dark:border-ink-700 dark:bg-ink-800">
            {PERIODS.map((p) => (
                <button
                    key={p.id}
                    type="button"
                    onClick={() => onChange(p.id)}
                    className={cn(
                        'rounded-md px-3 py-1.5 text-xs font-medium transition-colors',
                        value === p.id
                            ? 'bg-brand-600 text-white shadow-sm'
                            : 'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-700',
                    )}
                >
                    {p.label}
                </button>
            ))}
        </div>
    );
}

function dotTone(tone: ChartTone): string {
    return {
        brand: 'bg-brand-500',
        sage: 'bg-sage-500',
        warning: 'bg-warning-500',
        danger: 'bg-danger-500',
        neutral: 'bg-ink-400',
    }[tone];
}

function ShieldIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4" />
        </svg>
    );
}
function CheckListIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 11l3 3L22 4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
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
function AcademicIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
            <path d="M6 12v5c3 3 9 3 12 0v-5" />
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
function ClipboardIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
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
