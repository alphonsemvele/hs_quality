import { cn } from '@/lib/utils';

export type MoodValue = 1 | 2 | 3 | 4 | 5;

const MOODS: Array<{ value: MoodValue; label: string; tone: string; emoji: string }> = [
    { value: 1, label: 'Très difficile', tone: 'bg-danger-500', emoji: '😞' },
    { value: 2, label: 'Difficile', tone: 'bg-warning-500', emoji: '😕' },
    { value: 3, label: 'Neutre', tone: 'bg-ink-400', emoji: '😐' },
    { value: 4, label: 'Bon', tone: 'bg-sage-500', emoji: '🙂' },
    { value: 5, label: 'Très bon', tone: 'bg-sage-600', emoji: '😄' },
];

interface Props {
    value: MoodValue | null;
    onChange: (v: MoodValue) => void;
    name?: string;
    disabled?: boolean;
}

export function MoodSelector({ value, onChange, name = 'mood', disabled }: Props) {
    return (
        <fieldset
            className="grid grid-cols-5 gap-2 sm:gap-3"
            aria-label="Sélecteur d'humeur"
            disabled={disabled}
        >
            {MOODS.map((m) => {
                const active = value === m.value;
                return (
                    <label
                        key={m.value}
                        className={cn(
                            'group relative flex cursor-pointer flex-col items-center gap-1.5 rounded-2xl border-2 px-2 py-3 text-center transition-all duration-150',
                            active
                                ? 'border-brand-500 bg-brand-50 shadow-[0_0_0_4px_rgba(99,102,241,0.18)] dark:border-brand-400 dark:bg-brand-900/30'
                                : 'border-ink-200 bg-white hover:border-ink-300 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:hover:border-ink-600 dark:hover:bg-ink-700/40',
                            disabled && 'cursor-not-allowed opacity-50',
                        )}
                    >
                        <input
                            type="radio"
                            name={name}
                            value={m.value}
                            checked={active}
                            onChange={() => onChange(m.value)}
                            disabled={disabled}
                            className="absolute size-0 opacity-0"
                            aria-label={m.label}
                        />
                        <span aria-hidden className="text-2xl leading-none transition-transform group-hover:scale-110">
                            {m.emoji}
                        </span>
                        <span
                            className={cn(
                                'text-[10px] font-medium uppercase tracking-wider',
                                active ? 'text-brand-700 dark:text-brand-200' : 'text-ink-500 dark:text-ink-400',
                            )}
                        >
                            {m.label}
                        </span>
                    </label>
                );
            })}
        </fieldset>
    );
}
