import { Badge, Button, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Grid {
    id: string;
    title: string;
    description: string | null;
    source: string;
    items_count: number;
    evidence_required_count: number;
    max_points_total: number;
}

interface Item {
    id: string;
    title: string;
    description: string | null;
    scale: string;
    max_points: number;
    evidence_required: boolean;
    position: number;
}

interface Props {
    grid: Grid;
    items: Item[];
}

const SOURCE_META: Record<string, { label: string; tone: 'sage' | 'brand' | 'warning' | 'neutral' }> = {
    has: { label: 'HAS', tone: 'sage' },
    iso_9001: { label: 'ISO 9001', tone: 'brand' },
    afnor_nf_x50_056: { label: 'AFNOR NF X50-056', tone: 'warning' },
    custom: { label: 'Référentiel interne', tone: 'neutral' },
};

const SCALE_LABEL: Record<string, string> = {
    binary: 'Conforme / Non conforme',
    ternary: 'Conforme / Partiel / NC',
    score_5: 'Note 0 → 5',
    score_10: 'Note 0 → 10',
};

export default function AuditGridShow({ grid, items }: Props) {
    const meta = SOURCE_META[grid.source] ?? { label: grid.source, tone: 'neutral' as const };

    return (
        <DashboardLayout title={grid.title} subtitle={meta.label}>
            <PageHeader
                title={grid.title}
                subtitle={meta.label}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: 'Référentiels', href: '/audits/grids' },
                    { label: grid.title },
                ]}
                actions={
                    <Link href="/audits/create">
                        <Button>Lancer un audit avec ce référentiel →</Button>
                    </Link>
                }
            />

            {grid.description && (
                <p className="mb-5 max-w-4xl text-sm leading-relaxed text-ink-600 dark:text-ink-300">{grid.description}</p>
            )}

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Critères" value={grid.items_count} tone="brand" />
                <KpiCard label="Score maximum" value={grid.max_points_total.toFixed(1)} sub="points totaux" tone="sage" />
                <KpiCard
                    label="Preuves requises"
                    value={grid.evidence_required_count}
                    sub={`${Math.round((grid.evidence_required_count / Math.max(grid.items_count, 1)) * 100)}% des critères`}
                    tone="warning"
                />
                <KpiCard
                    label="Source"
                    value={meta.label}
                    tone={meta.tone}
                />
            </div>

            <Card>
                <CardHeader title="Critères" subtitle={`${items.length} item${items.length > 1 ? 's' : ''}`} />
                <CardBody className="px-0">
                    {items.length > 0 ? (
                        <ol className="divide-y divide-ink-100 dark:divide-ink-700/60">
                            {items.map((item, idx) => (
                                <li key={item.id} className="flex items-start gap-4 px-5 py-4">
                                    <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 font-mono text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                        {idx + 1}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-medium text-ink-900 dark:text-white">{item.title}</p>
                                            {item.evidence_required && (
                                                <Badge tone="warning" size="xs">
                                                    Preuve requise
                                                </Badge>
                                            )}
                                            <Badge tone="neutral" size="xs">
                                                {SCALE_LABEL[item.scale] ?? item.scale}
                                            </Badge>
                                        </div>
                                        {item.description && (
                                            <p className="mt-1 text-xs leading-relaxed text-ink-500 dark:text-ink-400">
                                                {item.description}
                                            </p>
                                        )}
                                    </div>
                                    <span className={cn('shrink-0 rounded-md bg-ink-50 px-2 py-1 font-mono text-[11px] tabular-nums text-ink-600 dark:bg-ink-700 dark:text-ink-300')}>
                                        {item.max_points} pt{item.max_points > 1 ? 's' : ''}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    ) : (
                        <EmptyState title="Aucun critère" description="Ce référentiel ne contient pas encore d'items." />
                    )}
                </CardBody>
            </Card>

            <div className="mt-5 rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                <p className="font-semibold text-brand-900 dark:text-brand-200">À savoir</p>
                <p className="mt-1 text-brand-900/80 dark:text-brand-200/80">
                    Cette bibliothèque est en lecture seule. Pour personnaliser un référentiel, lancez un audit puis
                    ajoutez des écarts spécifiques pendant l'exécution — ils seront intégrés au PDF horodaté.
                </p>
            </div>
        </DashboardLayout>
    );
}
