import { THEME_ACCENTS, type ThemeAccent, getStoredAccent, setAccent } from '@/lib/theme-accent';
import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';

/**
 * Small swatch picker for the per-user theme accent.
 *
 * Persists choice to localStorage and rebinds `--color-brand-*` via a
 * `data-accent` attribute on `<html>`. Designed to sit in profile /
 * preferences pages — kept compact so it fits next to other settings.
 */
export function ThemeAccentToggle({ className }: { className?: string }) {
    const [active, setActive] = useState<ThemeAccent>(() => getStoredAccent());

    useEffect(() => {
        const onChange = (e: Event) => {
            const detail = (e as CustomEvent<{ accent: ThemeAccent }>).detail;
            if (detail?.accent) setActive(detail.accent);
        };
        window.addEventListener('hsq:accent-changed', onChange);
        return () => window.removeEventListener('hsq:accent-changed', onChange);
    }, []);

    const onPick = (key: ThemeAccent) => {
        setActive(key);
        setAccent(key);
    };

    return (
        <div className={cn('flex items-center gap-2', className)} role="radiogroup" aria-label="Couleur d'accent">
            {THEME_ACCENTS.map((opt) => {
                const isActive = active === opt.key;
                return (
                    <button
                        key={opt.key}
                        type="button"
                        role="radio"
                        aria-checked={isActive}
                        title={opt.label}
                        onClick={() => onPick(opt.key)}
                        className={cn(
                            'group relative inline-flex size-7 items-center justify-center rounded-full border-2 transition-all',
                            isActive
                                ? 'border-ink-900 dark:border-white'
                                : 'border-transparent hover:border-ink-300 dark:hover:border-ink-500',
                        )}
                    >
                        <span
                            className="size-5 rounded-full shadow-sm ring-1 ring-black/5"
                            style={{ backgroundColor: opt.swatch }}
                            aria-hidden
                        />
                        <span className="sr-only">{opt.label}</span>
                    </button>
                );
            })}
            <span className="text-[11px] text-ink-500 dark:text-ink-400">
                {THEME_ACCENTS.find((a) => a.key === active)?.label}
            </span>
        </div>
    );
}
