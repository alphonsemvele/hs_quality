import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';

export interface TocItem {
    /** Anchor id of the target section (must match an existing element's id). */
    id: string;
    /** Visible label in the TOC. */
    label: string;
}

interface PageTocProps {
    items: TocItem[];
    className?: string;
}

/**
 * Sticky table-of-contents side nav.
 *
 * Use case: long show pages (care plan, audit run, beneficiary dossier)
 * where the user wants to jump straight to a section. The active item
 * is detected via IntersectionObserver — whichever section is closest
 * to the top of the viewport gets the highlight.
 *
 * Render this inside a 2-column grid alongside the main content; on
 * narrow viewports the TOC collapses to a horizontal chip strip.
 */
export function PageToc({ items, className }: PageTocProps) {
    const [activeId, setActiveId] = useState<string | null>(items[0]?.id ?? null);

    useEffect(() => {
        if (typeof window === 'undefined' || items.length === 0) return;
        const elements = items
            .map((item) => document.getElementById(item.id))
            .filter((el): el is HTMLElement => el !== null);
        if (elements.length === 0) return;

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((e) => e.isIntersecting)
                    .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
                if (visible.length > 0) {
                    setActiveId(visible[0].target.id);
                }
            },
            { rootMargin: '-20% 0px -60% 0px', threshold: 0 },
        );

        for (const el of elements) observer.observe(el);
        return () => observer.disconnect();
    }, [items]);

    if (items.length === 0) return null;

    const onJump = (id: string) => (event: React.MouseEvent<HTMLAnchorElement>) => {
        event.preventDefault();
        const target = document.getElementById(id);
        if (!target) return;
        const top = target.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top, behavior: 'smooth' });
        setActiveId(id);
    };

    return (
        <>
            {/* Desktop: vertical sticky nav */}
            <nav
                aria-label="Navigation interne"
                className={cn(
                    'hidden lg:sticky lg:top-24 lg:block',
                    className,
                )}
            >
                <p className="mb-3 text-[10px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                    Sur cette page
                </p>
                <ul className="space-y-1 border-l border-ink-100 dark:border-ink-700/60">
                    {items.map((item) => {
                        const active = activeId === item.id;
                        return (
                            <li key={item.id}>
                                <a
                                    href={`#${item.id}`}
                                    onClick={onJump(item.id)}
                                    className={cn(
                                        '-ml-px block border-l-2 py-1.5 pl-3 text-xs transition-colors',
                                        active
                                            ? 'border-brand-500 font-semibold text-ink-900 dark:border-brand-400 dark:text-white'
                                            : 'border-transparent text-ink-500 hover:border-ink-300 hover:text-ink-800 dark:text-ink-400 dark:hover:border-ink-600 dark:hover:text-ink-100',
                                    )}
                                >
                                    {item.label}
                                </a>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            {/* Mobile: horizontal chip strip */}
            <nav
                aria-label="Navigation interne"
                className="-mx-4 mb-4 flex gap-1.5 overflow-x-auto px-4 lg:hidden"
            >
                {items.map((item) => {
                    const active = activeId === item.id;
                    return (
                        <a
                            key={item.id}
                            href={`#${item.id}`}
                            onClick={onJump(item.id)}
                            className={cn(
                                'shrink-0 rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                active
                                    ? 'border-brand-300 bg-brand-50 text-brand-700 dark:border-brand-600/50 dark:bg-brand-900/30 dark:text-brand-300'
                                    : 'border-ink-200 bg-white text-ink-600 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300',
                            )}
                        >
                            {item.label}
                        </a>
                    );
                })}
            </nav>
        </>
    );
}
