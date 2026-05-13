import { lookupTerm } from '@/lib/glossary';
import { ReactNode } from 'react';
import { Tooltip } from './Tooltip';

interface GProps {
    /** The term to look up (case-insensitive). */
    term: string;
    /** Optional override of the displayed text (defaults to `term`). */
    children?: ReactNode;
}

/**
 * Inline glossary lookup. Wraps the term in a tooltip pulling its definition
 * from `resources/js/lib/glossary.ts`. Unknown terms render as plain text so
 * the UI never breaks if a dictionary entry is removed.
 *
 * Usage:
 *   <G term="GIR">GIR</G>
 *   <G term="QVCT">démarche QVCT</G>
 */
export function G({ term, children }: GProps) {
    const entry = lookupTerm(term);

    if (!entry) {
        return <>{children ?? term}</>;
    }

    return (
        <Tooltip
            content={
                <>
                    <span className="block font-semibold text-ink-900 dark:text-white">
                        {entry.term}
                        {entry.full && <span className="ml-1 font-normal text-ink-500 dark:text-ink-400">— {entry.full}</span>}
                    </span>
                    <span className="mt-1 block">{entry.definition}</span>
                </>
            }
            indicator="underline"
            widthClass="w-72"
        >
            {children ?? term}
        </Tooltip>
    );
}
