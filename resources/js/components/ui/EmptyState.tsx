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
        <div className="flex flex-col items-center justify-center px-6 py-12 text-center">
            {icon && (
                <div className="mb-3 flex size-11 items-center justify-center rounded-xl bg-ink-50 text-ink-300 dark:bg-ink-700 dark:text-ink-500">{icon}</div>
            )}
            <p className="text-sm font-medium text-ink-600 dark:text-ink-300">{title}</p>
            {description && <p className="mt-1 max-w-xs text-xs text-ink-400 dark:text-ink-500">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
