import { useEffect, useState } from 'react';

interface RelativeTimeProps {
    /** ISO-ish datetime string (anything `new Date(value)` accepts). */
    value: string | null | undefined;
    /** Fallback rendered when `value` is null/undefined/unparseable. */
    fallback?: string;
    /** Optional className passthrough. */
    className?: string;
}

/**
 * Renders a French relative duration ("il y a 5 min") as visible text,
 * with the full localised date+time exposed via the native browser
 * tooltip (`title` attribute) and via a screen-reader label.
 *
 * Re-renders once a minute so "à l'instant" turns into "il y a 1 min"
 * without a page reload. Stops updating after 1 hour to keep idle pages
 * cheap.
 */
export function RelativeTime({ value, fallback = '—', className }: RelativeTimeProps) {
    const [tick, setTick] = useState(0);

    useEffect(() => {
        if (!value) return;
        const d = new Date(value);
        if (Number.isNaN(d.getTime())) return;
        const ageSec = (Date.now() - d.getTime()) / 1000;
        if (ageSec > 3600) return;
        const interval = window.setInterval(() => setTick((t) => t + 1), 60_000);
        return () => window.clearInterval(interval);
    }, [value]);

    if (!value) {
        return <span className={className}>{fallback}</span>;
    }
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
        return <span className={className}>{fallback}</span>;
    }

    const label = formatRelative(d);
    const full = d.toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

    return (
        <time
            dateTime={d.toISOString()}
            title={full}
            aria-label={full}
            className={className}
            data-tick={tick}
        >
            {label}
        </time>
    );
}

function formatRelative(d: Date): string {
    const diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 0) return "à l'instant";
    if (diff < 60) return "à l'instant";
    if (diff < 3600) return `il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `il y a ${Math.floor(diff / 3600)} h`;
    if (diff < 86400 * 7) return `il y a ${Math.floor(diff / 86400)} j`;
    if (diff < 86400 * 30) return `il y a ${Math.floor(diff / (86400 * 7))} sem.`;
    if (diff < 86400 * 365) {
        return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
    }
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}
