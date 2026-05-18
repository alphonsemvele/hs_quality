import { useCallback, useEffect, useState } from 'react';

/**
 * Filter state synchronised with a URL search-param.
 *
 * Sibling to `useUrlTab` — same idea but uses `?key=value` instead of
 * `#tab=value` so the URL is bookmark/share-friendly and shows the
 * active filter without an ugly hash.
 *
 * Writes use `history.replaceState` so the back button is not polluted
 * by every filter change. Reads on mount preserve filter on refresh.
 *
 * Falls back to `defaultValue` when the param is missing OR holds a
 * value outside the allowed set (defensive against link rot).
 *
 * @example
 *   const [gravite, setGravite] = useUrlFilter('gravite', 'all', ['all', 'critique', 'majeur']);
 */
export function useUrlFilter<T extends string>(
    paramName: string,
    defaultValue: T,
    allowed: readonly T[],
): [T, (value: T) => void] {
    const readFromUrl = useCallback((): T => {
        if (typeof window === 'undefined') return defaultValue;
        const params = new URLSearchParams(window.location.search);
        const value = params.get(paramName);
        if (value === null) return defaultValue;
        return (allowed as readonly string[]).includes(value) ? (value as T) : defaultValue;
    }, [paramName, defaultValue, allowed]);

    const [value, setValueState] = useState<T>(readFromUrl);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const onPop = () => setValueState(readFromUrl());
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    }, [readFromUrl]);

    const setValue = useCallback(
        (next: T) => {
            setValueState(next);
            if (typeof window === 'undefined') return;
            const url = new URL(window.location.href);
            if (next === defaultValue) {
                url.searchParams.delete(paramName);
            } else {
                url.searchParams.set(paramName, next);
            }
            window.history.replaceState(null, '', url.toString());
        },
        [paramName, defaultValue],
    );

    return [value, setValue];
}

/**
 * Boolean variant — toggle stored as `?key=1` (truthy) or absent (false).
 */
export function useUrlBoolFilter(
    paramName: string,
    defaultValue = false,
): [boolean, (value: boolean) => void] {
    const readFromUrl = useCallback((): boolean => {
        if (typeof window === 'undefined') return defaultValue;
        const params = new URLSearchParams(window.location.search);
        const v = params.get(paramName);
        if (v === null) return defaultValue;
        return v === '1' || v === 'true';
    }, [paramName, defaultValue]);

    const [value, setValueState] = useState<boolean>(readFromUrl);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const onPop = () => setValueState(readFromUrl());
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    }, [readFromUrl]);

    const setValue = useCallback(
        (next: boolean) => {
            setValueState(next);
            if (typeof window === 'undefined') return;
            const url = new URL(window.location.href);
            if (next === defaultValue) {
                url.searchParams.delete(paramName);
            } else {
                url.searchParams.set(paramName, next ? '1' : '0');
            }
            window.history.replaceState(null, '', url.toString());
        },
        [paramName, defaultValue],
    );

    return [value, setValue];
}
