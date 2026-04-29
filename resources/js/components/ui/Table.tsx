import { cn } from '@/lib/utils';
import { HTMLAttributes, TdHTMLAttributes, ThHTMLAttributes } from 'react';

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
        <thead className={cn('border-b border-ink-100 bg-ink-50/60', className)} {...rest}>
            {children}
        </thead>
    );
}

export function TBody({ className, children, ...rest }: HTMLAttributes<HTMLTableSectionElement>) {
    return (
        <tbody className={cn('divide-y divide-ink-100', className)} {...rest}>
            {children}
        </tbody>
    );
}

export function Tr({ className, children, ...rest }: HTMLAttributes<HTMLTableRowElement>) {
    return (
        <tr className={cn('transition-colors hover:bg-ink-50/40', className)} {...rest}>
            {children}
        </tr>
    );
}

export function Th({ className, children, ...rest }: ThHTMLAttributes<HTMLTableCellElement>) {
    return (
        <th
            className={cn(
                'px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-ink-500',
                className,
            )}
            {...rest}
        >
            {children}
        </th>
    );
}

export function Td({ className, children, ...rest }: TdHTMLAttributes<HTMLTableCellElement>) {
    return (
        <td className={cn('px-4 py-3 align-middle text-sm text-ink-700', className)} {...rest}>
            {children}
        </td>
    );
}
