import { useCallback, useEffect, useState } from 'react';

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

export default function ThemeToggle() {
    const { appearance, setAppearance } = useAppearance();

    const next: Appearance =
        appearance === 'light' ? 'dark' : appearance === 'dark' ? 'system' : 'light';

    const label =
        appearance === 'light' ? 'Clair' : appearance === 'dark' ? 'Sombre' : 'Système';

    return (
        <button
            type="button"
            onClick={() => setAppearance(next)}
            className="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-ink-700 transition-colors hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700"
            aria-label={`Thème actuel : ${label}. Cliquer pour changer.`}
        >
            {appearance === 'light' && <SunIcon />}
            {appearance === 'dark' && <MoonIcon />}
            {appearance === 'system' && <MonitorIcon />}
            <span>{label}</span>
        </button>
    );
}

function SunIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="5" />
            <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
    );
}

function MoonIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" />
        </svg>
    );
}

function MonitorIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75} strokeLinecap="round" strokeLinejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
            <line x1="8" y1="21" x2="16" y2="21" />
            <line x1="12" y1="17" x2="12" y2="21" />
        </svg>
    );
}
