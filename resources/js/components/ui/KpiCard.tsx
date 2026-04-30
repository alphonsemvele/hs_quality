import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

type Tone = 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';

const TONE: Record<Tone, { iconBg: string; iconText: string; accent: string; bar: string }> = {
    brand: { iconBg: 'bg-brand-50', iconText: 'text-brand-600', accent: 'text-brand-600', bar: 'bg-brand-500' },
    sage: { iconBg: 'bg-sage-50', iconText: 'text-sage-600', accent: 'text-sage-600', bar: 'bg-sage-500' },
    warning: { iconBg: 'bg-warning-50', iconText: 'text-warning-600', accent: 'text-warning-600', bar: 'bg-warning-500' },
    danger: { iconBg: 'bg-danger-50', iconText: 'text-danger-600', accent: 'text-danger-600', bar: 'bg-danger-500' },
    neutral: { iconBg: 'bg-ink-100', iconText: 'text-ink-500', accent: 'text-ink-500', bar: 'bg-ink-400' },
};

export function KpiCard({
    label,
    value,
    sub,
    icon,
    tone = 'neutral',
    progress,
    trend,
}: {
    label: string;
    value: string | number;
    sub?: string;
    icon?: ReactNode;
    tone?: Tone;
    progress?: number;
    trend?: { value: string; positive?: boolean };
}) {
    const t = TONE[tone];
    const displayValue = value === undefined || value === null || String(value).includes('undefined') || String(value).includes('NaN')
        ? '—'
        : value;

    return (
        <div className="group rounded-2xl border border-ink-100 bg-white p-5 transition-all duration-200 hover:border-ink-200 hover:shadow-[0_4px_24px_rgba(15,23,42,0.06)]">
            <div className="flex items-center justify-between">
                {icon && (
                    <div className={cn('flex size-9 items-center justify-center rounded-lg transition-colors', t.iconBg, t.iconText)}>
                        {icon}
                    </div>
                )}
                {trend && (
                    <span
                        className={cn(
                            'inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[11px] font-semibold',
                            trend.positive ? 'bg-sage-50 text-sage-600' : 'bg-danger-50 text-danger-600',
                        )}
                    >
                        {trend.positive ? '↑' : '↓'} {trend.value}
                    </span>
                )}
            </div>
            <p className="mt-3 text-[11px] font-medium uppercase tracking-wider text-ink-400">{label}</p>
            <p className="mt-1 font-mono text-[22px] font-bold tabular tracking-tight text-ink-900">{displayValue}</p>
            {progress !== undefined && !isNaN(progress) && (
                <div className="mt-2.5 h-1 overflow-hidden rounded-full bg-ink-100">
                    <div
                        className={cn('h-full rounded-full transition-all duration-500', t.bar)}
                        style={{ width: `${Math.min(Math.max(progress || 0, 0), 100)}%` }}
                    />
                </div>
            )}
            {sub && <p className="mt-2 text-[11px] text-ink-400">{sub}</p>}
        </div>
    );
}
