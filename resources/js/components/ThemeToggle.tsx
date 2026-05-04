import { useCallback, useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

type Appearance = 'light' | 'dark' | 'system';

function getStoredAppearance(): Appearance {
    if (typeof window === 'undefined') return 'system';
    const stored = localStorage.getItem('appearance');
    if (stored === 'light' || stored === 'dark') return stored;
    return 'system';
}

function applyTheme(appearance: Appearance) {
    const isDark =
        appearance === 'dark' ||
        (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', isDark);
}

export function useAppearance() {
    const [appearance, setAppearanceState] = useState<Appearance>(getStoredAppearance);

    const setAppearance = useCallback((mode: Appearance) => {
        setAppearanceState(mode);
        localStorage.setItem('appearance', mode);
        applyTheme(mode);
    }, []);

    useEffect(() => {
        const mq = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = () => {
            if (getStoredAppearance() === 'system') {
                applyTheme('system');
            }
        };
        mq.addEventListener('change', handler);
        return () => mq.removeEventListener('change', handler);
    }, []);

    return { appearance, setAppearance };
}

const OPTIONS: { value: Appearance; label: string; icon: React.ReactNode }[] = [
    { value: 'light', label: 'Clair', icon: <SunIcon /> },
    { value: 'system', label: 'Auto', icon: <MonitorIcon /> },
    { value: 'dark', label: 'Sombre', icon: <MoonIcon /> },
];

export default function ThemeToggle() {
    const { appearance, setAppearance } = useAppearance();

    return (
        <div className="px-3 py-2" role="radiogroup" aria-label="Thème d'affichage">
            <p className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-ink-400 dark:text-ink-500">
                Apparence
            </p>
            <div className="flex gap-1 rounded-lg bg-ink-100 p-1 dark:bg-ink-900/60">
                {OPTIONS.map((opt) => {
                    const active = appearance === opt.value;
                    return (
                        <button
                            key={opt.value}
                            type="button"
                            role="radio"
                            aria-checked={active}
                            aria-label={`Thème ${opt.label}`}
                            onClick={() => setAppearance(opt.value)}
                            className={cn(
                                'flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-all duration-150',
                                active
                                    ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-700 dark:text-white'
                                    : 'text-ink-500 hover:text-ink-700 dark:text-ink-400 dark:hover:text-ink-300',
                            )}
                        >
                            <span className={cn('shrink-0 transition-colors', active ? 'text-brand-500 dark:text-brand-400' : '')}>
                                {opt.icon}
                            </span>
                            <span>{opt.label}</span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}

function SunIcon() {
    return (
        <svg className="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="5" />
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
    );
}

function MoonIcon() {
    return (
        <svg className="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
        </svg>
    );
}

function MonitorIcon() {
    return (
        <svg className="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
            <line x1="8" y1="21" x2="16" y2="21" />
            <line x1="12" y1="17" x2="12" y2="21" />
        </svg>
    );
}
