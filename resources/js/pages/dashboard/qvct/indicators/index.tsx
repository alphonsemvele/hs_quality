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
    const dimAverages = dimensions.map((d) => {
        const ds = matrix.cells.filter((c) => c.dimension === d.key).map((c) => c.score);
        const avg = ds.length > 0 ? ds.reduce((s, v) => s + v, 0) / ds.length : null;
        return { ...d, avg };
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

                {/* Dimension averages */}
                <Card className="lg:col-span-1">
                    <CardHeader title="Moyenne par dimension" subtitle="Tous secteurs confondus" />
                    <CardBody className="space-y-3">
                        {dimAverages.map((d) => (
                            <div key={d.key}>
                                <div className="flex items-center justify-between text-sm">
                                    <span className="font-medium text-ink-700 dark:text-ink-200">{d.label}</span>
                                    <span className="font-mono text-xs font-semibold tabular-nums text-ink-900 dark:text-white">
                                        {d.avg !== null ? d.avg.toFixed(1) : '—'}
                                    </span>
                                </div>
                                <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                                    <div
                                        className={barColor(d.avg ?? 0)}
                                        style={{ width: `${((d.avg ?? 0) / 10) * 100}%`, height: '100%' }}
                                    />
                                </div>
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

function barColor(score: number): string {
    if (score < 5) return 'bg-danger-500 h-full rounded-full';
    if (score < 6.5) return 'bg-warning-500 h-full rounded-full';
    if (score < 8) return 'bg-brand-500 h-full rounded-full';
    return 'bg-sage-500 h-full rounded-full';
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
