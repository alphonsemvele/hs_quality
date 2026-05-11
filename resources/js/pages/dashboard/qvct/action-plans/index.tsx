import { Badge, Button, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../layout';

interface Plan {
    id: string;
    title: string;
    status: 'draft' | 'published' | 'closed';
    status_label: string;
    published_at: string | null;
    closed_at: string | null;
    owner: string;
    items_total: number;
    items_done: number;
    items_in_progress: number;
    impact_target: string;
    tone: 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';
}

interface Stats {
    total: number;
    open: number;
    closed: number;
    items_in_progress: number;
}

interface Props {
    plans: Plan[];
    stats: Stats;
}

type Filter = 'all' | 'open' | 'draft' | 'closed';

const FILTERS: Array<{ id: Filter; label: string }> = [
    { id: 'open', label: 'En cours' },
    { id: 'draft', label: 'Brouillons' },
    { id: 'closed', label: 'Clos' },
    { id: 'all', label: 'Tous' },
];

const STATUS_TONE: Record<Plan['status'], 'sage' | 'warning' | 'brand'> = {
    published: 'sage',
    draft: 'warning',
    closed: 'brand',
};

export default function ActionPlansIndex({
    plans = [],
    stats = { total: 0, open: 0, closed: 0, items_in_progress: 0 },
}: Partial<Props>) {
    const [filter, setFilter] = useState<Filter>('open');
    const canManage = useCan('qvct.manage');

    const filtered = plans.filter((p) => {
        if (filter === 'all') return true;
        if (filter === 'open') return p.status === 'published';
        if (filter === 'draft') return p.status === 'draft';
        if (filter === 'closed') return p.status === 'closed';
        return true;
    });

    return (
        <DashboardLayout title="Plans d'action QVCT" subtitle="Mesurer l'impact des actions RH">
            <PageHeader
                title="Plans d'action QVCT"
                subtitle="Définissez des actions concrètes et mesurez leur impact sur les indicateurs"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: "Plans d'action" },
                ]}
                actions={
                    canManage ? (
                        <Link href="/qvct/action-plans">
                            <Button leadingIcon={<PlusIcon />}>Nouveau plan</Button>
                        </Link>
                    ) : null
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Total" value={stats.total} icon={<ClipboardIcon />} tone="neutral" />
                <KpiCard label="En cours" value={stats.open} icon={<PlayIcon />} tone="sage" />
                <KpiCard label="Clos" value={stats.closed} icon={<CheckIcon />} tone="brand" />
                <KpiCard label="Items en progression" value={stats.items_in_progress} icon={<RefreshIcon />} tone="brand" />
            </div>

            {/* Filters */}
            <div className="mb-5 flex flex-wrap gap-2">
                {FILTERS.map((f) => {
                    const count =
                        f.id === 'all'
                            ? plans.length
                            : f.id === 'open'
                                ? plans.filter((p) => p.status === 'published').length
                                : f.id === 'draft'
                                    ? plans.filter((p) => p.status === 'draft').length
                                    : plans.filter((p) => p.status === 'closed').length;
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

            {filtered.length > 0 ? (
                <ul className="space-y-3">
                    {filtered.map((p) => (
                        <li key={p.id}>
                            <PlanRow plan={p} />
                        </li>
                    ))}
                </ul>
            ) : (
                <Card>
                    <EmptyState
                        icon={<ClipboardIcon />}
                        title="Aucun plan d'action"
                        description={
                            filter === 'open'
                                ? "Lancez un plan d'action pour traiter un signal faible ou améliorer un indicateur."
                                : 'Changez de filtre pour voir d\'autres plans.'
                        }
                        action={
                            canManage && filter !== 'all' ? (
                                <Button variant="secondary" onClick={() => setFilter('all')}>
                                    Voir tous les plans
                                </Button>
                            ) : undefined
                        }
                    />
                </Card>
            )}
        </DashboardLayout>
    );
}

function PlanRow({ plan }: { plan: Plan }) {
    const progress = plan.items_total > 0 ? (plan.items_done / plan.items_total) * 100 : 0;
    return (
        <Card>
            <CardBody>
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{plan.title}</h3>
                            <Badge tone={STATUS_TONE[plan.status]} size="sm" dot>
                                {plan.status_label}
                            </Badge>
                        </div>
                        <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                            <span className="font-medium text-ink-700 dark:text-ink-300">Pilote :</span> {plan.owner}
                        </p>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            <span className="font-medium text-ink-700 dark:text-ink-300">Objectif d'impact :</span>{' '}
                            {plan.impact_target}
                        </p>
                    </div>

                    <div className="flex-1 lg:max-w-md">
                        <div className="flex items-center justify-between text-[11px] text-ink-500 dark:text-ink-400">
                            <span className="font-medium">
                                {plan.items_done}/{plan.items_total} items
                                {plan.items_in_progress > 0 && (
                                    <span className="ml-1.5 text-brand-600 dark:text-brand-400">
                                        ({plan.items_in_progress} en cours)
                                    </span>
                                )}
                            </span>
                            <span className="font-mono tabular-nums">{progress.toFixed(0)}%</span>
                        </div>
                        <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                            <div
                                className={cn(
                                    'h-full rounded-full transition-all duration-700',
                                    progress === 100 ? 'bg-sage-500' : 'bg-brand-500',
                                )}
                                style={{ width: `${progress}%` }}
                            />
                        </div>
                        <p className="mt-1.5 font-mono text-[11px] text-ink-400 dark:text-ink-500">
                            {plan.published_at && `Publié ${plan.published_at}`}
                            {plan.closed_at && ` · Clos ${plan.closed_at}`}
                            {!plan.published_at && !plan.closed_at && 'Brouillon — non publié'}
                        </p>
                    </div>
                </div>
            </CardBody>
        </Card>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
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
function PlayIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <polygon points="5 3 19 12 5 21 5 3" strokeLinejoin="round" />
        </svg>
    );
}
function CheckIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function RefreshIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <polyline points="1 4 1 10 7 10" /><polyline points="23 20 23 14 17 14" />
            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" />
        </svg>
    );
}
