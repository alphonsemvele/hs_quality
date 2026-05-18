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
