/**
 * UI density — per-user preference (FE-only).
 *
 * Writes `data-density="compact"` on the root <html>; CSS rules in
 * `resources/css/app.css` shrink cell padding and section gaps without
 * touching individual components. Default (no attribute) keeps the
 * comfortable spacing already used everywhere.
 */

export type Density = 'comfortable' | 'compact';

export const DENSITIES: { key: Density; label: string }[] = [
    { key: 'comfortable', label: 'Confortable' },
    { key: 'compact', label: 'Compact' },
];

const STORAGE_KEY = 'hsq.ui.density';

export function getStoredDensity(): Density {
    if (typeof window === 'undefined') return 'comfortable';
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (raw === 'compact' || raw === 'comfortable') return raw;
    } catch {
        // ignore
    }
    return 'comfortable';
}

export function applyDensity(density: Density): void {
    if (typeof document === 'undefined') return;
    if (density === 'comfortable') {
        document.documentElement.removeAttribute('data-density');
    } else {
        document.documentElement.setAttribute('data-density', density);
    }
}

export function setDensity(density: Density): void {
    applyDensity(density);
    try {
        if (density === 'comfortable') {
            window.localStorage.removeItem(STORAGE_KEY);
        } else {
            window.localStorage.setItem(STORAGE_KEY, density);
        }
    } catch {
        // ignore
    }
    try {
        window.dispatchEvent(new CustomEvent('hsq:density-changed', { detail: { density } }));
    } catch {
        // ignore
    }
}
