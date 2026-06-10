import { cn } from '@/lib/utils';
import type { HTMLAttributes, ReactNode } from 'react';

type Shape = 'block' | 'text' | 'circle';

interface SkeletonProps extends HTMLAttributes<HTMLDivElement> {
    /** Shape preset. `text` is a thin row sized for typography. */
    shape?: Shape;
    /** Width override (Tailwind class or arbitrary value). */
    w?: string;
    /** Height override (Tailwind class or arbitrary value). */
    h?: string;
    /** Honour reduced-motion preferences — the bar fades instead of pulsing. */
    children?: ReactNode;
}

/**
 * Loading skeleton primitive.
 *
 * Use cases:
 *   - <Skeleton shape="text" w="w-40" />          — placeholder name
 *   - <Skeleton shape="block" h="h-32" />          — placeholder card
 *   - <Skeleton shape="circle" w="size-10" />     — placeholder avatar
 *
 * Renders a single div with a gentle pulse animation. Honors
 * prefers-reduced-motion automatically via the global rule in
 * resources/css/app.css.
 */
export function Skeleton({ shape = 'block', w, h, className, children, ...rest }: SkeletonProps) {
    const base = 'animate-pulse bg-ink-200/70 dark:bg-ink-700/60';

    const shapeClass =
        shape === 'circle'
            ? 'rounded-full'
            : shape === 'text'
              ? 'rounded-md h-4'
              : 'rounded-xl';

    return (
        <div
            aria-hidden="true"
            className={cn(base, shapeClass, w, h, className)}
            {...rest}
        >
            {children}
        </div>
    );
}

/**
 * Convenience: a vertical stack of N text skeletons for placeholder lists.
 */
export function SkeletonLines({ count = 3, className }: { count?: number; className?: string }) {
    return (
        <div className={cn('space-y-2', className)} aria-hidden="true">
            {Array.from({ length: count }, (_, i) => (
                <Skeleton key={i} shape="text" w={i % 2 === 0 ? 'w-full' : 'w-3/4'} />
            ))}
        </div>
    );
}

/**
 * Placeholder shaped like a `KpiCard` (icon block, label, value, optional
 * progress bar). Pair with Inertia deferred props so the dashboard's KPI
 * row keeps its dimensions while the real numbers are loading.
 */
export function KpiSkeleton({ count = 1, showProgress = false }: { count?: number; showProgress?: boolean }) {
    if (count === 1) {
        return <KpiSkeletonOne showProgress={showProgress} />;
    }
    return (
        <>
            {Array.from({ length: count }, (_, i) => (
                <KpiSkeletonOne key={i} showProgress={showProgress} />
            ))}
        </>
    );
}

function KpiSkeletonOne({ showProgress }: { showProgress: boolean }) {
    return (
        <div
            data-component="kpi"
            aria-hidden="true"
            className="rounded-2xl border border-ink-100 bg-white p-5 dark:border-ink-700/60 dark:bg-ink-800"
        >
            <Skeleton w="size-10" h="" className="rounded-xl" />
            <Skeleton shape="text" w="w-24" className="mt-3" />
            <Skeleton w="w-16" h="h-7" className="mt-1 rounded-md" />
            {showProgress && (
                <Skeleton w="w-full" h="h-1.5" className="mt-3 rounded-full" />
            )}
        </div>
    );
}

interface TableSkeletonProps {
    /** Number of skeleton rows. Default 5. */
    rows?: number;
    /** Number of columns. Default 4. */
    cols?: number;
    /** Optional header labels. If provided, length must equal `cols`. */
    headers?: string[];
    className?: string;
}

/**
 * Compose-friendly table placeholder used in deferred-prop empty states.
 * Pairs with Inertia's `<Deferred>` / `<WhenVisible>` to keep the row
 * height stable while data is loading, preventing layout shift.
 */
export function TableSkeleton({ rows = 5, cols = 4, headers, className }: TableSkeletonProps) {
    return (
        <div className={cn('overflow-hidden rounded-xl border border-ink-100 dark:border-ink-700/60', className)} aria-hidden="true">
            <table className="w-full table-fixed">
                <thead className="bg-ink-50/40 dark:bg-ink-900/30">
                    <tr>
                        {Array.from({ length: cols }, (_, i) => (
                            <th key={i} className="px-4 py-2.5 text-left">
                                {headers && headers[i] ? (
                                    <span className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                        {headers[i]}
                                    </span>
                                ) : (
                                    <Skeleton shape="text" w="w-24" />
                                )}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y divide-ink-100 dark:divide-ink-700/60">
                    {Array.from({ length: rows }, (_, r) => (
                        <tr key={r}>
                            {Array.from({ length: cols }, (_, c) => (
                                <td key={c} className="px-4 py-3.5">
                                    <Skeleton
                                        shape="text"
                                        w={c === 0 ? 'w-36' : c === cols - 1 ? 'w-16' : 'w-24'}
                                    />
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
