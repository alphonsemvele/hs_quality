/**
 * Theme accent — per-user color preference (FE-only).
 *
 * Writes `data-accent="<key>"` on the root `<html>` element; matching CSS
 * rules in `resources/css/app.css` rebind `--color-brand-*` to a different
 * palette. Persisted to `localStorage` under `STORAGE_KEY`.
 *
 * The default ("brand") is the steel-blue palette declared in `@theme`,
 * applied when no `data-accent` attribute is set.
 */

export type ThemeAccent = 'brand' | 'sage' | 'mauve';

export const THEME_ACCENTS: { key: ThemeAccent; label: string; swatch: string }[] = [
    { key: 'brand', label: 'Bleu acier', swatch: '#2a7ac2' },
    { key: 'sage', label: 'Vert sauge', swatch: '#4a9568' },
    { key: 'mauve', label: 'Mauve', swatch: '#9333ea' },
];

const STORAGE_KEY = 'hsq.theme.accent';

export function getStoredAccent(): ThemeAccent {
    if (typeof window === 'undefined') return 'brand';
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (raw === 'sage' || raw === 'mauve' || raw === 'brand') return raw;
    } catch {
        // ignore
    }
    return 'brand';
}

export function applyAccent(accent: ThemeAccent): void {
    if (typeof document === 'undefined') return;
    if (accent === 'brand') {
        document.documentElement.removeAttribute('data-accent');
    } else {
        document.documentElement.setAttribute('data-accent', accent);
    }
}

export function setAccent(accent: ThemeAccent): void {
    applyAccent(accent);
    try {
        if (accent === 'brand') {
            window.localStorage.removeItem(STORAGE_KEY);
        } else {
            window.localStorage.setItem(STORAGE_KEY, accent);
        }
    } catch {
        // ignore
    }
    try {
        window.dispatchEvent(new CustomEvent('hsq:accent-changed', { detail: { accent } }));
    } catch {
        // ignore
    }
}
