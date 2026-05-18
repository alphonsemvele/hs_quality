import { cn } from '@/lib/utils';
import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

type LevelFilter = 'all' | 'info' | 'success' | 'warning' | 'danger';

function dayBucketKey(iso: string | null): 'today' | 'yesterday' | 'older' {
    if (!iso) return 'older';
    const d = new Date(iso);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dayStart = new Date(d);
    dayStart.setHours(0, 0, 0, 0);
    const diff = (today.getTime() - dayStart.getTime()) / (1000 * 60 * 60 * 24);
    if (diff < 1) return 'today';
    if (diff < 2) return 'yesterday';
    return 'older';
}

const DAY_LABEL: Record<'today' | 'yesterday' | 'older', string> = {
    today: "Aujourd'hui",
    yesterday: 'Hier',
    older: 'Plus tôt',
};

export interface NotificationItem {
    id: string;
    type: string;
    title: string;
    message: string | null;
    href: string | null;
    level: 'info' | 'success' | 'warning' | 'danger';
    created_at: string | null;
    read_at: string | null;
}

interface NotificationsPayload {
    unread_count: number;
    items: NotificationItem[];
}

interface PageProps {
    notifications?: NotificationsPayload | null;
    [key: string]: unknown;
}

export default function NotificationsCenter() {
    const { props } = usePage<PageProps>();
    const payload = (props.notifications as NotificationsPayload | undefined) ?? {
        unread_count: 0,
        items: [],
    };
    const [open, setOpen] = useState(false);
    const [filter, setFilter] = useState<LevelFilter>('all');
    const [search, setSearch] = useState('');
    const containerRef = useRef<HTMLDivElement>(null);

    const filteredItems = useMemo(() => {
        const byLevel = filter === 'all' ? payload.items : payload.items.filter((i) => i.level === filter);
        const q = search.trim().toLowerCase();
        if (!q) return byLevel;
        return byLevel.filter((i) =>
            i.title.toLowerCase().includes(q)
            || (i.message?.toLowerCase().includes(q) ?? false),
        );
    }, [filter, payload.items, search]);

    const grouped = useMemo(() => {
        const buckets: Record<'today' | 'yesterday' | 'older', NotificationItem[]> = {
            today: [],
            yesterday: [],
            older: [],
        };
        for (const item of filteredItems) {
            buckets[dayBucketKey(item.created_at)].push(item);
        }
        return buckets;
    }, [filteredItems]);

    useEffect(() => {
        if (!open) {
            setSearch('');
            return;
        }
        const handleClick = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        const handleKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };
        document.addEventListener('mousedown', handleClick);
        document.addEventListener('keydown', handleKey);
        return () => {
            document.removeEventListener('mousedown', handleClick);
            document.removeEventListener('keydown', handleKey);
        };
    }, [open]);

    const markOne = (id: string) => {
        router.post(`/notifications/${id}/read`, undefined, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const markAll = () => {
        router.post('/notifications/read-all', undefined, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const unread = payload.unread_count;

    return (
        <div className="relative" ref={containerRef}>
            <button
                type="button"
                onClick={() => setOpen((o) => !o)}
                aria-label={`Notifications${unread > 0 ? ` (${unread} non lue${unread > 1 ? 's' : ''})` : ''}`}
                aria-haspopup="dialog"
                aria-expanded={open}
                className={cn(
                    'relative flex size-9 cursor-pointer items-center justify-center rounded-lg border border-transparent text-ink-500 transition-colors hover:bg-ink-100 hover:text-ink-700 dark:text-ink-400 dark:hover:bg-ink-700 dark:hover:text-white',
                    open && 'bg-ink-100 text-ink-700 dark:bg-ink-700 dark:text-white',
                )}
            >
                <BellIcon />
                {unread > 0 && (
                    <span className="absolute -right-0.5 -top-0.5 flex min-w-[18px] items-center justify-center rounded-full bg-danger-600 px-1 text-[10px] font-bold leading-tight text-white ring-2 ring-white dark:ring-ink-800">
                        {unread > 99 ? '99+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <div
                    role="dialog"
                    aria-label="Centre de notifications"
                    className="absolute right-0 z-50 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-xl dark:border-ink-700/80 dark:bg-ink-800 dark:shadow-[0_8px_32px_rgba(0,0,0,0.4)]"
                >
                    <div className="flex items-center justify-between border-b border-ink-100 bg-ink-50/60 px-4 py-3 dark:border-ink-700/60 dark:bg-ink-900/50">
                        <div>
                            <p className="text-sm font-semibold text-ink-900 dark:text-white">Notifications</p>
                            <p className="text-[11px] text-ink-500 dark:text-ink-400">
                                {unread > 0
                                    ? `${unread} non lue${unread > 1 ? 's' : ''}`
                                    : 'Tout est à jour'}
                            </p>
                        </div>
                        {unread > 0 && (
                            <button
                                type="button"
                                onClick={markAll}
                                className="rounded-md px-2 py-1 text-[11px] font-medium text-brand-600 hover:bg-brand-50 dark:text-brand-400 dark:hover:bg-brand-900/30"
                            >
                                Tout marquer comme lu
                            </button>
                        )}
                    </div>

                    {payload.items.length > 5 && (
                        <div className="border-b border-ink-100 bg-ink-50/40 px-3 py-2 dark:border-ink-700/60 dark:bg-ink-900/30">
                            <input
                                type="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Filtrer les notifications…"
                                aria-label="Filtrer les notifications par texte"
                                className="h-8 w-full rounded-md border border-ink-200 bg-white px-2.5 text-xs text-ink-900 placeholder:text-ink-400 focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400 dark:border-ink-700 dark:bg-ink-800 dark:text-white dark:placeholder:text-ink-500"
                            />
                        </div>
                    )}

                    {payload.items.length > 0 && (
                        <div className="flex gap-1 overflow-x-auto border-b border-ink-100 bg-ink-50/40 px-3 py-2 dark:border-ink-700/60 dark:bg-ink-900/30">
                            {(['all', 'info', 'success', 'warning', 'danger'] as const).map((value) => (
                                <button
                                    key={value}
                                    type="button"
                                    onClick={() => setFilter(value)}
                                    aria-pressed={filter === value}
                                    className={cn(
                                        'rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider transition-colors',
                                        filter === value
                                            ? 'bg-ink-900 text-white dark:bg-white dark:text-ink-900'
                                            : 'text-ink-500 hover:bg-white hover:text-ink-700 dark:text-ink-400 dark:hover:bg-ink-700 dark:hover:text-ink-200',
                                    )}
                                >
                                    {value === 'all' ? 'Tout' : value === 'info' ? 'Info' : value === 'success' ? 'Succès' : value === 'warning' ? 'Vigilance' : 'Urgent'}
                                </button>
                            ))}
                        </div>
                    )}

                    <div className="max-h-[26rem] overflow-y-auto">
                        {filteredItems.length === 0 ? (
                            <div className="flex flex-col items-center justify-center gap-2 px-4 py-10 text-center">
                                <span className="flex size-10 items-center justify-center rounded-full bg-ink-100 text-ink-400 dark:bg-ink-700 dark:text-ink-500">
                                    <BellIcon />
                                </span>
                                <p className="text-sm font-medium text-ink-700 dark:text-ink-200">
                                    {payload.items.length === 0
                                        ? 'Aucune notification'
                                        : search.trim()
                                            ? 'Aucun résultat pour cette recherche'
                                            : 'Aucune notification dans ce filtre'}
                                </p>
                                <p className="text-xs text-ink-500 dark:text-ink-400">
                                    {payload.items.length === 0
                                        ? 'Les alertes ARS, RPS et expirations apparaîtront ici.'
                                        : search.trim()
                                            ? 'Essayez d\'élargir votre recherche ou de changer de filtre.'
                                            : 'Essayez « Tout ».'}
                                </p>
                            </div>
                        ) : (
                            (['today', 'yesterday', 'older'] as const)
                                .filter((b) => grouped[b].length > 0)
                                .map((bucket) => (
                                    <div key={bucket}>
                                        <p className="sticky top-0 z-10 bg-white/95 px-4 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-ink-500 backdrop-blur dark:bg-ink-800/95 dark:text-ink-400">
                                            {DAY_LABEL[bucket]} · {grouped[bucket].length}
                                        </p>
                                        <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                            {grouped[bucket].map((n) => (
                                                <NotificationRow
                                                    key={n.id}
                                                    item={n}
                                                    onRead={() => markOne(n.id)}
                                                    onClose={() => setOpen(false)}
                                                />
                                            ))}
                                        </ul>
                                    </div>
                                ))
                        )}
                    </div>

                    <div className="border-t border-ink-100 bg-ink-50/40 px-4 py-2.5 text-center dark:border-ink-700/60 dark:bg-ink-900/30">
                        <Link
                            href="/dashboard/profile"
                            onClick={() => setOpen(false)}
                            className="text-[11px] font-medium text-ink-600 hover:text-ink-900 dark:text-ink-400 dark:hover:text-white"
                        >
                            Paramètres de notification
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
}

function NotificationRow({
    item,
    onRead,
    onClose,
}: {
    item: NotificationItem;
    onRead: () => void;
    onClose: () => void;
}) {
    const unread = !item.read_at;
    const Wrapper = item.href ? Link : 'div';
    const wrapperProps = item.href ? { href: item.href, onClick: onClose } : {};

    const handleClick = () => {
        if (unread) onRead();
    };

    return (
        <li>
            <Wrapper
                {...(wrapperProps as { href: string; onClick: () => void })}
                onClick={item.href ? () => { handleClick(); onClose(); } : handleClick}
                className={cn(
                    'flex w-full cursor-pointer items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-ink-50 dark:hover:bg-ink-700/40',
                    unread && 'bg-brand-50/30 dark:bg-brand-900/10',
                )}
            >
                <LevelDot level={item.level} />
                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                        <p
                            className={cn(
                                'text-sm leading-snug',
                                unread ? 'font-semibold text-ink-900 dark:text-white' : 'font-medium text-ink-700 dark:text-ink-200',
                            )}
                        >
                            {item.title}
                        </p>
                        {unread && <span className="mt-1 size-2 shrink-0 rounded-full bg-brand-500" aria-label="Non lu" />}
                    </div>
                    {item.message && (
                        <p className="mt-0.5 line-clamp-2 text-xs text-ink-500 dark:text-ink-400">{item.message}</p>
                    )}
                    <p className="mt-1 text-[11px] font-mono text-ink-400 dark:text-ink-500">{formatTime(item.created_at)}</p>
                </div>
            </Wrapper>
        </li>
    );
}

function LevelDot({ level }: { level: NotificationItem['level'] }) {
    const map: Record<NotificationItem['level'], { bg: string; ring: string; icon: ReactNode }> = {
        info: {
            bg: 'bg-brand-100 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300',
            ring: 'ring-brand-200 dark:ring-brand-800/40',
            icon: <InfoIcon />,
        },
        success: {
            bg: 'bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300',
            ring: 'ring-sage-200 dark:ring-sage-800/40',
            icon: <CheckIcon />,
        },
        warning: {
            bg: 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-300',
            ring: 'ring-warning-200 dark:ring-warning-800/40',
            icon: <WarnIcon />,
        },
        danger: {
            bg: 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-300',
            ring: 'ring-danger-200 dark:ring-danger-800/40',
            icon: <AlertIcon />,
        },
    };
    const c = map[level];
    return (
        <span className={cn('mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg ring-1', c.bg, c.ring)}>
            {c.icon}
        </span>
    );
}

function formatTime(iso: string | null): string {
    if (!iso) return '';
    const d = new Date(iso);
    const now = new Date();
    const diff = (now.getTime() - d.getTime()) / 1000;
    if (diff < 60) return 'à l\'instant';
    if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `il y a ${Math.floor(diff / 3600)} h`;
    if (diff < 604800) return `il y a ${Math.floor(diff / 86400)} j`;
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
}

function BellIcon() {
    return (
        <svg className="size-[18px]" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
    );
}
function InfoIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" />
        </svg>
    );
}
function CheckIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function WarnIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            <line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
    );
}
function AlertIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
    );
}
