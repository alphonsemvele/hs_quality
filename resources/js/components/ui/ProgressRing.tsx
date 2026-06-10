import { cn } from '@/lib/utils';

type Tone = 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';
type Size = 'sm' | 'md' | 'lg';

interface ProgressRingProps {
    /** Progress in [0, 100]. Values outside the range are clamped. */
    value: number;
    /** Visual tone (defaults to `brand`). */
    tone?: Tone;
    /** Diameter preset. `md` is the default sized for KPI compact mode. */
    size?: Size;
    /** Optional centre slot — number, "85 %", icon, … Auto-shows `value%` if omitted. */
    children?: React.ReactNode;
    /** Optional `aria-label` for the SVG. */
    label?: string;
    className?: string;
}

const TONE_STROKE: Record<Tone, string> = {
    brand: 'stroke-brand-500 dark:stroke-brand-400',
    sage: 'stroke-sage-500 dark:stroke-sage-400',
    warning: 'stroke-warning-500 dark:stroke-warning-400',
    danger: 'stroke-danger-500 dark:stroke-danger-400',
    neutral: 'stroke-ink-400 dark:stroke-ink-500',
};

const SIZE: Record<Size, { box: number; stroke: number; text: string }> = {
    sm: { box: 36, stroke: 4, text: 'text-[10px]' },
    md: { box: 56, stroke: 5, text: 'text-xs' },
    lg: { box: 88, stroke: 7, text: 'text-base' },
};

/**
 * Donut-style progress ring. Pure SVG, no animation library.
 *
 * Use it as a visual alternative to the progress bar already baked into
 * `KpiCard` — handy when the bar would crowd a small surface (compact
 * density, table row, drawer header) and you want a glanceable value.
 */
export function ProgressRing({ value, tone = 'brand', size = 'md', children, label, className }: ProgressRingProps) {
    const pct = Math.max(0, Math.min(100, value));
    const { box, stroke, text } = SIZE[size];
    const radius = (box - stroke) / 2;
    const circumference = 2 * Math.PI * radius;
    const offset = circumference * (1 - pct / 100);
    const labelText = label ?? `Progression : ${Math.round(pct)} %`;

    return (
        <div
            className={cn('relative inline-flex items-center justify-center', className)}
            style={{ width: box, height: box }}
            role="img"
            aria-label={labelText}
        >
            <svg width={box} height={box} viewBox={`0 0 ${box} ${box}`} aria-hidden>
                <circle
                    cx={box / 2}
                    cy={box / 2}
                    r={radius}
                    fill="none"
                    strokeWidth={stroke}
                    className="stroke-ink-100 dark:stroke-ink-700"
                />
                <circle
                    cx={box / 2}
                    cy={box / 2}
                    r={radius}
                    fill="none"
                    strokeWidth={stroke}
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={offset}
                    className={cn('-rotate-90 origin-center transition-[stroke-dashoffset] duration-700 ease-out', TONE_STROKE[tone])}
                    style={{ transformOrigin: 'center' }}
                />
            </svg>
            <span className={cn('absolute inset-0 flex items-center justify-center font-mono font-semibold tabular-nums text-ink-900 dark:text-white', text)}>
                {children ?? `${Math.round(pct)}%`}
            </span>
        </div>
    );
}
