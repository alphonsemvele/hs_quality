import { cn } from '@/lib/utils';
import { ReactNode } from 'react';
import { Tooltip } from './Tooltip';

type Tone = 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';

const TONE: Record<Tone, { iconBg: string; iconText: string; accent: string; bar: string; darkIconBg: string; darkIconText: string; darkBar: string }> = {
    brand: { iconBg: 'bg-brand-50', iconText: 'text-brand-600', accent: 'text-brand-600', bar: 'bg-brand-500', darkIconBg: 'dark:bg-brand-900/40', darkIconText: 'dark:text-brand-300', darkBar: 'dark:bg-brand-400' },
    sage: { iconBg: 'bg-sage-50', iconText: 'text-sage-600', accent: 'text-sage-600', bar: 'bg-sage-500', darkIconBg: 'dark:bg-sage-900/40', darkIconText: 'dark:text-sage-300', darkBar: 'dark:bg-sage-400' },
    warning: { iconBg: 'bg-warning-50', iconText: 'text-warning-600', accent: 'text-warning-600', bar: 'bg-warning-500', darkIconBg: 'dark:bg-warning-900/40', darkIconText: 'dark:text-warning-300', darkBar: 'dark:bg-warning-400' },
    danger: { iconBg: 'bg-danger-50', iconText: 'text-danger-600', accent: 'text-danger-600', bar: 'bg-danger-500', darkIconBg: 'dark:bg-danger-900/40', darkIconText: 'dark:text-danger-300', darkBar: 'dark:bg-danger-400' },
    neutral: { iconBg: 'bg-ink-100', iconText: 'text-ink-500', accent: 'text-ink-500', bar: 'bg-ink-400', darkIconBg: 'dark:bg-ink-700', darkIconText: 'dark:text-ink-300', darkBar: 'dark:bg-ink-500' },
};

export function KpiCard({
    label,
    value,
    sub,
    icon,
    tone = 'neutral',
    progress,
    trend,
    hint,
}: {
    label: string;
    value: string | number;
    sub?: string;
    icon?: ReactNode;
    tone?: Tone;
    progress?: number;
    trend?: { value: string; positive?: boolean };
    /** Pedagogical explanation of the metric — rendered as a `?` icon next to the label. */
    hint?: ReactNode;
}) {
    const t = TONE[tone];
    const displayValue = value === undefined || value === null || String(value).includes('undefined') || String(value).includes('NaN')
        ? '—'
        : value;

    return (
        <div className="group rounded-2xl border border-ink-100 bg-white p-5 transition-all duration-200 hover:border-ink-200 hover:shadow-[0_4px_24px_rgba(15,23,42,0.06)] dark:border-ink-700/60 dark:bg-ink-800 dark:hover:border-ink-600 dark:hover:shadow-[0_4px_24px_rgba(0,0,0,0.2)]">
            <div className="flex items-center justify-between">
                {icon && (
                    <div className={cn('flex size-10 items-center justify-center rounded-xl transition-colors', t.iconBg, t.iconText, t.darkIconBg, t.darkIconText)}>
                        {icon}
                    </div>
                )}
                {trend && (
                    <span
                        className={cn(
                            'inline-flex items-center gap-0.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                            trend.positive
                                ? 'bg-sage-50 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300'
                                : 'bg-danger-50 text-danger-700 dark:bg-danger-900/40 dark:text-danger-300',
                        )}
                    >
                        <svg className={cn('size-3', trend.positive ? '' : 'rotate-180')} viewBox="0 0 12 12" fill="none" stroke="currentColor" strokeWidth={2}>
                            <path d="M6 9V3M3 5l3-3 3 3" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                        {trend.value}
                    </span>
                )}
            </div>
            <p className="mt-3 text-xs font-medium text-ink-500 dark:text-ink-400">
                {hint ? (
                    <Tooltip content={hint} indicator="question" triggerLabel={`Explication : ${label}`}>
                        {label}
                    </Tooltip>
                ) : (
                    label
                )}
            </p>
            <p className="mt-1 font-mono text-2xl font-bold tabular tracking-tight text-ink-900 dark:text-white">{displayValue}</p>
            {progress !== undefined && !isNaN(progress) && (
                <div className="mt-3 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                    <div
                        className={cn('h-full rounded-full transition-all duration-700 ease-out', t.bar, t.darkBar)}
                        style={{ width: `${Math.min(Math.max(progress || 0, 0), 100)}%` }}
                    />
                </div>
            )}
            {sub && <p className="mt-2 text-xs text-ink-500 dark:text-ink-400">{sub}</p>}
        </div>
    );
}
