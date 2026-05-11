import { cn } from '@/lib/utils';

export type LikertValue = 1 | 2 | 3 | 4 | 5;

const LIKERT_LABELS: Record<LikertValue, string> = {
    1: 'Pas du tout',
    2: 'Plutôt non',
    3: 'Neutre',
    4: 'Plutôt oui',
    5: 'Totalement',
};

interface Props {
    name: string;
    value: LikertValue | null;
    onChange: (v: LikertValue) => void;
    minLabel?: string;
    maxLabel?: string;
    disabled?: boolean;
    inverted?: boolean;
}

export function LikertScale({ name, value, onChange, minLabel, maxLabel, disabled, inverted }: Props) {
    return (
        <div className="space-y-2">
            <fieldset className="flex items-stretch gap-1.5 sm:gap-2" aria-label="Échelle de Likert">
                {([1, 2, 3, 4, 5] as LikertValue[]).map((v) => {
                    const active = value === v;
                    return (
                        <label
                            key={v}
                            className={cn(
                                'group flex flex-1 cursor-pointer flex-col items-center gap-1 rounded-xl border-2 px-1 py-2.5 text-center transition-all duration-150',
                                active
                                    ? 'border-brand-500 bg-brand-50 dark:border-brand-400 dark:bg-brand-900/30'
                                    : 'border-ink-200 bg-white hover:border-ink-300 dark:border-ink-700 dark:bg-ink-800 dark:hover:border-ink-600',
                                disabled && 'cursor-not-allowed opacity-50',
                            )}
                        >
                            <input
                                type="radio"
                                name={name}
                                value={v}
                                checked={active}
                                onChange={() => onChange(v)}
                                disabled={disabled}
                                className="absolute size-0 opacity-0"
                                aria-label={LIKERT_LABELS[v]}
                            />
                            <span
                                className={cn(
                                    'flex size-6 items-center justify-center rounded-full font-mono text-xs font-semibold',
                                    active
                                        ? 'bg-brand-600 text-white'
                                        : 'bg-ink-100 text-ink-500 group-hover:bg-ink-200 dark:bg-ink-700 dark:text-ink-300 dark:group-hover:bg-ink-600',
                                )}
                            >
                                {v}
                            </span>
                            <span
                                className={cn(
                                    'hidden text-[10px] font-medium leading-tight sm:block',
                                    active ? 'text-brand-700 dark:text-brand-200' : 'text-ink-500 dark:text-ink-400',
                                )}
                            >
                                {LIKERT_LABELS[v]}
                            </span>
                        </label>
                    );
                })}
            </fieldset>
            {(minLabel || maxLabel) && (
                <div className="flex items-center justify-between text-[10px] text-ink-400 dark:text-ink-500">
                    <span>{inverted ? maxLabel : minLabel}</span>
                    <span>{inverted ? minLabel : maxLabel}</span>
                </div>
            )}
        </div>
    );
}
