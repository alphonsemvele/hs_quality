import { useCallback, useEffect, useState } from 'react';

/**
 * Tab state synchronised with the URL hash (#tab=foo).
 *
 * Reading the hash on mount preserves the tab on refresh and makes the
 * URL shareable. Writes use history.replaceState so the back button is
 * not polluted by every tab switch.
 *
 * Falls back to `defaultTab` when the hash is missing or contains a
 * value outside the allowed list (defensive against link rot).
 *
 * @example
 *   const [tab, setTab] = useUrlTab('news', ['messages', 'news', 'docs', 'qa']);
 */
export function useUrlTab<T extends string>(defaultTab: T, allowed: readonly T[]): [T, (value: T) => void] {
    const readFromHash = useCallback((): T => {
        if (typeof window === 'undefined') return defaultTab;
        const match = window.location.hash.match(/[#&]tab=([^&]+)/);
        if (!match) return defaultTab;
        const value = decodeURIComponent(match[1]);
        return (allowed as readonly string[]).includes(value) ? (value as T) : defaultTab;
    }, [defaultTab, allowed]);

    const [tab, setTabState] = useState<T>(readFromHash);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const onHashChange = () => setTabState(readFromHash());
        window.addEventListener('hashchange', onHashChange);
        return () => window.removeEventListener('hashchange', onHashChange);
    }, [readFromHash]);

    const setTab = useCallback(
        (value: T) => {
            setTabState(value);
            if (typeof window === 'undefined') return;
            const url = new URL(window.location.href);
            url.hash = `tab=${encodeURIComponent(value)}`;
            window.history.replaceState(null, '', url.toString());
        },
        [],
    );

    return [tab, setTab];
}
