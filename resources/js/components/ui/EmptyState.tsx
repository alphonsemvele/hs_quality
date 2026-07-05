import { ReactNode } from 'react';

export function EmptyState({
    icon,
    title,
    description,
    message,
    action,
}: {
    icon?: ReactNode;
    title: string;
    description?: string;
    /** @deprecated Alias for `description`, kept for legacy callers. */
    message?: string;
    action?: ReactNode;
}) {
    const body = description ?? message;
    return (
        <div className="flex flex-col items-center justify-center px-6 py-12 text-center">
            {icon && (
                <div className="mb-3 flex size-11 items-center justify-center rounded-xl bg-ink-50 text-ink-300 dark:bg-ink-700 dark:text-ink-500">{icon}</div>
            )}
            <p className="text-sm font-medium text-ink-600 dark:text-ink-300">{title}</p>
            {body && <p className="mt-1 max-w-xs text-xs text-ink-400 dark:text-ink-500">{body}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
