import { DENSITIES, type Density, getStoredDensity, setDensity } from '@/lib/density';
import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';

/**
 * Two-option pill switch for the per-user UI density.
 *
 * Persists choice to localStorage and toggles `data-density` on <html>.
 * Mirrors the visual language of the theme-accent toggle so the two can
 * sit side-by-side in profile preferences.
 */
export function DensityToggle({ className }: { className?: string }) {
    const [active, setActive] = useState<Density>(() => getStoredDensity());

    useEffect(() => {
        const onChange = (e: Event) => {
            const detail = (e as CustomEvent<{ density: Density }>).detail;
            if (detail?.density) setActive(detail.density);
        };
        window.addEventListener('hsq:density-changed', onChange);
        return () => window.removeEventListener('hsq:density-changed', onChange);
    }, []);

    const onPick = (key: Density) => {
        setActive(key);
        setDensity(key);
    };

    return (
        <div
            className={cn(
                'inline-flex rounded-full border border-ink-200 bg-white p-0.5 text-xs font-medium dark:border-ink-700 dark:bg-ink-800',
                className,
            )}
            role="radiogroup"
            aria-label="Densité d'affichage"
        >
            {DENSITIES.map((opt) => {
                const isActive = active === opt.key;
                return (
                    <button
                        key={opt.key}
                        type="button"
                        role="radio"
                        aria-checked={isActive}
                        onClick={() => onPick(opt.key)}
                        className={
                            isActive
                                ? 'rounded-full bg-ink-900 px-3 py-1 text-white dark:bg-white dark:text-ink-900'
                                : 'rounded-full px-3 py-1 text-ink-600 hover:text-ink-900 dark:text-ink-300 dark:hover:text-white'
                        }
                    >
                        {opt.label}
                    </button>
                );
            })}
        </div>
    );
}
