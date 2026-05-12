import { cn } from '@/lib/utils';
import { ReactNode } from 'react';
import { Button } from './Button';

export interface WizardStep {
    id: string;
    label: string;
    description?: string;
    icon?: ReactNode;
}

interface WizardProps {
    steps: WizardStep[];
    currentStep: number;
    onStepChange?: (index: number) => void;
    title?: string;
    subtitle?: string;
    children: ReactNode;
    onPrevious?: () => void;
    onNext?: () => void;
    onCancel?: () => void;
    onFinish?: () => void;
    nextLabel?: string;
    finishLabel?: string;
    nextDisabled?: boolean;
    nextLoading?: boolean;
}

export function Wizard({
    steps,
    currentStep,
    onStepChange,
    title,
    subtitle,
    children,
    onPrevious,
    onNext,
    onCancel,
    onFinish,
    nextLabel = 'Suivant',
    finishLabel = 'Terminer',
    nextDisabled = false,
    nextLoading = false,
}: WizardProps) {
    const isFirst = currentStep === 0;
    const isLast = currentStep === steps.length - 1;
    const progress = ((currentStep + 1) / steps.length) * 100;

    return (
        <div className="mx-auto max-w-4xl">
            {(title || subtitle) && (
                <div className="mb-5">
                    {title && <h1 className="text-xl font-semibold text-ink-900 dark:text-white">{title}</h1>}
                    {subtitle && <p className="mt-1 text-sm text-ink-500 dark:text-ink-400">{subtitle}</p>}
                </div>
            )}

            {/* Stepper */}
            <nav aria-label="Progression" className="mb-6">
                <ol className="flex items-center gap-1 sm:gap-2">
                    {steps.map((step, i) => {
                        const completed = i < currentStep;
                        const active = i === currentStep;
                        const clickable = completed && onStepChange;
                        return (
                            <li key={step.id} className={cn('flex flex-1 items-center', i > 0 && '')}>
                                {i > 0 && (
                                    <span
                                        className={cn(
                                            'h-px flex-1 transition-colors',
                                            completed || active ? 'bg-brand-500' : 'bg-ink-200 dark:bg-ink-700',
                                        )}
                                    />
                                )}
                                <button
                                    type="button"
                                    onClick={clickable ? () => onStepChange?.(i) : undefined}
                                    disabled={!clickable}
                                    aria-current={active ? 'step' : undefined}
                                    className={cn(
                                        'group flex shrink-0 items-center gap-2 rounded-lg px-2 py-1 transition-colors',
                                        clickable && 'cursor-pointer hover:bg-ink-50 dark:hover:bg-ink-700/40',
                                        !clickable && 'cursor-default',
                                    )}
                                >
                                    <span
                                        className={cn(
                                            'flex size-7 shrink-0 items-center justify-center rounded-full font-mono text-xs font-semibold transition-colors',
                                            active && 'bg-brand-600 text-white shadow-[0_0_0_4px_rgba(99,102,241,0.18)]',
                                            completed && 'bg-sage-600 text-white',
                                            !active && !completed && 'bg-ink-100 text-ink-500 dark:bg-ink-700 dark:text-ink-400',
                                        )}
                                    >
                                        {completed ? <CheckIcon /> : i + 1}
                                    </span>
                                    <span
                                        className={cn(
                                            'hidden whitespace-nowrap text-[11px] font-medium uppercase tracking-wider sm:inline',
                                            active && 'text-ink-900 dark:text-white',
                                            completed && 'text-sage-700 dark:text-sage-300',
                                            !active && !completed && 'text-ink-400 dark:text-ink-500',
                                        )}
                                    >
                                        {step.label}
                                    </span>
                                </button>
                            </li>
                        );
                    })}
                </ol>
                {/* Mobile progress bar */}
                <div className="mt-3 h-1 overflow-hidden rounded-full bg-ink-100 sm:hidden dark:bg-ink-700">
                    <div
                        className="h-full bg-brand-500 transition-all duration-500"
                        style={{ width: `${progress}%` }}
                    />
                </div>
            </nav>

            {/* Step heading */}
            <div className="mb-5">
                <p className="text-[11px] font-semibold uppercase tracking-wider text-brand-600 dark:text-brand-400">
                    Étape {currentStep + 1} sur {steps.length}
                </p>
                <h2 className="mt-1 text-lg font-semibold text-ink-900 dark:text-white">
                    {steps[currentStep]?.label}
                </h2>
                {steps[currentStep]?.description && (
                    <p className="mt-1 text-sm text-ink-500 dark:text-ink-400">{steps[currentStep]?.description}</p>
                )}
            </div>

            {/* Step content */}
            <div className="rounded-2xl border border-ink-100 bg-white p-5 shadow-[0_1px_3px_rgba(15,23,42,0.04)] dark:border-ink-700/60 dark:bg-ink-800 dark:shadow-none">
                {children}
            </div>

            {/* Navigation */}
            <div className="mt-5 flex items-center justify-between gap-3">
                <div>
                    {onCancel && (
                        <Button variant="ghost" onClick={onCancel}>
                            Annuler
                        </Button>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    {!isFirst && onPrevious && (
                        <Button variant="secondary" onClick={onPrevious}>
                            ← Précédent
                        </Button>
                    )}
                    {!isLast && onNext && (
                        <Button onClick={onNext} disabled={nextDisabled} loading={nextLoading}>
                            {nextLabel}
                        </Button>
                    )}
                    {isLast && onFinish && (
                        <Button onClick={onFinish} disabled={nextDisabled} loading={nextLoading}>
                            {finishLabel}
                        </Button>
                    )}
                </div>
            </div>
        </div>
    );
}

function CheckIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
