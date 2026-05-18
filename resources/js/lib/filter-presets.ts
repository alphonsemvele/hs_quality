/**
 * Saved filter presets — per-page, per-device.
 *
 * Stores named combinations of URL search params so a coordinateur can
 * pin frequent filter sets ("Incidents graves cette semaine", "ARS
 * notifiée non clos", …) and re-apply them in one click.
 *
 * Backed by plain `localStorage` (per origin), one bucket per `pageKey`
 * (`hsq.filter-presets.incidents`, `hsq.filter-presets.interventions`,
 * etc.). Presets carry only a name and an opaque query-string snippet —
 * no domain data.
 */

export interface FilterPreset {
    id: string;
    name: string;
    /** Query string snippet without the leading "?" (`gravite=grave&statut=declare`). */
    qs: string;
    createdAt: number;
}

const MAX_PER_PAGE = 6;

function storageKey(pageKey: string): string {
    return `hsq.filter-presets.${pageKey}`;
}

function read(pageKey: string): FilterPreset[] {
    if (typeof window === 'undefined') return [];
    try {
        const raw = window.localStorage.getItem(storageKey(pageKey));
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed.filter(isPreset);
    } catch {
        return [];
    }
}

function write(pageKey: string, presets: FilterPreset[]): void {
    if (typeof window === 'undefined') return;
    try {
        window.localStorage.setItem(storageKey(pageKey), JSON.stringify(presets.slice(0, MAX_PER_PAGE)));
        window.dispatchEvent(new CustomEvent('hsq:filter-presets-changed', { detail: { pageKey } }));
    } catch {
        // ignore quota errors
    }
}

function isPreset(x: unknown): x is FilterPreset {
    if (!x || typeof x !== 'object') return false;
    const o = x as Record<string, unknown>;
    return (
        typeof o.id === 'string'
        && typeof o.name === 'string'
        && typeof o.qs === 'string'
        && typeof o.createdAt === 'number'
    );
}

export function listPresets(pageKey: string): FilterPreset[] {
    return read(pageKey);
}

export function savePreset(pageKey: string, name: string, qs: string): FilterPreset {
    const existing = read(pageKey);
    const preset: FilterPreset = {
        id: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        name: name.trim().slice(0, 40),
        qs: qs.replace(/^\?/, ''),
        createdAt: Date.now(),
    };
    write(pageKey, [preset, ...existing]);
    return preset;
}

export function deletePreset(pageKey: string, id: string): void {
    write(pageKey, read(pageKey).filter((p) => p.id !== id));
}
