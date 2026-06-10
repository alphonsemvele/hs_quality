import { Link } from '@inertiajs/react';
import { ReactNode } from 'react';

export function PageHeader({
    title,
    subtitle,
    breadcrumb,
    actions,
}: {
    title: string;
    subtitle?: ReactNode;
    breadcrumb?: { label: string; href?: string }[];
    actions?: ReactNode;
}) {
    return (
        <div className="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div className="min-w-0">
                {breadcrumb && breadcrumb.length > 0 && (
                    <nav className="mb-2 flex items-center gap-1.5 text-xs text-ink-500 dark:text-ink-400" aria-label="Fil d'ariane">
                        {breadcrumb.map((crumb, i) => (
                            <span key={i} className="flex items-center gap-1.5">
                                {i > 0 && (
                                    <svg className="size-3 text-ink-300 dark:text-ink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                )}
                                {crumb.href ? (
                                    <Link href={crumb.href} className="hover:text-brand-600 dark:hover:text-brand-400">
                                        {crumb.label}
                                    </Link>
                                ) : (
                                    <span className="text-ink-700 dark:text-ink-200">{crumb.label}</span>
                                )}
                            </span>
                        ))}
                    </nav>
                )}
                <h1 className="text-xl font-bold tracking-tight text-ink-900 sm:text-2xl dark:text-white">{title}</h1>
                {subtitle && <p className="mt-1 text-sm text-ink-500 dark:text-ink-400">{subtitle}</p>}
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}
