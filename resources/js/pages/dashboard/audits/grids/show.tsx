import { Badge, Button, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { useMemo } from 'react';
import DashboardLayout from '../../layout';

interface Grid {
    id: string;
    title: string;
    description: string | null;
    source: string;
    items_count: number;
    evidence_required_count: number;
    imperatif_count: number;
    max_points_total: number;
}

interface Axis {
    id: string;
    code: string;
    title: string;
    position: number;
}

interface Item {
    id: string;
    axis_id: string | null;
    title: string;
    description: string | null;
    scale: string;
    max_points: number;
    evidence_required: boolean;
    position: number;
    level: 'S' | 'I' | '+' | null;
    sources: string[] | null;
}

interface Props {
    grid: Grid;
    axes: Axis[];
    items: Item[];
    can?: { update: boolean; delete: boolean };
}

const SOURCE_META: Record<string, { label: string; tone: 'sage' | 'brand' | 'warning' | 'neutral' }> = {
    has: { label: 'HAS — Référentiel unifié SAP', tone: 'sage' },
    iso_9001: { label: 'ISO 9001', tone: 'brand' },
    afnor_x50_056: { label: 'AFNOR NF X50-056', tone: 'warning' },
    custom: { label: 'Référentiel interne', tone: 'neutral' },
};

const SCALE_LABEL: Record<string, string> = {
    binary: 'Oui / Non',
    '1_to_5': 'Échelle 1-5',
    percentage: 'Pourcentage',
    has_cotation: 'Cotation HAS (A/B/C/D/NA)',
};

const LEVEL_META: Record<string, { label: string; tone: 'neutral' | 'danger' | 'brand' }> = {
    S: { label: 'Standard', tone: 'neutral' },
    I: { label: 'Impératif HAS', tone: 'danger' },
    '+': { label: 'Supra-réglementaire', tone: 'brand' },
};

const SOURCE_BADGE_TONE: Record<string, 'sage' | 'warning' | 'brand'> = {
    HAS: 'sage',
    AFNOR: 'warning',
    CAP_HANDEO: 'brand',
};

const SOURCE_BADGE_LABEL: Record<string, string> = {
    HAS: 'HAS',
    AFNOR: 'AFNOR',
    CAP_HANDEO: "Cap'Handéo",
};

export default function AuditGridShow({ grid, axes, items, can }: Props) {
    const meta = SOURCE_META[grid.source] ?? { label: grid.source, tone: 'neutral' as const };
    const canUpdate = can?.update ?? false;

    const grouped = useMemo<Array<{ axis: Axis | null; items: Item[] }>>(() => {
        if (axes.length === 0) {
            return [{ axis: null, items }];
        }
        const byAxis = new Map<string, Item[]>();
        items.forEach((it) => {
            const key = it.axis_id ?? '__none__';
            if (!byAxis.has(key)) byAxis.set(key, []);
            byAxis.get(key)!.push(it);
        });
        const sortedAxes = [...axes].sort((a, b) => a.position - b.position);
        const groups: Array<{ axis: Axis | null; items: Item[] }> = sortedAxes.map((a) => ({
            axis: a,
            items: (byAxis.get(a.id) ?? []).sort((x, y) => x.position - y.position),
        }));
        const orphans = byAxis.get('__none__') ?? [];
        if (orphans.length > 0) groups.push({ axis: null, items: orphans });
        return groups;
    }, [axes, items]);

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
                    <div className="flex items-center gap-2">
                        {canUpdate && (
                            <Link href={`/audits/grids/${grid.id}/edit`}>
                                <Button variant="secondary">Éditer</Button>
                            </Link>
                        )}
                        <Link href="/audits/create">
                            <Button>Lancer un audit avec ce référentiel →</Button>
                        </Link>
                    </div>
                }
            />

            {grid.description && (
                <p className="mb-5 max-w-4xl text-sm leading-relaxed text-ink-600 dark:text-ink-300">{grid.description}</p>
            )}

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Exigences" value={grid.items_count} tone="brand" />
                <KpiCard
                    label="Impératifs HAS"
                    value={grid.imperatif_count}
                    sub={grid.imperatif_count > 0 ? 'priorité absolue' : 'aucun'}
                    tone={grid.imperatif_count > 0 ? 'danger' : 'neutral'}
                />
                <KpiCard
                    label="Preuves requises"
                    value={grid.evidence_required_count}
                    sub={`${Math.round((grid.evidence_required_count / Math.max(grid.items_count, 1)) * 100)}% des exigences`}
                    tone="warning"
                />
                <KpiCard label="Score maximum" value={grid.max_points_total.toFixed(1)} sub="points totaux" tone="sage" />
            </div>

            {grouped.map((group, gi) => (
                <Card key={group.axis?.id ?? `g-${gi}`} className="mb-5">
                    <CardHeader
                        title={group.axis ? `Axe ${group.axis.position} — ${group.axis.title}` : 'Exigences'}
                        subtitle={`${group.items.length} exigence${group.items.length > 1 ? 's' : ''}`}
                    />
                    <CardBody className="px-0">
                        {group.items.length > 0 ? (
                            <ol className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {group.items.map((item) => {
                                    const levelMeta = item.level ? LEVEL_META[item.level] : null;
                                    return (
                                        <li key={item.id} className="flex items-start gap-4 px-5 py-4">
                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 font-mono text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                                {item.position}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="text-sm font-medium text-ink-900 dark:text-white">{item.title}</p>
                                                    {levelMeta && (
                                                        <Badge tone={levelMeta.tone} size="xs">
                                                            {levelMeta.label}
                                                        </Badge>
                                                    )}
                                                    {(item.sources ?? []).map((src) => (
                                                        <Badge key={src} tone={SOURCE_BADGE_TONE[src] ?? 'neutral'} size="xs">
                                                            {SOURCE_BADGE_LABEL[src] ?? src}
                                                        </Badge>
                                                    ))}
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
                                    );
                                })}
                            </ol>
                        ) : (
                            <EmptyState title="Aucune exigence" description="Cet axe ne contient pas encore d'items." />
                        )}
                    </CardBody>
                </Card>
            ))}

            <div className="mt-5 rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                <p className="font-semibold text-brand-900 dark:text-brand-200">À savoir</p>
                <p className="mt-1 text-brand-900/80 dark:text-brand-200/80">
                    Ce référentiel est en lecture seule. Pour conduire une auto-évaluation, lancez un audit ; chaque exigence
                    sera cotée A / B / C / D / NA selon le système HAS. Les exigences cotées C ou D, en particulier les
                    Impératifs HAS, déclencheront automatiquement un plan d'action correctif.
                </p>
            </div>
        </DashboardLayout>
    );
}
