import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface Suggestion {
    icon?: ReactNode;
    title: string;
    description: string;
    cta?: { label: string; onClick?: () => void; href?: string };
    tone?: 'brand' | 'sage' | 'warning' | 'neutral';
}

interface EmptyStateRichProps {
    icon?: ReactNode;
    title: string;
    description?: string;
    primaryAction?: ReactNode;
    suggestions?: Suggestion[];
    /** Optional short pedagogical link below the suggestions (e.g. doc URL). */
    helpHref?: string;
    helpLabel?: string;
}

const TONE_STYLES = {
    brand: 'border-brand-200 hover:border-brand-400 dark:border-brand-700/40 dark:hover:border-brand-500',
    sage: 'border-sage-200 hover:border-sage-400 dark:border-sage-700/40 dark:hover:border-sage-500',
    warning: 'border-warning-200 hover:border-warning-400 dark:border-warning-700/40 dark:hover:border-warning-500',
    neutral: 'border-ink-200 hover:border-ink-300 dark:border-ink-700 dark:hover:border-ink-600',
} as const;

export function EmptyStateRich({
    icon,
    title,
    description,
    primaryAction,
    suggestions = [],
    helpHref,
    helpLabel = "Consulter l'aide",
}: EmptyStateRichProps) {
    return (
        <div className="px-6 py-10 text-center sm:py-14">
            {icon && (
                <div className="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-brand-50 text-brand-700 dark:from-brand-900/40 dark:to-brand-900/20 dark:text-brand-300">
                    {icon}
                </div>
            )}
            <h3 className="text-base font-semibold text-ink-900 dark:text-white">{title}</h3>
            {description && (
                <p className="mx-auto mt-2 max-w-md text-sm text-ink-500 dark:text-ink-400">{description}</p>
            )}
            {primaryAction && <div className="mt-5 flex justify-center">{primaryAction}</div>}

            {suggestions.length > 0 && (
                <div className="mx-auto mt-8 max-w-3xl">
                    <p className="mb-3 text-[11px] font-semibold uppercase tracking-wider text-ink-400 dark:text-ink-500">
                        Démarrer rapidement
                    </p>
                    <ul className="grid grid-cols-1 gap-3 text-left sm:grid-cols-2 lg:grid-cols-3">
                        {suggestions.map((s, i) => {
                            const tone = s.tone ?? 'brand';
                            const inner = (
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        {s.icon && <span aria-hidden className="text-base">{s.icon}</span>}
                                        <p className="text-sm font-semibold text-ink-900 dark:text-white">{s.title}</p>
                                    </div>
                                    <p className="text-[11px] leading-relaxed text-ink-500 dark:text-ink-400">{s.description}</p>
                                    {s.cta && (
                                        <span className="inline-flex items-center gap-1 pt-1 text-[11px] font-medium text-brand-600 dark:text-brand-400">
                                            {s.cta.label}
                                            <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                                <path d="M5 12h14m-7-7l7 7-7 7" strokeLinecap="round" strokeLinejoin="round" />
                                            </svg>
                                        </span>
                                    )}
                                </div>
                            );
                            const baseCls = cn(
                                'block rounded-xl border-2 bg-white p-3.5 text-left transition-all hover:shadow-md dark:bg-ink-800',
                                TONE_STYLES[tone],
                            );
                            if (s.cta?.href) {
                                return (
                                    <li key={i}>
                                        <a href={s.cta.href} className={baseCls}>
                                            {inner}
                                        </a>
                                    </li>
                                );
                            }
                            if (s.cta?.onClick) {
                                return (
                                    <li key={i}>
                                        <button type="button" onClick={s.cta.onClick} className={cn(baseCls, 'w-full cursor-pointer')}>
                                            {inner}
                                        </button>
                                    </li>
                                );
                            }
                            return (
                                <li key={i} className={cn(baseCls, 'cursor-default opacity-80')}>
                                    {inner}
                                </li>
                            );
                        })}
                    </ul>
                </div>
            )}

            {helpHref && (
                <a
                    href={helpHref}
                    className="mt-6 inline-flex items-center gap-1 text-[11px] font-medium text-ink-500 hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400"
                >
                    {helpLabel}
                    <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path d="M5 12h14m-7-7l7 7-7 7" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                </a>
            )}
        </div>
    );
}
