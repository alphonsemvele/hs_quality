import { cn } from '@/lib/utils';
import { useRef } from 'react';

interface RatingScaleProps {
    /** Current value (0 = unrated). */
    value: number;
    /** Called when the user picks a value. */
    onChange?: (value: number) => void;
    /** Number of stars. Default 5. */
    max?: number;
    /** Render read-only — no hover / click / keyboard interaction. */
    readOnly?: boolean;
    /** Optional `aria-label` (defaults to "Note sur N étoiles"). */
    label?: string;
    /** Star size. */
    size?: 'sm' | 'md' | 'lg';
    className?: string;
}

const SIZE: Record<NonNullable<RatingScaleProps['size']>, string> = {
    sm: 'size-4',
    md: 'size-5',
    lg: 'size-7',
};

/**
 * 1–N star rating control.
 *
 * Used for satisfaction surveys (intervention follow-up forms, QVCT
 * verbatims). Keyboard-navigable: arrow left/right moves the rating,
 * arrow up/down jumps to extremes, Home/End jump to 1 and max, 1–9
 * digit keys set the value directly.
 *
 * Half-star not supported on purpose — keep the UI honest.
 */
export function RatingScale({ value, onChange, max = 5, readOnly, label, size = 'md', className }: RatingScaleProps) {
    const ref = useRef<HTMLDivElement>(null);
    const ariaLabel = label ?? `Note sur ${max} étoiles`;
    const safeValue = Math.max(0, Math.min(max, Math.round(value)));

    const pick = (n: number) => {
        if (readOnly) return;
        onChange?.(n);
    };

    const handleKey = (e: React.KeyboardEvent<HTMLDivElement>) => {
        if (readOnly) return;
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            pick(Math.min(max, safeValue + 1));
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            pick(Math.max(0, safeValue - 1));
        } else if (e.key === 'ArrowUp' || e.key === 'End') {
            e.preventDefault();
            pick(max);
        } else if (e.key === 'ArrowDown' || e.key === 'Home') {
            e.preventDefault();
            pick(1);
        } else if (/^[1-9]$/.test(e.key)) {
            const n = parseInt(e.key, 10);
            if (n <= max) {
                e.preventDefault();
                pick(n);
            }
        }
    };

    return (
        <div
            ref={ref}
            role="radiogroup"
            aria-label={ariaLabel}
            tabIndex={readOnly ? -1 : 0}
            onKeyDown={handleKey}
            className={cn('inline-flex items-center gap-1 rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40', className)}
        >
            {Array.from({ length: max }, (_, i) => {
                const n = i + 1;
                const filled = n <= safeValue;
                return (
                    <button
                        key={n}
                        type="button"
                        role="radio"
                        aria-checked={n === safeValue}
                        aria-label={`${n} étoile${n > 1 ? 's' : ''}`}
                        onClick={() => pick(n)}
                        disabled={readOnly}
                        tabIndex={-1}
                        className={cn(
                            'inline-flex items-center justify-center rounded-sm transition-transform disabled:cursor-default',
                            !readOnly && 'cursor-pointer hover:scale-110',
                        )}
                    >
                        <svg
                            className={cn(
                                SIZE[size],
                                filled
                                    ? 'fill-warning-400 stroke-warning-500 dark:fill-warning-300 dark:stroke-warning-400'
                                    : 'fill-transparent stroke-ink-300 dark:stroke-ink-600',
                            )}
                            viewBox="0 0 24 24"
                            strokeWidth="1.5"
                            strokeLinejoin="round"
                            aria-hidden
                        >
                            <path d="M12 2.5l2.9 6.6 7.1.7-5.4 4.8 1.6 7-6.2-3.7-6.2 3.7 1.6-7L2 9.8l7.1-.7z" />
                        </svg>
                    </button>
                );
            })}
        </div>
    );
}
