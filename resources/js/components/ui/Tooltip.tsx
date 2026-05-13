import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface TooltipProps {
    /** The element that triggers the tooltip on hover/focus. */
    children: ReactNode;
    /** The tooltip content. If empty, the trigger is rendered without any decoration. */
    content?: ReactNode;
    /** Optional ARIA label for screen-reader announcement of the trigger. */
    triggerLabel?: string;
    /** Constrain tooltip width (default `w-64`). */
    widthClass?: string;
    /** Tooltip alignment relative to the trigger. */
    align?: 'left' | 'right' | 'center';
    /** Show a question-mark affordance after children (useful for headers / labels). */
    indicator?: 'none' | 'question' | 'underline';
    /** Override the wrapper's display behaviour. */
    inline?: boolean;
}

/**
 * Lightweight CSS-only tooltip — no JS positioning library, no portal.
 * Visible on hover OR keyboard focus, dismissible by moving away.
 *
 * Use this for short pedagogical hints (≤ 2 sentences). For long-form help,
 * use a `HelpDrawer` or a dedicated `/docs` page instead.
 */
export function Tooltip({
    children,
    content,
    triggerLabel,
    widthClass = 'w-64',
    align = 'left',
    indicator = 'none',
    inline = true,
}: TooltipProps) {
    if (!content) {
        return <>{children}</>;
    }

    const alignClass =
        align === 'right'
            ? 'right-0'
            : align === 'center'
              ? 'left-1/2 -translate-x-1/2'
              : 'left-0';

    return (
        <span
            className={cn(
                'group/tooltip relative gap-1.5',
                inline ? 'inline-flex items-center' : 'flex items-center',
            )}
        >
            {indicator === 'underline' ? (
                <span className="decoration-dotted decoration-ink-400 underline-offset-2 group-hover/tooltip:underline group-focus-within/tooltip:underline dark:decoration-ink-500">
                    {children}
                </span>
            ) : (
                <span>{children}</span>
            )}
            {indicator === 'question' && (
                <button
                    type="button"
                    tabIndex={0}
                    aria-label={triggerLabel ?? 'Explication'}
                    className="peer/hint inline-flex size-4 cursor-help items-center justify-center rounded-full border border-ink-300 text-[10px] font-bold text-ink-400 transition-colors hover:border-brand-500 hover:bg-brand-50 hover:text-brand-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:border-ink-600 dark:text-ink-500 dark:hover:border-brand-400 dark:hover:bg-brand-900/30 dark:hover:text-brand-300"
                >
                    ?
                </button>
            )}
            <span
                role="tooltip"
                className={cn(
                    'pointer-events-none invisible absolute top-full z-50 mt-2 rounded-lg border border-ink-200 bg-white p-3 text-left text-xs font-normal normal-case leading-relaxed tracking-normal text-ink-700 opacity-0 shadow-xl transition-all duration-150 group-hover/tooltip:visible group-hover/tooltip:opacity-100 group-focus-within/tooltip:visible group-focus-within/tooltip:opacity-100 peer-focus-visible/hint:visible peer-focus-visible/hint:opacity-100 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200',
                    alignClass,
                    widthClass,
                )}
            >
                {content}
            </span>
        </span>
    );
}
