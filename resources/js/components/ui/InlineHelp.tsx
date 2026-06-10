import { Tooltip } from './Tooltip';

interface InlineHelpProps {
    /** Tooltip body. Plain text for screen readers (no HTML). */
    children: string;
    /** Accessible label for the trigger. Defaults to "Plus d'informations". */
    label?: string;
    /** Tooltip alignment relative to the trigger. */
    align?: 'left' | 'right' | 'center';
}

/**
 * Tiny "?" icon next to a form label or table column header that reveals
 * a short explanation on hover / focus.
 *
 * Use it sparingly for terms where the audience may need a refresher
 * (GIR scale, RPS dimensions, ARS notification window, "Auditeur", etc.).
 * Don't use it for things that should be obvious from the label.
 *
 * @example
 *   <Label>GIR <InlineHelp>Niveau de dépendance (1 = très dépendant, 6 = autonome)</InlineHelp></Label>
 */
export function InlineHelp({ children, label = "Plus d'informations", align = 'center' }: InlineHelpProps) {
    return (
        <Tooltip content={children} align={align} inline>
            <span
                role="img"
                aria-label={label}
                tabIndex={0}
                className="ml-1 inline-flex size-4 cursor-help items-center justify-center rounded-full border border-ink-300 text-[10px] font-semibold text-ink-500 transition-colors hover:border-brand-400 hover:text-brand-600 focus-visible:border-brand-500 focus-visible:text-brand-600 focus-visible:outline-none dark:border-ink-600 dark:text-ink-400 dark:hover:border-brand-400 dark:hover:text-brand-300"
            >
                ?
            </span>
        </Tooltip>
    );
}
