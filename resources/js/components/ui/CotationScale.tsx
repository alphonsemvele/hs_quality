import { cn } from '@/lib/utils';
import { useRef } from 'react';

export type Cotation = 'A' | 'B' | 'C' | 'D' | 'NA';

interface CotationScaleProps {
    /** Current cotation. `null` (or undefined) means unrated. */
    value: Cotation | null | undefined;
    /** Called when the user picks a cotation. */
    onChange?: (value: Cotation) => void;
    /** Render read-only — no hover / click / keyboard interaction. */
    readOnly?: boolean;
    /** Optional `aria-label`. */
    label?: string;
    size?: 'sm' | 'md' | 'lg';
    className?: string;
    /** Show the equivalent numeric score (A→4, B→3...) below the buttons. */
    showScoreHint?: boolean;
}

const OPTIONS: { code: Cotation; label: string; score: number | null; tone: string }[] = [
    { code: 'A', label: 'A', score: 4, tone: 'bg-sage-500 text-white hover:bg-sage-600 dark:bg-sage-500 dark:hover:bg-sage-400' },
    { code: 'B', label: 'B', score: 3, tone: 'bg-sage-300 text-sage-900 hover:bg-sage-400 dark:bg-sage-400/70 dark:text-sage-50 dark:hover:bg-sage-400' },
    { code: 'C', label: 'C', score: 2, tone: 'bg-warning-400 text-warning-900 hover:bg-warning-500 dark:bg-warning-500/80 dark:text-warning-50 dark:hover:bg-warning-500' },
    { code: 'D', label: 'D', score: 1, tone: 'bg-danger-500 text-white hover:bg-danger-600 dark:bg-danger-500 dark:hover:bg-danger-400' },
    { code: 'NA', label: 'NA', score: null, tone: 'bg-ink-300 text-ink-800 hover:bg-ink-400 dark:bg-ink-600 dark:text-ink-100 dark:hover:bg-ink-500' },
];

const SIZE: Record<NonNullable<CotationScaleProps['size']>, string> = {
    sm: 'h-7 min-w-7 text-xs px-2',
    md: 'h-9 min-w-9 text-sm px-2.5',
    lg: 'h-11 min-w-11 text-base px-3',
};

const SCORE_LABEL: Record<Cotation, string> = {
    A: 'Atteint à un niveau avancé · 4 pts',
    B: 'Atteint · 3 pts',
    C: 'Partiellement atteint · 2 pts',
    D: 'Non atteint · 1 pt',
    NA: 'Non applicable · exclu',
};

/**
 * HAS-cotation control: A / B / C / D / NA, used by the unified SAP
 * audit grid (HAS + AFNOR + Cap'Handéo). Sibling of `RatingScale`.
 *
 * Keyboard: A/B/C/D/N pick the matching code; ← → cycle through options.
 */
export function CotationScale({ value, onChange, readOnly, label, size = 'md', className, showScoreHint }: CotationScaleProps) {
    const ref = useRef<HTMLDivElement>(null);
    const ariaLabel = label ?? 'Cotation HAS A / B / C / D / NA';
    const currentIndex = value ? OPTIONS.findIndex((o) => o.code === value) : -1;

    const pick = (code: Cotation) => {
        if (readOnly) return;
        onChange?.(code);
    };

    const handleKey = (e: React.KeyboardEvent<HTMLDivElement>) => {
        if (readOnly) return;
        const key = e.key.toUpperCase();
        if (key === 'A' || key === 'B' || key === 'C' || key === 'D') {
            e.preventDefault();
            pick(key as Cotation);
            return;
        }
        if (key === 'N' || key === 'NA') {
            e.preventDefault();
            pick('NA');
            return;
        }
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            const next = Math.min(OPTIONS.length - 1, Math.max(0, currentIndex) + 1);
            pick(OPTIONS[next].code);
            return;
        }
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            const prev = Math.max(0, (currentIndex === -1 ? OPTIONS.length : currentIndex) - 1);
            pick(OPTIONS[prev].code);
        }
    };

    return (
        <div className={cn('inline-flex flex-col gap-1', className)}>
            <div
                ref={ref}
                role="radiogroup"
                aria-label={ariaLabel}
                tabIndex={readOnly ? -1 : 0}
                onKeyDown={handleKey}
                className="inline-flex items-center gap-1.5 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40"
            >
                {OPTIONS.map((opt) => {
                    const selected = value === opt.code;
                    return (
                        <button
                            key={opt.code}
                            type="button"
                            role="radio"
                            aria-checked={selected}
                            aria-label={`Cotation ${opt.label} — ${SCORE_LABEL[opt.code]}`}
                            onClick={() => pick(opt.code)}
                            disabled={readOnly}
                            tabIndex={-1}
                            className={cn(
                                'inline-flex items-center justify-center rounded-md font-semibold transition-all disabled:cursor-default',
                                SIZE[size],
                                selected
                                    ? cn(opt.tone, 'ring-2 ring-offset-1 ring-brand-500 dark:ring-offset-ink-900')
                                    : 'bg-ink-100 text-ink-600 hover:bg-ink-200 dark:bg-ink-800 dark:text-ink-300 dark:hover:bg-ink-700',
                                !readOnly && 'cursor-pointer',
                            )}
                        >
                            {opt.label}
                        </button>
                    );
                })}
            </div>
            {showScoreHint && value && (
                <span className="text-[11px] text-ink-500 dark:text-ink-400">{SCORE_LABEL[value]}</span>
            )}
        </div>
    );
}
