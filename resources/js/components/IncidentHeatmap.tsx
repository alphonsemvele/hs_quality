import { useMemo, useState } from 'react';

type Gravite = 'mineur' | 'significatif' | 'grave' | 'critique';

export interface HeatmapIncident {
    id: number | string;
    /** Display string in dd/mm/yyyy HH:mm. */
    date_heure: string;
    gravite: Gravite;
}

type GraviteFilter = 'all' | Gravite;

function parseFrDate(raw: string): Date | null {
    const match = /^(\d{2})\/(\d{2})\/(\d{4})/.exec(raw.trim());
    if (!match) return null;
    const [, d, m, y] = match;
    return new Date(Number(y), Number(m) - 1, Number(d));
}

function ymd(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function daysOfRolling12Weeks(): Date[] {
    // 12 weeks starting from the Monday 11 weeks ago.
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dayOfWeek = (today.getDay() + 6) % 7; // 0 = Monday
    const start = new Date(today);
    start.setDate(today.getDate() - dayOfWeek - 7 * 11);

    const out: Date[] = [];
    for (let i = 0; i < 12 * 7; i++) {
        const d = new Date(start);
        d.setDate(start.getDate() + i);
        out.push(d);
    }
    return out;
}

const GRAVITE_RANK: Record<Gravite, number> = {
    mineur: 1,
    significatif: 2,
    grave: 3,
    critique: 4,
};

const GRAVITE_LABEL: Record<Gravite, string> = {
    mineur: 'Mineur',
    significatif: 'Significatif',
    grave: 'Grave',
    critique: 'Critique',
};

interface IncidentHeatmapProps {
    incidents: HeatmapIncident[];
}

/**
 * 12-week heatmap of declared incidents.
 *
 * Each cell is one day; colour scales from neutral (no incident) to deep
 * red (3+ incidents OR at least one critique). Hover reveals the day,
 * count and dominant gravity.
 *
 * The user can filter the source list by gravité chip — the cell colour
 * uses only the matching subset, the underlying totals are unchanged.
 */
export function IncidentHeatmap({ incidents }: IncidentHeatmapProps) {
    const [filter, setFilter] = useState<GraviteFilter>('all');

    const buckets = useMemo(() => {
        const map = new Map<string, { count: number; maxRank: number }>();
        for (const inc of incidents) {
            if (filter !== 'all' && inc.gravite !== filter) continue;
            const date = parseFrDate(inc.date_heure);
            if (!date) continue;
            const key = ymd(date);
            const existing = map.get(key);
            const rank = GRAVITE_RANK[inc.gravite];
            if (existing) {
                existing.count++;
                if (rank > existing.maxRank) existing.maxRank = rank;
            } else {
                map.set(key, { count: 1, maxRank: rank });
            }
        }
        return map;
    }, [incidents, filter]);

    const days = useMemo(daysOfRolling12Weeks, []);

    // Group days into 12 weekly columns of 7 days each.
    const weeks: Date[][] = [];
    for (let i = 0; i < 12; i++) {
        weeks.push(days.slice(i * 7, (i + 1) * 7));
    }

    const today = ymd(new Date());

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center gap-2">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                    Filtrer
                </span>
                {(['all', 'mineur', 'significatif', 'grave', 'critique'] as const).map((value) => (
                    <button
                        key={value}
                        type="button"
                        onClick={() => setFilter(value)}
                        aria-pressed={filter === value}
                        className={
                            filter === value
                                ? 'inline-flex items-center rounded-full bg-ink-900 px-3 py-1 text-xs font-semibold text-white dark:bg-white dark:text-ink-900'
                                : 'inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1 text-xs font-medium text-ink-700 transition-colors hover:border-ink-400 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200'
                        }
                    >
                        {value === 'all' ? 'Tous' : GRAVITE_LABEL[value]}
                    </button>
                ))}
            </div>

            <div className="overflow-x-auto">
                <div className="inline-grid gap-1" style={{ gridTemplateColumns: 'auto repeat(12, minmax(0, 1fr))' }}>
                    {/* Day-of-week labels */}
                    <div />
                    {weeks.map((week) => (
                        <div key={ymd(week[0])} className="text-center text-[10px] font-mono text-ink-400 dark:text-ink-500">
                            {week[0].toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })}
                        </div>
                    ))}

                    {['L', 'M', 'M', 'J', 'V', 'S', 'D'].map((label, dayIndex) => (
                        <div key={'label-' + dayIndex} className="contents">
                            <span className="pr-2 text-right text-[10px] font-mono text-ink-400 dark:text-ink-500">
                                {label}
                            </span>
                            {weeks.map((week, weekIndex) => {
                                const day = week[dayIndex];
                                const key = ymd(day);
                                const bucket = buckets.get(key);
                                return (
                                    <button
                                        key={`${weekIndex}-${dayIndex}`}
                                        type="button"
                                        title={
                                            bucket
                                                ? `${day.toLocaleDateString('fr-FR', { weekday: 'long', day: '2-digit', month: 'long' })} — ${bucket.count} incident(s)`
                                                : day.toLocaleDateString('fr-FR', { weekday: 'long', day: '2-digit', month: 'long' })
                                        }
                                        aria-label={`Jour ${key} — ${bucket?.count ?? 0} incident(s)`}
                                        className={
                                            'aspect-square w-full rounded-sm transition-colors hover:ring-2 hover:ring-ink-400 ' +
                                            cellColor(bucket) +
                                            (key === today ? ' ring-2 ring-brand-500 dark:ring-brand-400' : '')
                                        }
                                    />
                                );
                            })}
                        </div>
                    ))}
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-3 text-[11px] text-ink-500 dark:text-ink-400">
                <span className="font-semibold uppercase tracking-wider">Densité</span>
                <span className="flex items-center gap-1">
                    <span className="size-3 rounded-sm bg-ink-100 dark:bg-ink-800" /> 0
                </span>
                <span className="flex items-center gap-1">
                    <span className="size-3 rounded-sm bg-warning-200 dark:bg-warning-900/50" /> 1
                </span>
                <span className="flex items-center gap-1">
                    <span className="size-3 rounded-sm bg-warning-400 dark:bg-warning-700/70" /> 2
                </span>
                <span className="flex items-center gap-1">
                    <span className="size-3 rounded-sm bg-danger-400 dark:bg-danger-700" /> 3+
                </span>
                <span className="flex items-center gap-1">
                    <span className="size-3 rounded-sm bg-danger-700 dark:bg-danger-500" /> critique
                </span>
            </div>
        </div>
    );
}

function cellColor(bucket: { count: number; maxRank: number } | undefined): string {
    if (!bucket || bucket.count === 0) {
        return 'bg-ink-100 dark:bg-ink-800';
    }
    if (bucket.maxRank >= 4) {
        return 'bg-danger-700 dark:bg-danger-500';
    }
    if (bucket.count >= 3) {
        return 'bg-danger-400 dark:bg-danger-700';
    }
    if (bucket.count === 2) {
        return 'bg-warning-400 dark:bg-warning-700/70';
    }
    return 'bg-warning-200 dark:bg-warning-900/50';
}
