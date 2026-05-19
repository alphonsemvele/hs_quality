import { cn } from '@/lib/utils';

export interface AvatarStackItem {
    /** Stable key used for React lists; also exposed as `title`. */
    id: string | number;
    /** Pre-computed initials (up to 2 chars). */
    initials: string;
    /** Optional full label rendered in the native tooltip + a11y label. */
    name?: string;
}

interface AvatarStackProps {
    items: AvatarStackItem[];
    /** Max avatars rendered before showing the "+N" overflow chip. Default 4. */
    max?: number;
    /** Size in `size-*` Tailwind tokens (default `size-7`). */
    size?: 'xs' | 'sm' | 'md';
    /** Optional className passthrough for parent layout. */
    className?: string;
}

const SIZE: Record<NonNullable<AvatarStackProps['size']>, { box: string; text: string; offset: string }> = {
    xs: { box: 'size-5', text: 'text-[9px]', offset: '-ml-1' },
    sm: { box: 'size-7', text: 'text-[10px]', offset: '-ml-1.5' },
    md: { box: 'size-9', text: 'text-xs', offset: '-ml-2' },
};

/**
 * Overlapping circular avatar group with a "+N" overflow chip.
 *
 * Used for assignment rosters (intervenants on a care plan, attendees of
 * a training session, members of a structure). Avatars are derived from
 * initials only — no external image URL needed, no GDPR risk.
 *
 * The component renders `<ul>` with `role="list"` so screen readers can
 * count the participants; each item exposes the full name via
 * `aria-label` + the native browser tooltip.
 */
export function AvatarStack({ items, max = 4, size = 'sm', className }: AvatarStackProps) {
    if (items.length === 0) return null;
    const visible = items.slice(0, max);
    const overflow = items.length - visible.length;
    const s = SIZE[size];

    return (
        <ul role="list" className={cn('inline-flex items-center', className)} aria-label={`${items.length} personne(s)`}>
            {visible.map((item, i) => (
                <li
                    key={item.id}
                    aria-label={item.name ?? item.initials}
                    title={item.name ?? item.initials}
                    className={cn(
                        'inline-flex items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-brand-100 to-brand-200 font-semibold text-brand-700 dark:border-ink-800 dark:from-brand-900/60 dark:to-brand-900/30 dark:text-brand-200',
                        s.box,
                        s.text,
                        i > 0 && s.offset,
                    )}
                >
                    {item.initials}
                </li>
            ))}
            {overflow > 0 && (
                <li
                    aria-label={`${overflow} de plus`}
                    title={`${overflow} de plus`}
                    className={cn(
                        'inline-flex items-center justify-center rounded-full border-2 border-white bg-ink-100 font-semibold text-ink-600 dark:border-ink-800 dark:bg-ink-700 dark:text-ink-200',
                        s.box,
                        s.text,
                        s.offset,
                    )}
                >
                    +{overflow}
                </li>
            )}
        </ul>
    );
}
