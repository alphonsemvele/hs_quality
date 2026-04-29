import { ReactNode } from 'react';

export function EmptyState({
    icon,
    title,
    description,
    action,
}: {
    icon?: ReactNode;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center px-6 py-16 text-center">
            {icon && (
                <div className="mb-4 flex size-12 items-center justify-center rounded-2xl bg-ink-100 text-ink-400">{icon}</div>
            )}
            <p className="text-sm font-semibold text-ink-900">{title}</p>
            {description && <p className="mt-1 max-w-sm text-sm text-ink-500">{description}</p>}
            {action && <div className="mt-5">{action}</div>}
        </div>
    );
}
