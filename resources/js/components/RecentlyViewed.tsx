import { Badge } from '@/components/ui';
import { RECENT_KIND_LABEL, type RecentItem, type RecentKind, getRecent, trackRecent } from '@/lib/recently-viewed';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

const KIND_TONE: Record<RecentKind, 'brand' | 'sage' | 'warning' | 'neutral' | 'danger'> = {
    beneficiary: 'brand',
    intervention: 'sage',
    incident: 'danger',
    audit: 'warning',
    'plan-amelioration': 'warning',
    'care-plan': 'brand',
    formation: 'brand',
    'qvct-campaign': 'sage',
};

/**
 * Side-effect hook called from a show page to record the visit.
 *
 * @example
 *   useTrackRecent({ kind: 'audit', id: audit.id, label: audit.titre, href: `/audits/${audit.id}` });
 */
export function useTrackRecent(item: { kind: RecentKind; id: string; label: string; href: string } | null): void {
    useEffect(() => {
        if (!item) return;
        trackRecent(item);
    }, [item?.kind, item?.id, item?.label, item?.href]);
}

/**
 * Compact horizontal pill row showing the last N items the user has
 * opened. Renders nothing when the history is empty so the dashboard
 * stays clean on a fresh profile.
 */
export function RecentlyViewed({ limit = 5, className }: { limit?: number; className?: string }) {
    const [items, setItems] = useState<RecentItem[]>([]);

    useEffect(() => {
        const sync = () => setItems(getRecent());
        sync();
        window.addEventListener('hsq:recently-viewed-changed', sync);
        window.addEventListener('storage', sync);
        return () => {
            window.removeEventListener('hsq:recently-viewed-changed', sync);
            window.removeEventListener('storage', sync);
        };
    }, []);

    const visible = items.slice(0, limit);
    if (visible.length === 0) return null;

    return (
        <section className={cn('mb-6', className)} aria-label="Récemment consultés">
            <p className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                Récemment consultés
            </p>
            <ul className="flex flex-wrap gap-2">
                {visible.map((item) => (
                    <li key={`${item.kind}:${item.id}`}>
                        <Link
                            href={item.href}
                            className="group inline-flex max-w-xs items-center gap-2 rounded-full border border-ink-200 bg-white px-3 py-1.5 text-xs font-medium text-ink-700 transition-colors hover:border-ink-300 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200 dark:hover:border-ink-600 dark:hover:bg-ink-700"
                        >
                            <Badge tone={KIND_TONE[item.kind]} size="xs">{RECENT_KIND_LABEL[item.kind]}</Badge>
                            <span className="truncate">{item.label}</span>
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
