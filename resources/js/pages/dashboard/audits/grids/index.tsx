import { Badge, Button, Card, CardBody, CardHeader, EmptyState, Input, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../layout';

interface Grid {
    id: string;
    title: string;
    description: string | null;
    source: string;
    items_count: number;
}

interface Filters {
    source: string | null;
    q: string | null;
}

interface Props {
    grids: Grid[];
    filters: Filters;
    sources: Record<string, number>;
}

const SOURCE_META: Record<string, { label: string; tone: 'sage' | 'brand' | 'warning' | 'neutral' }> = {
    has: { label: 'HAS', tone: 'sage' },
    iso_9001: { label: 'ISO 9001', tone: 'brand' },
    afnor_nf_x50_056: { label: 'AFNOR', tone: 'warning' },
    custom: { label: 'Référentiel interne', tone: 'neutral' },
};

export default function AuditGridsIndex({ grids = [], filters = { source: null, q: null }, sources = {} }: Partial<Props>) {
    const [search, setSearch] = useState(filters.q ?? '');

    const applyFilter = (next: Partial<Filters>) => {
        const merged: Record<string, string> = {};
        const current = { ...filters, ...next };
        if (current.source) merged.source = current.source;
        if (current.q) merged.q = current.q;
        router.get('/audits/grids', merged, { preserveScroll: true, preserveState: true });
    };

    const submitSearch = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilter({ q: search.trim() || null });
    };

    return (
        <DashboardLayout title="Bibliothèque référentiels" subtitle="Audit grids library">
            <PageHeader
                title="Bibliothèque de référentiels"
                subtitle="Consultez les grilles HAS, ISO 9001, AFNOR et internes avant de lancer un audit"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: 'Référentiels' },
                ]}
            />

            <Card className="mb-5">
                <CardBody className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-wrap gap-2">
                        <FilterChip label="Tous" count={grids.length} active={!filters.source} onClick={() => applyFilter({ source: null })} />
                        {Object.entries(sources).map(([src, count]) => (
                            <FilterChip
                                key={src}
                                label={SOURCE_META[src]?.label ?? src.toUpperCase()}
                                count={count}
                                active={filters.source === src}
                                onClick={() => applyFilter({ source: src })}
                            />
                        ))}
                    </div>

                    <form onSubmit={submitSearch} className="flex items-center gap-2">
                        <Input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher un critère…"
                            className="h-9 w-64"
                        />
                        <Button type="submit" variant="secondary" size="sm">
                            Filtrer
                        </Button>
                        {filters.q && (
                            <Button type="button" variant="ghost" size="sm" onClick={() => { setSearch(''); applyFilter({ q: null }); }}>
                                ✕
                            </Button>
                        )}
                    </form>
                </CardBody>
            </Card>

            {grids.length > 0 ? (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {grids.map((g) => {
                        const meta = SOURCE_META[g.source] ?? { label: g.source, tone: 'neutral' as const };
                        return (
                            <Link
                                key={g.id}
                                href={`/audits/grids/${g.id}`}
                                className="group block rounded-2xl border border-ink-100 bg-white p-5 transition-all hover:border-brand-300 hover:shadow-md dark:border-ink-700/60 dark:bg-ink-800 dark:hover:border-brand-500"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <Badge tone={meta.tone} size="sm">
                                        {meta.label}
                                    </Badge>
                                    <span className="font-mono text-xs text-ink-400 dark:text-ink-500">
                                        {g.items_count} critère{g.items_count > 1 ? 's' : ''}
                                    </span>
                                </div>
                                <h3 className="mt-3 text-base font-semibold text-ink-900 group-hover:text-brand-700 dark:text-white dark:group-hover:text-brand-300">
                                    {g.title}
                                </h3>
                                {g.description && (
                                    <p className="mt-1 line-clamp-3 text-xs leading-relaxed text-ink-500 dark:text-ink-400">
                                        {g.description}
                                    </p>
                                )}
                                <span className="mt-3 inline-flex items-center gap-1 text-[11px] font-medium text-brand-600 dark:text-brand-400">
                                    Consulter →
                                </span>
                            </Link>
                        );
                    })}
                </div>
            ) : (
                <Card>
                    <EmptyState
                        icon={<ShieldIcon />}
                        title={filters.q || filters.source ? 'Aucun référentiel correspondant' : 'Aucun référentiel actif'}
                        description="Les référentiels HAS, ISO 9001 et AFNOR sont initialisés à la création de la structure. Si la bibliothèque est vide, contactez le support."
                        action={
                            (filters.q || filters.source) && (
                                <Button variant="secondary" onClick={() => router.get('/audits/grids')}>
                                    Effacer les filtres
                                </Button>
                            )
                        }
                    />
                </Card>
            )}
        </DashboardLayout>
    );
}

function FilterChip({ label, count, active, onClick }: { label: string; count: number; active: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors',
                active
                    ? 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-400 dark:bg-brand-900/30 dark:text-brand-200'
                    : 'border-ink-200 bg-white text-ink-600 hover:border-ink-300 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300 dark:hover:bg-ink-700/40',
            )}
        >
            {label}
            <span className={cn('rounded-full px-1.5 py-0.5 font-mono text-[10px] font-semibold', active ? 'bg-brand-200 text-brand-800 dark:bg-brand-800/50 dark:text-brand-100' : 'bg-ink-100 text-ink-600 dark:bg-ink-700 dark:text-ink-300')}>
                {count}
            </span>
        </button>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
        </svg>
    );
}
