import { RpsHeatmap } from '@/components/qvct';
import {
    Card,
    CardBody,
    CardHeader,
    ChartSeriesPoint,
    EmptyState,
    KpiCard,
    LineChart,
    PageHeader,
} from '@/components/ui';
import { cn } from '@/lib/utils';
import DashboardLayout from '../../layout';

interface Dimension {
    key: string;
    label: string;
    description: string;
}

interface Cell {
    team: string;
    dimension: string;
    score: number;
}

interface Matrix {
    teams: string[];
    cells: Cell[];
}

interface Props {
    dimensions: Dimension[];
    matrix: Matrix;
    trend: ChartSeriesPoint[];
}

export default function QvctIndicators({
    dimensions = [],
    matrix = { teams: [], cells: [] },
    trend = [],
}: Partial<Props>) {
    const allScores = matrix.cells.map((c) => c.score);
    const overallAvg = allScores.length > 0 ? allScores.reduce((s, v) => s + v, 0) / allScores.length : null;
    const minScore = allScores.length > 0 ? Math.min(...allScores) : null;
    const lowestCell = matrix.cells.find((c) => c.score === minScore);
    const ANON_THRESHOLD = 5;
    const dimAverages = dimensions.map((d) => {
        const ds = matrix.cells.filter((c) => c.dimension === d.key).map((c) => c.score);
        const n = ds.length;
        const masked = n > 0 && n < ANON_THRESHOLD;
        const avg = n > 0 ? ds.reduce((s, v) => s + v, 0) / n : null;
        const min = n > 0 ? Math.min(...ds) : null;
        const max = n > 0 ? Math.max(...ds) : null;
        return { ...d, avg, min, max, n, masked };
    });

    return (
        <DashboardLayout title="Indicateurs RPS" subtitle="Cartographie par équipe et dimension">
            <PageHeader
                title="Cartographie RPS"
                subtitle="Risques psycho-sociaux croisés par équipe et dimension (charge, soutien, sens, récupération, autonomie)"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Indicateurs RPS' },
                ]}
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard
                    label="Score moyen global"
                    value={overallAvg !== null ? `${overallAvg.toFixed(1)}/10` : '—'}
                    icon={<HeartIcon />}
                    tone={overallAvg !== null && overallAvg < 6 ? 'warning' : 'sage'}
                    progress={overallAvg !== null ? overallAvg * 10 : 0}
                />
                <KpiCard
                    label="Équipes mesurées"
                    value={matrix.teams.length}
                    icon={<UsersIcon />}
                    tone="brand"
                />
                <KpiCard
                    label="Dimensions"
                    value={dimensions.length}
                    icon={<LayersIcon />}
                    tone="neutral"
                />
                <KpiCard
                    label="Point d'attention"
                    value={lowestCell ? lowestCell.dimension : '—'}
                    sub={lowestCell ? `${lowestCell.team} · ${lowestCell.score.toFixed(1)}/10` : 'Aucun'}
                    icon={<TargetIcon />}
                    tone={lowestCell && lowestCell.score < 5 ? 'danger' : 'warning'}
                />
            </div>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Heatmap */}
                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Cartographie équipes × dimensions"
                        subtitle="Survolez une cellule pour la mettre en évidence — cliquez pour zoomer (à venir)"
                    />
                    <CardBody>
                        {matrix.teams.length > 0 && dimensions.length > 0 ? (
                            <RpsHeatmap
                                teams={matrix.teams}
                                dimensions={dimensions}
                                cells={matrix.cells}
                                ariaLabel="Cartographie des risques psychosociaux par équipe et dimension"
                            />
                        ) : (
                            <EmptyState
                                icon={<LayersIcon />}
                                title="Pas encore de cartographie"
                                description="La cartographie sera générée après la clôture de la prochaine campagne."
                            />
                        )}
                    </CardBody>
                </Card>

                {/* Dimension distribution (min/avg/max, anonymisé si n<5) */}
                <Card className="lg:col-span-1">
                    <CardHeader
                        title="Distribution par dimension"
                        subtitle={`Min · Moyenne · Max sur les équipes (n≥${ANON_THRESHOLD} requis)`}
                    />
                    <CardBody className="space-y-4">
                        {dimAverages.map((d) => (
                            <div key={d.key}>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="font-medium text-ink-700 dark:text-ink-200">{d.label}</span>
                                    <span className="font-mono text-[11px] text-ink-500 dark:text-ink-400">
                                        n = {d.n}
                                    </span>
                                </div>
                                {d.masked ? (
                                    <p className="mt-1 text-[11px] italic text-ink-500 dark:text-ink-400">
                                        Données masquées (anonymat : moins de {ANON_THRESHOLD} équipes ont répondu).
                                    </p>
                                ) : d.avg === null ? (
                                    <p className="mt-1 text-[11px] italic text-ink-500 dark:text-ink-400">
                                        Aucune donnée pour cette dimension.
                                    </p>
                                ) : (
                                    <>
                                        <div className="relative mt-1.5 h-3 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                                            {d.min !== null && d.max !== null && (
                                                <div
                                                    className="absolute inset-y-0 rounded-full bg-ink-300/70 dark:bg-ink-600/80"
                                                    style={{
                                                        left: `${(d.min / 10) * 100}%`,
                                                        width: `${((d.max - d.min) / 10) * 100}%`,
                                                    }}
                                                    aria-hidden
                                                />
                                            )}
                                            {d.avg !== null && (
                                                <div
                                                    className={cn('absolute top-0 h-full w-0.5', avgMarkerColor(d.avg))}
                                                    style={{ left: `calc(${(d.avg / 10) * 100}% - 1px)` }}
                                                    aria-hidden
                                                />
                                            )}
                                        </div>
                                        <div className="mt-1 flex justify-between font-mono text-[10px] text-ink-500 dark:text-ink-400">
                                            <span>min {d.min?.toFixed(1) ?? '—'}</span>
                                            <span className="font-semibold text-ink-700 dark:text-ink-200">
                                                moy {d.avg.toFixed(1)}
                                            </span>
                                            <span>max {d.max?.toFixed(1) ?? '—'}</span>
                                        </div>
                                    </>
                                )}
                                <p className="mt-1 text-[11px] text-ink-500 dark:text-ink-400">{d.description}</p>
                            </div>
                        ))}
                    </CardBody>
                </Card>

                {/* Trend */}
                <Card className="lg:col-span-2">
                    <CardHeader title="Évolution du score global" subtitle="7 derniers mois" />
                    <CardBody>
                        {trend.length > 0 ? (
                            <LineChart data={trend} tone="brand" height={220} ariaLabel="Évolution du score global RPS" />
                        ) : (
                            <EmptyState title="Pas d'historique" description="Lancez plusieurs campagnes pour voir l'évolution." />
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function avgMarkerColor(score: number): string {
    if (score < 5) return 'bg-danger-500';
    if (score < 6.5) return 'bg-warning-500';
    if (score < 8) return 'bg-brand-500';
    return 'bg-sage-500';
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
function LayersIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <polygon points="12 2 2 7 12 12 22 7 12 2" />
            <polyline points="2 17 12 22 22 17" />
            <polyline points="2 12 12 17 22 12" />
        </svg>
    );
}
function TargetIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <circle cx="12" cy="12" r="6" />
            <circle cx="12" cy="12" r="2" />
        </svg>
    );
}
