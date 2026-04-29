import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

type Tone = 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';

const TONE: Record<Tone, { iconBg: string; iconText: string; valueText: string }> = {
    brand: { iconBg: 'bg-brand-50', iconText: 'text-brand-600', valueText: 'text-ink-900' },
    sage: { iconBg: 'bg-sage-50', iconText: 'text-sage-600', valueText: 'text-ink-900' },
    warning: { iconBg: 'bg-warning-50', iconText: 'text-warning-600', valueText: 'text-ink-900' },
    danger: { iconBg: 'bg-danger-50', iconText: 'text-danger-600', valueText: 'text-ink-900' },
    neutral: { iconBg: 'bg-ink-100', iconText: 'text-ink-500', valueText: 'text-ink-900' },
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
    return (
        <div className="rounded-2xl border border-ink-100 bg-white p-5 transition-shadow hover:shadow-[0_4px_20px_rgba(15,23,42,0.06)]">
            <div className="mb-3 flex items-start justify-between">
                {icon && (
                    <div className={cn('flex size-10 items-center justify-center rounded-xl', t.iconBg, t.iconText)}>
                        {icon}
                    </div>
                )}
                {trend && (
                    <span
                        className={cn(
                            'inline-flex items-center gap-0.5 text-xs font-semibold',
                            trend.positive ? 'text-sage-600' : 'text-danger-600',
                        )}
                    >
                        {trend.value}
                    </span>
                )}
            </div>
            <p className="text-xs font-medium text-ink-500">{label}</p>
            <p className={cn('mt-1 font-mono text-2xl font-semibold tabular tracking-tight', t.valueText)}>{value}</p>
            {progress !== undefined && (
                <div className="mt-3 h-1 overflow-hidden rounded-full bg-ink-100">
                    <div
                        className={cn(
                            'h-full rounded-full',
                            tone === 'sage' && 'bg-sage-500',
                            tone === 'warning' && 'bg-warning-500',
                            tone === 'danger' && 'bg-danger-500',
                            tone === 'brand' && 'bg-brand-500',
                            tone === 'neutral' && 'bg-ink-400',
                        )}
                        style={{ width: `${Math.min(Math.max(progress, 0), 100)}%` }}
                    />
                </div>
            )}
            {sub && <p className="mt-2 text-xs text-ink-500">{sub}</p>}
        </div>
    );
}
