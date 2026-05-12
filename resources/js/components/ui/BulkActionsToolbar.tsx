import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface BulkActionsToolbarProps {
    selectedCount: number;
    totalCount: number;
    onSelectAll: () => void;
    onClear: () => void;
    actions: ReactNode;
    /** Optional message shown in subtle text on the right side */
    hint?: string;
}

export function BulkActionsToolbar({
    selectedCount,
    totalCount,
    onSelectAll,
    onClear,
    actions,
    hint,
}: BulkActionsToolbarProps) {
    if (selectedCount === 0) return null;

    return (
        <div
            role="region"
            aria-label={`${selectedCount} éléments sélectionnés`}
            className={cn(
                'sticky top-16 z-30 mb-3 flex flex-wrap items-center gap-3 rounded-2xl border border-brand-200 bg-brand-50/95 px-4 py-2.5 shadow-md backdrop-blur',
                'dark:border-brand-700/50 dark:bg-brand-900/40',
            )}
        >
            <div className="flex items-center gap-2">
                <span className="flex size-7 shrink-0 items-center justify-center rounded-lg bg-brand-600 font-mono text-xs font-bold text-white">
                    {selectedCount}
                </span>
                <span className="text-sm font-medium text-brand-900 dark:text-brand-100">
                    {selectedCount === 1 ? 'élément sélectionné' : 'éléments sélectionnés'}
                    {totalCount > 0 && selectedCount < totalCount && (
                        <button
                            type="button"
                            onClick={onSelectAll}
                            className="ml-2 text-xs font-semibold text-brand-700 underline-offset-2 hover:underline dark:text-brand-300"
                        >
                            Sélectionner les {totalCount}
                        </button>
                    )}
                </span>
            </div>

            <span className="hidden flex-1 sm:block" aria-hidden />

            <div className="flex flex-wrap items-center gap-2">
                {actions}
                <button
                    type="button"
                    onClick={onClear}
                    aria-label="Effacer la sélection"
                    className="ml-1 rounded-md p-1 text-brand-600 transition-colors hover:bg-brand-100 dark:text-brand-300 dark:hover:bg-brand-900/50"
                >
                    <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <line x1="18" y1="6" x2="6" y2="18" strokeLinecap="round" />
                        <line x1="6" y1="6" x2="18" y2="18" strokeLinecap="round" />
                    </svg>
                </button>
            </div>

            {hint && (
                <p className="basis-full pt-1 text-[11px] text-brand-800/80 dark:text-brand-200/80">{hint}</p>
            )}
        </div>
    );
}

/**
 * Reusable checkbox cell for table row selection.
 */
export function BulkSelectCheckbox({
    checked,
    indeterminate,
    onChange,
    ariaLabel,
}: {
    checked: boolean;
    indeterminate?: boolean;
    onChange: (next: boolean) => void;
    ariaLabel: string;
}) {
    return (
        <input
            ref={(el) => {
                if (el) el.indeterminate = indeterminate ?? false;
            }}
            type="checkbox"
            checked={checked}
            onChange={(e) => onChange(e.target.checked)}
            aria-label={ariaLabel}
            className="size-4 cursor-pointer rounded border-ink-300 text-brand-600 focus:ring-brand-400 focus:ring-offset-0 dark:border-ink-600 dark:bg-ink-800"
        />
    );
}
