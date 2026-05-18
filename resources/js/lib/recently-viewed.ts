/**
 * Recently viewed — small per-device history of dashboard items.
 *
 * Tracks the last N (default 5) entities the user has opened so the
 * dashboard can surface a quick "Reprendre où vous étiez" row and the
 * command palette can show contextual suggestions.
 *
 * Storage is plain `localStorage`. Cross-tenant leaks are not a concern:
 * localStorage is per-origin/per-browser-profile, and the entries only
 * carry IDs + display labels (no health data).
 */

export type RecentKind =
    | 'beneficiary'
    | 'intervention'
    | 'incident'
    | 'audit'
    | 'plan-amelioration'
    | 'formation'
    | 'qvct-campaign';

export interface RecentItem {
    kind: RecentKind;
    id: string;
    label: string;
    href: string;
    viewedAt: number;
}

const STORAGE_KEY = 'hsq.recently-viewed';
const MAX_ITEMS = 8;

export const RECENT_KIND_LABEL: Record<RecentKind, string> = {
    beneficiary: 'Bénéficiaire',
    intervention: 'Intervention',
    incident: 'Incident',
    audit: 'Audit',
    'plan-amelioration': "Plan d'amélioration",
    formation: 'Formation',
    'qvct-campaign': 'Campagne QVCT',
};

function read(): RecentItem[] {
    if (typeof window === 'undefined') return [];
    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        if (!Array.isArray(parsed)) return [];
        return parsed.filter(isRecentItem);
    } catch {
        return [];
    }
}

function write(items: RecentItem[]): void {
    if (typeof window === 'undefined') return;
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items.slice(0, MAX_ITEMS)));
        window.dispatchEvent(new CustomEvent('hsq:recently-viewed-changed'));
    } catch {
        // ignore quota errors
    }
}

function isRecentItem(x: unknown): x is RecentItem {
    if (!x || typeof x !== 'object') return false;
    const o = x as Record<string, unknown>;
    return (
        typeof o.kind === 'string'
        && typeof o.id === 'string'
        && typeof o.label === 'string'
        && typeof o.href === 'string'
        && typeof o.viewedAt === 'number'
    );
}

export function trackRecent(item: Omit<RecentItem, 'viewedAt'>): void {
    const existing = read().filter((r) => !(r.kind === item.kind && r.id === item.id));
    const next: RecentItem[] = [{ ...item, viewedAt: Date.now() }, ...existing];
    write(next);
}

export function getRecent(): RecentItem[] {
    return read();
}

export function clearRecent(): void {
    write([]);
}
