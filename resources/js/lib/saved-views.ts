import { useCallback, useEffect, useState } from 'react';

export interface SavedView {
    id: string;
    name: string;
    query: string;
    createdAt: string;
}

const storageKey = (pageKey: string): string => `hsq.saved-views.${pageKey}`;

function read(pageKey: string): SavedView[] {
    if (typeof window === 'undefined') return [];
    try {
        const raw = window.localStorage.getItem(storageKey(pageKey));
        if (!raw) return [];
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function write(pageKey: string, views: SavedView[]): void {
    if (typeof window === 'undefined') return;
    try {
        window.localStorage.setItem(storageKey(pageKey), JSON.stringify(views));
    } catch {
        // ignore
    }
}

/**
 * Tracks saved filter views for a list page. Storage is local to the browser —
 * cross-device sync would require a tenant-scoped table (deferred).
 */
export function useSavedViews(pageKey: string): {
    views: SavedView[];
    save: (name: string, query: string) => SavedView;
    remove: (id: string) => void;
    rename: (id: string, name: string) => void;
} {
    const [views, setViews] = useState<SavedView[]>([]);

    useEffect(() => {
        setViews(read(pageKey));
    }, [pageKey]);

    const persist = useCallback(
        (next: SavedView[]) => {
            setViews(next);
            write(pageKey, next);
        },
        [pageKey],
    );

    const save = useCallback(
        (name: string, query: string): SavedView => {
            const view: SavedView = {
                id: `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`,
                name: name.trim() || 'Sans titre',
                query,
                createdAt: new Date().toISOString(),
            };
            persist([view, ...views].slice(0, 20));
            return view;
        },
        [persist, views],
    );

    const remove = useCallback(
        (id: string) => {
            persist(views.filter((v) => v.id !== id));
        },
        [persist, views],
    );

    const rename = useCallback(
        (id: string, name: string) => {
            persist(views.map((v) => (v.id === id ? { ...v, name: name.trim() || 'Sans titre' } : v)));
        },
        [persist, views],
    );

    return { views, save, remove, rename };
}
