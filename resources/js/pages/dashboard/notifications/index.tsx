import { Badge, Button, Card, CardBody, EmptyState, PageHeader, RelativeTime } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import DashboardLayout from '../layout';

type Status = 'all' | 'unread' | 'read';
type Level = 'all' | 'info' | 'success' | 'warning' | 'danger';

interface NotificationItem {
    id: string;
    type: string;
    title: string;
    message: string | null;
    href: string | null;
    level: 'info' | 'success' | 'warning' | 'danger';
    created_at: string | null;
    read_at: string | null;
}

interface Pagination {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    links: { prev: string | null; next: string | null };
}

interface Filters {
    status: Status;
    level: Level;
    q: string;
}

interface Props {
    items: NotificationItem[];
    pagination: Pagination;
    filters: Filters;
    unread_total: number;
}

const LEVEL_TONE: Record<NotificationItem['level'], 'brand' | 'sage' | 'warning' | 'danger'> = {
    info: 'brand',
    success: 'sage',
    warning: 'warning',
    danger: 'danger',
};

const STATUS_OPTIONS: { value: Status; label: string }[] = [
    { value: 'all', label: 'Toutes' },
    { value: 'unread', label: 'Non lues' },
    { value: 'read', label: 'Lues' },
];

const LEVEL_OPTIONS: { value: Level; label: string }[] = [
    { value: 'all', label: 'Tous niveaux' },
    { value: 'danger', label: 'Urgent' },
    { value: 'warning', label: 'Vigilance' },
    { value: 'info', label: 'Info' },
    { value: 'success', label: 'Succès' },
];

export default function NotificationsIndex({
    items = [],
    pagination,
    filters,
    unread_total = 0,
}: Partial<Props>) {
    const [q, setQ] = useState(filters?.q ?? '');

    const applyFilter = (next: Partial<Filters>) => {
        router.get(
            '/notifications',
            {
                status: next.status ?? filters?.status ?? 'all',
                level: next.level ?? filters?.level ?? 'all',
                q: next.q !== undefined ? next.q : (filters?.q ?? ''),
            },
            { preserveScroll: true, preserveState: true },
        );
    };

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilter({ q });
    };

    const clearFilters = () => {
        setQ('');
        router.get('/notifications', {}, { preserveScroll: true });
    };

    const markRead = (id: string) => {
        router.post(`/notifications/${id}/read`, undefined, { preserveScroll: true });
    };

    const remove = (id: string) => {
        if (!window.confirm('Supprimer cette notification ?')) return;
        router.delete(`/notifications/${id}`, { preserveScroll: true });
    };

    const markAll = () => {
        router.post('/notifications/read-all', undefined, { preserveScroll: true });
    };

    const hasFilters =
        (filters?.status && filters.status !== 'all') ||
        (filters?.level && filters.level !== 'all') ||
        (filters?.q ?? '') !== '';

    return (
        <DashboardLayout title="Notifications" subtitle="Centre des alertes et événements">
            <PageHeader
                title="Notifications"
                subtitle={
                    unread_total > 0
                        ? `${unread_total} notification${unread_total > 1 ? 's' : ''} non lue${unread_total > 1 ? 's' : ''}`
                        : 'Vous êtes à jour'
                }
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Notifications' }]}
                actions={
                    unread_total > 0 ? (
                        <Button variant="secondary" onClick={markAll}>
                            Tout marquer comme lu
                        </Button>
                    ) : undefined
                }
            />

            <Card>
                <CardBody className="space-y-4">
                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex flex-wrap items-center gap-2">
                            {STATUS_OPTIONS.map((opt) => (
                                <FilterChip
                                    key={opt.value}
                                    label={opt.label}
                                    active={(filters?.status ?? 'all') === opt.value}
                                    onClick={() => applyFilter({ status: opt.value })}
                                />
                            ))}
                            <span className="mx-2 h-5 w-px bg-ink-200 dark:bg-ink-700" aria-hidden />
                            {LEVEL_OPTIONS.map((opt) => (
                                <FilterChip
                                    key={opt.value}
                                    label={opt.label}
                                    active={(filters?.level ?? 'all') === opt.value}
                                    onClick={() => applyFilter({ level: opt.value })}
                                />
                            ))}
                        </div>

                        <form onSubmit={submitSearch} className="flex w-full items-center gap-2 lg:w-auto">
                            <input
                                type="search"
                                value={q}
                                onChange={(e) => setQ(e.target.value)}
                                placeholder="Rechercher dans les notifications…"
                                aria-label="Rechercher dans les notifications"
                                className="h-9 flex-1 rounded-lg border border-ink-200 bg-white px-3 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 lg:w-80 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                            />
                            <Button type="submit" variant="secondary" size="sm">
                                Filtrer
                            </Button>
                        </form>
                    </div>

                    {hasFilters && (
                        <button
                            type="button"
                            onClick={clearFilters}
                            className="text-[11px] font-medium text-ink-500 underline-offset-2 hover:text-ink-800 hover:underline dark:text-ink-400 dark:hover:text-white"
                        >
                            Réinitialiser les filtres
                        </button>
                    )}

                    {items.length === 0 ? (
                        <EmptyState
                            icon={<BellIcon />}
                            title="Aucune notification"
                            description={
                                hasFilters
                                    ? 'Aucune notification ne correspond à vos critères. Essayez de changer les filtres.'
                                    : 'Les alertes ARS, expirations de certification et signaux faibles apparaîtront ici.'
                            }
                        />
                    ) : (
                        <ul className="-mx-3 divide-y divide-ink-100 dark:divide-ink-700/60">
                            {items.map((n) => (
                                <NotificationRow
                                    key={n.id}
                                    item={n}
                                    onRead={() => markRead(n.id)}
                                    onDelete={() => remove(n.id)}
                                />
                            ))}
                        </ul>
                    )}

                    {pagination && pagination.last_page > 1 && (
                        <div className="flex items-center justify-between border-t border-ink-100 pt-3 text-xs text-ink-500 dark:border-ink-700/60 dark:text-ink-400">
                            <p>
                                Page {pagination.current_page} sur {pagination.last_page} ·{' '}
                                {pagination.total} notification{pagination.total > 1 ? 's' : ''}
                            </p>
                            <div className="flex gap-2">
                                {pagination.links.prev && (
                                    <Link
                                        href={pagination.links.prev}
                                        preserveScroll
                                        className="rounded-md border border-ink-200 px-3 py-1.5 font-medium text-ink-700 hover:bg-ink-50 dark:border-ink-700 dark:text-ink-200 dark:hover:bg-ink-700/40"
                                    >
                                        ← Précédent
                                    </Link>
                                )}
                                {pagination.links.next && (
                                    <Link
                                        href={pagination.links.next}
                                        preserveScroll
                                        className="rounded-md border border-ink-200 px-3 py-1.5 font-medium text-ink-700 hover:bg-ink-50 dark:border-ink-700 dark:text-ink-200 dark:hover:bg-ink-700/40"
                                    >
                                        Suivant →
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </CardBody>
            </Card>
        </DashboardLayout>
    );
}

function FilterChip({ label, active, onClick }: { label: string; active: boolean; onClick: () => void }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            className={cn(
                'rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-wider transition-colors',
                active
                    ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900'
                    : 'border border-ink-200 bg-white text-ink-600 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300 dark:hover:bg-ink-700/40',
            )}
        >
            {label}
        </button>
    );
}

function NotificationRow({
    item,
    onRead,
    onDelete,
}: {
    item: NotificationItem;
    onRead: () => void;
    onDelete: () => void;
}) {
    const unread = !item.read_at;

    return (
        <li
            className={cn(
                'flex flex-col gap-2 px-3 py-3 transition-colors sm:flex-row sm:items-start',
                unread && 'bg-brand-50/30 dark:bg-brand-900/10',
            )}
        >
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <Badge tone={LEVEL_TONE[item.level]} size="sm" dot>
                        {item.level === 'info'
                            ? 'Info'
                            : item.level === 'success'
                                ? 'Succès'
                                : item.level === 'warning'
                                    ? 'Vigilance'
                                    : 'Urgent'}
                    </Badge>
                    <p
                        className={cn(
                            'truncate text-sm',
                            unread
                                ? 'font-semibold text-ink-900 dark:text-white'
                                : 'font-medium text-ink-700 dark:text-ink-200',
                        )}
                    >
                        {item.title}
                    </p>
                    {unread && <span className="size-2 shrink-0 rounded-full bg-brand-500" aria-label="Non lu" />}
                </div>
                {item.message && (
                    <p className="mt-1 text-sm text-ink-600 dark:text-ink-300">{item.message}</p>
                )}
                <RelativeTime
                    value={item.created_at}
                    className="mt-1 block font-mono text-[11px] text-ink-400 dark:text-ink-500"
                />
            </div>

            <div className="flex items-center gap-1.5 sm:flex-col sm:items-end">
                {item.href && (
                    <Link
                        href={item.href}
                        onClick={onRead}
                        className="rounded-md bg-brand-50 px-2.5 py-1 text-[11px] font-semibold text-brand-700 hover:bg-brand-100 dark:bg-brand-900/30 dark:text-brand-300 dark:hover:bg-brand-900/50"
                    >
                        Ouvrir
                    </Link>
                )}
                {unread && (
                    <button
                        type="button"
                        onClick={onRead}
                        className="rounded-md border border-ink-200 px-2.5 py-1 text-[11px] font-medium text-ink-600 hover:bg-ink-50 dark:border-ink-700 dark:text-ink-300 dark:hover:bg-ink-700/40"
                    >
                        Marquer lue
                    </button>
                )}
                <button
                    type="button"
                    onClick={onDelete}
                    aria-label="Supprimer la notification"
                    className="rounded-md p-1 text-ink-400 hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-900/30"
                >
                    <TrashIcon />
                </button>
            </div>
        </li>
    );
}

function BellIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
            />
        </svg>
    );
}

function TrashIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6" />
            <path d="M19 6l-2 14a2 2 0 01-2 2H9a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
        </svg>
    );
}
