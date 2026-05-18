import { useMemo } from 'react';

export interface BurndownAction {
    statut: 'planifiee' | 'en_cours' | 'realisee' | 'annulee';
    realise_at: string | null;
    /** Optional creation date for the action — fallback to today if absent. */
    created_at?: string | null;
}

interface PacBurndownProps {
    actions: BurndownAction[];
    /** Window in days, default 30. */
    days?: number;
}

function ymd(date: Date): string {
    return date.toISOString().slice(0, 10);
}

function parseDate(value: string | null | undefined): Date | null {
    if (!value) return null;
    // Accept ISO 8601 or `dd/mm/yyyy [HH:mm]`.
    const fr = /^(\d{2})\/(\d{2})\/(\d{4})/.exec(value);
    if (fr) {
        return new Date(Number(fr[3]), Number(fr[2]) - 1, Number(fr[1]));
    }
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
}

/**
 * 30-day open/closed cumulative chart for the actions of a single PAC.
 *
 * Read-only and purely client-side: we compute the daily open count and
 * the cumulative closed count from the actions' `realise_at` timestamp.
 * Plotted as two stacked SVG paths (closed below the line, still-open
 * above) so the eye reads "how fast did the plan burn down" at a glance.
 *
 * Note: this is an approximation. We don't have the action's own
 * `created_at` for every shape on every backend version — when absent
 * we treat the action as "always existed" within the window, which
 * means the open line stays flat at the total until each realisation
 * subtracts one. Good enough for a visual signal; not a financial KPI.
 */
export function PacBurndown({ actions, days = 30 }: PacBurndownProps) {
    const series = useMemo(() => {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const start = new Date(today);
        start.setDate(today.getDate() - (days - 1));

        const realisations = actions
            .map((a) => parseDate(a.realise_at))
            .filter((d): d is Date => d !== null)
            .map((d) => {
                d.setHours(0, 0, 0, 0);
                return d;
            });

        // Closed-by-day map.
        const closedByDay = new Map<string, number>();
        for (const date of realisations) {
            const key = ymd(date);
            closedByDay.set(key, (closedByDay.get(key) ?? 0) + 1);
        }

        // Total non-cancelled actions today.
        const totalActive = actions.filter((a) => a.statut !== 'annulee').length;

        // Closed before the window started.
        const closedBefore = realisations.filter((d) => d < start).length;
        let openSoFar = totalActive - closedBefore;
        let closedSoFar = closedBefore;

        const points: { date: Date; open: number; closed: number }[] = [];
        for (let i = 0; i < days; i++) {
            const date = new Date(start);
            date.setDate(start.getDate() + i);
            const todayClosed = closedByDay.get(ymd(date)) ?? 0;
            closedSoFar += todayClosed;
            openSoFar -= todayClosed;
            points.push({ date: new Date(date), open: Math.max(0, openSoFar), closed: closedSoFar });
        }

        return { points, totalActive };
    }, [actions, days]);

    if (actions.length === 0 || series.totalActive === 0) {
        return (
            <div className="rounded-2xl border border-dashed border-ink-200 px-6 py-8 text-center dark:border-ink-700">
                <p className="text-sm font-medium text-ink-700 dark:text-ink-200">Pas encore assez de données pour tracer le burndown</p>
                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">Ajoutez et clôturez des actions pour visualiser leur progression.</p>
            </div>
        );
    }

    const width = 320;
    const height = 100;
    const padX = 4;
    const padY = 4;

    const maxY = Math.max(...series.points.map((p) => p.open + p.closed), 1);
    const xAt = (i: number) =>
        padX + (i / Math.max(series.points.length - 1, 1)) * (width - 2 * padX);
    const yAt = (v: number) => padY + (1 - v / maxY) * (height - 2 * padY);

    const openPath = series.points
        .map((p, i) => `${i === 0 ? 'M' : 'L'}${xAt(i).toFixed(1)},${yAt(p.open).toFixed(1)}`)
        .join(' ');
    const closedPath = series.points
        .map((p, i) => `${i === 0 ? 'M' : 'L'}${xAt(i).toFixed(1)},${yAt(p.closed).toFixed(1)}`)
        .join(' ');

    const first = series.points[0];
    const last = series.points[series.points.length - 1];
    const fmt = (d: Date) => d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });

    return (
        <div className="space-y-3">
            <svg
                viewBox={`0 0 ${width} ${height}`}
                role="img"
                aria-label="Burndown sur 30 jours : actions ouvertes vs clôturées"
                className="block h-24 w-full"
            >
                {/* Open trend */}
                <path d={openPath} fill="none" stroke="currentColor" strokeWidth={1.75} className="text-warning-500 dark:text-warning-400" />
                {/* Closed cumulative */}
                <path d={closedPath} fill="none" stroke="currentColor" strokeWidth={1.75} className="text-sage-500 dark:text-sage-400" />
            </svg>

            <div className="flex flex-wrap items-center justify-between gap-3 text-[11px] text-ink-500 dark:text-ink-400">
                <div className="flex items-center gap-4">
                    <span className="flex items-center gap-1.5">
                        <span className="block h-0.5 w-4 bg-warning-500 dark:bg-warning-400" /> Ouvertes
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="block h-0.5 w-4 bg-sage-500 dark:bg-sage-400" /> Clôturées (cumul)
                    </span>
                </div>
                <div className="font-mono">
                    {fmt(first.date)} → {fmt(last.date)}
                </div>
            </div>

            <div className="grid grid-cols-3 gap-3 border-t border-ink-100 pt-3 dark:border-ink-700/60">
                <Metric label="Actions actives" value={String(series.totalActive)} />
                <Metric label="Clôturées sur 30 j" value={String(last.closed - first.closed + (series.points[0].closed > 0 ? 0 : 0))} />
                <Metric label="Ouvertes ce jour" value={String(last.open)} />
            </div>
        </div>
    );
}

function Metric({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="font-mono text-base font-bold tracking-tight text-ink-900 dark:text-white">{value}</p>
            <p className="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                {label}
            </p>
        </div>
    );
}
