import { cn } from '@/lib/utils';
import { HTMLAttributes, ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react';

export function Table({ className, children, ...rest }: HTMLAttributes<HTMLTableElement>) {
    return (
        <div className="overflow-x-auto">
            <table className={cn('w-full border-collapse text-sm', className)} {...rest}>
                {children}
            </table>
        </div>
    );
}

export function THead({ className, children, ...rest }: HTMLAttributes<HTMLTableSectionElement>) {
    return (
        <thead className={cn('border-b border-ink-100 bg-ink-50/60 dark:border-ink-700/60 dark:bg-ink-800/60', className)} {...rest}>
            {children}
        </thead>
    );
}

export function TBody({ className, children, ...rest }: HTMLAttributes<HTMLTableSectionElement>) {
    return (
        <tbody className={cn('divide-y divide-ink-100 dark:divide-ink-700/60', className)} {...rest}>
            {children}
        </tbody>
    );
}

export function Tr({ className, children, ...rest }: HTMLAttributes<HTMLTableRowElement>) {
    return (
        <tr className={cn('transition-colors hover:bg-ink-50/60 dark:hover:bg-ink-700/30', className)} {...rest}>
            {children}
        </tr>
    );
}

interface ThProps extends ThHTMLAttributes<HTMLTableCellElement> {
    /**
     * Optional explanatory hint shown in a tooltip on hover/focus.
     * When provided, a small (?) icon is rendered next to the header label.
     */
    hint?: ReactNode;
}

export function Th({ className, children, hint, ...rest }: ThProps) {
    return (
        <th
            className={cn(
                'px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400',
                className,
            )}
            {...rest}
        >
            {hint ? (
                <span className="group/th relative inline-flex items-center gap-1.5">
                    <span>{children}</span>
                    <button
                        type="button"
                        tabIndex={0}
                        aria-label="Explication"
                        className="peer/hint inline-flex size-4 cursor-help items-center justify-center rounded-full border border-ink-300 text-[10px] font-bold text-ink-400 transition-colors hover:border-brand-500 hover:bg-brand-50 hover:text-brand-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:border-ink-600 dark:text-ink-500 dark:hover:border-brand-400 dark:hover:bg-brand-900/30 dark:hover:text-brand-300"
                    >
                        ?
                    </button>
                    <span
                        role="tooltip"
                        className="pointer-events-none invisible absolute left-0 top-full z-50 mt-2 w-64 rounded-lg border border-ink-200 bg-white p-3 text-left text-xs font-normal normal-case tracking-normal text-ink-700 opacity-0 shadow-xl transition-all duration-150 group-hover/th:visible group-hover/th:opacity-100 peer-focus-visible/hint:visible peer-focus-visible/hint:opacity-100 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                    >
                        {hint}
                    </span>
                </span>
            ) : (
                children
            )}
        </th>
    );
}

export function Td({ className, children, ...rest }: TdHTMLAttributes<HTMLTableCellElement>) {
    return (
        <td className={cn('px-4 py-3 align-middle text-sm text-ink-700 dark:text-ink-300', className)} {...rest}>
            {children}
        </td>
    );
}
