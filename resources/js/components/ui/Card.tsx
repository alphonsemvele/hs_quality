import { cn } from '@/lib/utils';
import { HTMLAttributes, ReactNode } from 'react';

export function Card({ className, children, ...rest }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div
            className={cn(
                'rounded-2xl border border-ink-100 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.04)] transition-shadow hover:shadow-[0_2px_8px_rgba(15,23,42,0.06)]',
                className,
            )}
            {...rest}
        >
            {children}
        </div>
    );
}

export function CardHeader({
    title,
    subtitle,
    action,
    className,
}: {
    title: string;
    subtitle?: string;
    action?: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('flex items-start justify-between gap-4 border-b border-ink-100 px-5 py-4', className)}>
            <div className="min-w-0">
                <h3 className="text-sm font-semibold text-ink-900">{title}</h3>
                {subtitle && <p className="mt-0.5 text-xs text-ink-500">{subtitle}</p>}
            </div>
            {action && <div className="shrink-0">{action}</div>}
        </div>
    );
}

export function CardBody({ className, children, ...rest }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div className={cn('p-5', className)} {...rest}>
            {children}
        </div>
    );
}

export function CardFooter({ className, children, ...rest }: HTMLAttributes<HTMLDivElement>) {
    return (
        <div className={cn('flex items-center justify-end gap-3 border-t border-ink-100 bg-ink-50/60 px-5 py-3', className)} {...rest}>
            {children}
        </div>
    );
}
