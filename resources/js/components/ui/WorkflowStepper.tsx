import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

export interface WorkflowStep {
    key: string;
    label: string;
    /** Short caption (date, by whom…). */
    caption?: string;
    /** When `true`, this step is highlighted as the current state. */
    isCurrent?: boolean;
    /** Optional icon override. Defaults to a numbered badge. */
    icon?: ReactNode;
}

interface WorkflowStepperProps {
    /** Ordered list of steps in the workflow. */
    steps: WorkflowStep[];
    /**
     * Index of the active step (0-based). Steps before it are rendered as
     * "done", the active one as "current", and the ones after as
     * "upcoming". A negative index marks every step as upcoming.
     */
    activeIndex: number;
    /** Optional tone override; defaults to `brand`. */
    tone?: 'brand' | 'sage' | 'warning' | 'danger';
    className?: string;
}

const TONE_CLASS: Record<NonNullable<WorkflowStepperProps['tone']>, { dot: string; line: string; current: string }> = {
    brand: {
        dot: 'bg-brand-600 text-white border-brand-600',
        line: 'bg-brand-300 dark:bg-brand-500/50',
        current: 'ring-brand-200 dark:ring-brand-700/60',
    },
    sage: {
        dot: 'bg-sage-600 text-white border-sage-600',
        line: 'bg-sage-300 dark:bg-sage-500/50',
        current: 'ring-sage-200 dark:ring-sage-700/60',
    },
    warning: {
        dot: 'bg-warning-600 text-white border-warning-600',
        line: 'bg-warning-300 dark:bg-warning-500/50',
        current: 'ring-warning-200 dark:ring-warning-700/60',
    },
    danger: {
        dot: 'bg-danger-600 text-white border-danger-600',
        line: 'bg-danger-300 dark:bg-danger-500/50',
        current: 'ring-danger-200 dark:ring-danger-700/60',
    },
};

/**
 * Horizontal progress indicator for a state machine. Renders a row of dots
 * connected by lines; the active step is ring-highlighted.
 *
 * Use it on a state-machine show page (incidents, audits, PAC actions…)
 * to make the current state and what comes next legible at a glance.
 */
export function WorkflowStepper({ steps, activeIndex, tone = 'brand', className }: WorkflowStepperProps) {
    const t = TONE_CLASS[tone];

    return (
        <ol
            role="list"
            aria-label="Étapes du workflow"
            className={cn('flex w-full items-start gap-0 overflow-x-auto', className)}
        >
            {steps.map((step, index) => {
                const isPast = index < activeIndex;
                const isCurrent = index === activeIndex;
                const isFuture = index > activeIndex;
                const isLast = index === steps.length - 1;

                return (
                    <li key={step.key} className="flex min-w-0 flex-1 items-start" aria-current={isCurrent ? 'step' : undefined}>
                        <div className="flex min-w-0 flex-1 flex-col items-center text-center">
                            <span
                                className={cn(
                                    'relative flex size-9 items-center justify-center rounded-full border-2 text-sm font-bold transition-all',
                                    isPast && t.dot,
                                    isCurrent && [t.dot, 'ring-4', t.current],
                                    isFuture && 'border-ink-200 bg-white text-ink-400 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-500',
                                )}
                            >
                                {step.icon ?? (isPast ? <CheckIcon /> : index + 1)}
                            </span>
                            <p
                                className={cn(
                                    'mt-2 max-w-[10rem] truncate text-xs font-medium',
                                    isCurrent ? 'text-ink-900 dark:text-white' : 'text-ink-600 dark:text-ink-400',
                                )}
                                title={step.label}
                            >
                                {step.label}
                            </p>
                            {step.caption && (
                                <p className="mt-0.5 max-w-[10rem] truncate text-[10px] text-ink-400 dark:text-ink-500" title={step.caption}>
                                    {step.caption}
                                </p>
                            )}
                        </div>
                        {!isLast && (
                            <span
                                className={cn(
                                    'mt-[1.05rem] h-0.5 flex-1 self-start',
                                    isPast ? t.line : 'bg-ink-200 dark:bg-ink-700',
                                )}
                                aria-hidden="true"
                            />
                        )}
                    </li>
                );
            })}
        </ol>
    );
}

function CheckIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2.5}>
            <path d="M5 12l5 5L20 7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
