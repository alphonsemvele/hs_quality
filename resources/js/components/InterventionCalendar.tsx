import { Badge } from '@/components/ui';
import { useMemo } from 'react';

type Statut = 'planifiee' | 'en_cours' | 'realisee' | 'annulee' | 'non_realisee';

export interface CalendarIntervention {
    id: number | string;
    initials: string;
    intervenant: string;
    beneficiaire: string;
    /** Display string in `dd/mm/yyyy HH:mm` format. */
    date_heure_debut: string;
    duree_minutes: number | null;
    statut: Statut;
}

const STATUT_CARD: Record<Statut, { bg: string; border: string; text: string }> = {
    planifiee: {
        bg: 'bg-brand-50',
        border: 'border-brand-300 dark:border-brand-600/50',
        text: 'text-brand-900 dark:text-brand-100',
    },
    en_cours: {
        bg: 'bg-warning-50',
        border: 'border-warning-300 dark:border-warning-600/50',
        text: 'text-warning-900 dark:text-warning-100',
    },
    realisee: {
        bg: 'bg-sage-50',
        border: 'border-sage-300 dark:border-sage-600/50',
        text: 'text-sage-900 dark:text-sage-100',
    },
    annulee: {
        bg: 'bg-danger-50',
        border: 'border-danger-300 dark:border-danger-600/50',
        text: 'text-danger-900 dark:text-danger-100',
    },
    non_realisee: {
        bg: 'bg-ink-100 dark:bg-ink-800',
        border: 'border-ink-300 dark:border-ink-600',
        text: 'text-ink-700 dark:text-ink-200',
    },
};

const STATUT_LABEL: Record<Statut, string> = {
    planifiee: 'Planifiée',
    en_cours: 'En cours',
    realisee: 'Réalisée',
    annulee: 'Annulée',
    non_realisee: 'Non réalisée',
};

const DAY_NAMES = ['lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.', 'dim.'];

/**
 * Parse a `dd/mm/yyyy HH:mm` display string into a Date.
 * Returns null when the format is unexpected so the caller can skip the row.
 */
function parseFrDateTime(raw: string): Date | null {
    const match = /^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})$/.exec(raw.trim());
    if (!match) return null;
    const [, d, m, y, h, min] = match;
    const date = new Date(Number(y), Number(m) - 1, Number(d), Number(h), Number(min));
    return Number.isNaN(date.getTime()) ? null : date;
}

function isoDay(date: Date): string {
    return date.toISOString().slice(0, 10);
}

function frenchWeekday(date: Date): string {
    // JS: 0 = Sunday … 6 = Saturday. Shift so Monday = 0.
    const idx = (date.getDay() + 6) % 7;
    return DAY_NAMES[idx];
}

function shortLabel(date: Date): string {
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
}

interface InterventionCalendarProps {
    interventions: CalendarIntervention[];
    onSelect?: (intervention: CalendarIntervention) => void;
}

/**
 * Week-view calendar of interventions.
 *
 * Approach: rather than asking the user to pick a week, the calendar
 * surfaces whatever days are present in the current `interventions`
 * list (already filtered server-side by `?from=` / `?to=` query params).
 * The columns are the distinct days, ordered chronologically; rows are
 * hourly slots from the earliest planned hour to one hour after the
 * latest. Interventions render as colour-coded blocks in their slot.
 *
 * Click a block → calls `onSelect` (used by the parent to open the
 * preview sheet).
 */
export function InterventionCalendar({ interventions, onSelect }: InterventionCalendarProps) {
    const parsed = useMemo(() => {
        return interventions
            .map((i) => {
                const date = parseFrDateTime(i.date_heure_debut);
                return date ? { intervention: i, date } : null;
            })
            .filter((row): row is { intervention: CalendarIntervention; date: Date } => row !== null);
    }, [interventions]);

    if (parsed.length === 0) {
        return (
            <div className="rounded-2xl border border-ink-100 bg-ink-50/40 px-6 py-12 text-center dark:border-ink-700/60 dark:bg-ink-800/40">
                <p className="text-sm font-medium text-ink-700 dark:text-ink-200">
                    Aucune intervention datée à afficher.
                </p>
                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                    Ajustez le filtre de période ou repassez sur la vue liste.
                </p>
            </div>
        );
    }

    // Group by ISO day.
    const dayBuckets = new Map<string, { date: Date; rows: { intervention: CalendarIntervention; date: Date }[] }>();
    let minHour = 23;
    let maxHour = 0;
    for (const row of parsed) {
        const key = isoDay(row.date);
        const bucket = dayBuckets.get(key) ?? { date: row.date, rows: [] };
        bucket.rows.push(row);
        dayBuckets.set(key, bucket);
        const hour = row.date.getHours();
        if (hour < minHour) minHour = hour;
        if (hour > maxHour) maxHour = hour;
    }

    const days = Array.from(dayBuckets.values()).sort((a, b) => a.date.getTime() - b.date.getTime());
    const startHour = Math.max(6, minHour);
    const endHour = Math.min(22, maxHour + 1);
    const hours: number[] = [];
    for (let h = startHour; h <= endHour; h++) {
        hours.push(h);
    }

    return (
        <div className="overflow-x-auto">
            <div className="min-w-[640px]">
                {/* Header row */}
                <div
                    className="grid border-b border-ink-100 dark:border-ink-700/60"
                    style={{ gridTemplateColumns: `4rem repeat(${days.length}, minmax(8rem, 1fr))` }}
                >
                    <div className="px-2 py-3 text-[10px] font-semibold uppercase tracking-wider text-ink-400 dark:text-ink-500">
                        Heure
                    </div>
                    {days.map((d) => (
                        <div key={isoDay(d.date)} className="border-l border-ink-100 px-3 py-2 dark:border-ink-700/60">
                            <p className="text-[10px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                {frenchWeekday(d.date)}
                            </p>
                            <p className="font-mono text-sm font-semibold text-ink-900 dark:text-white">{shortLabel(d.date)}</p>
                            <p className="text-[10px] text-ink-400 dark:text-ink-500">
                                {d.rows.length} interv.
                            </p>
                        </div>
                    ))}
                </div>

                {/* Body — one row per hour */}
                {hours.map((hour) => (
                    <div
                        key={hour}
                        className="grid border-b border-ink-100/70 dark:border-ink-700/40"
                        style={{ gridTemplateColumns: `4rem repeat(${days.length}, minmax(8rem, 1fr))` }}
                    >
                        <div className="px-2 py-3 text-right font-mono text-[11px] text-ink-400 dark:text-ink-500">
                            {String(hour).padStart(2, '0')}:00
                        </div>
                        {days.map((d) => {
                            const inSlot = d.rows.filter((r) => r.date.getHours() === hour);
                            return (
                                <div
                                    key={isoDay(d.date) + '-' + hour}
                                    className="min-h-[3.5rem] space-y-1 border-l border-ink-100 p-1 dark:border-ink-700/60"
                                >
                                    {inSlot.map((row) => {
                                        const tone = STATUT_CARD[row.intervention.statut];
                                        return (
                                            <button
                                                type="button"
                                                key={row.intervention.id}
                                                onClick={() => onSelect?.(row.intervention)}
                                                className={
                                                    'block w-full rounded-lg border px-2 py-1.5 text-left text-[11px] leading-tight transition-shadow hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 ' +
                                                    tone.bg +
                                                    ' ' +
                                                    tone.border +
                                                    ' ' +
                                                    tone.text +
                                                    ' dark:bg-opacity-30'
                                                }
                                            >
                                                <p className="truncate font-semibold">{row.intervention.beneficiaire}</p>
                                                <p className="truncate opacity-80">
                                                    {row.date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}
                                                    {row.intervention.duree_minutes ? ` · ${row.intervention.duree_minutes} min` : ''}
                                                </p>
                                                <p className="truncate text-[10px] opacity-70">{row.intervention.intervenant}</p>
                                            </button>
                                        );
                                    })}
                                </div>
                            );
                        })}
                    </div>
                ))}
            </div>

            {/* Legend */}
            <div className="mt-3 flex flex-wrap items-center gap-2 px-3 text-[11px]">
                <span className="font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">Statuts</span>
                {(Object.entries(STATUT_LABEL) as [Statut, string][]).map(([key, label]) => (
                    <Badge key={key} tone={statutToTone(key)} size="xs" dot>
                        {label}
                    </Badge>
                ))}
            </div>
        </div>
    );
}

function statutToTone(statut: Statut): 'brand' | 'warning' | 'sage' | 'danger' | 'neutral' {
    switch (statut) {
        case 'planifiee':
            return 'brand';
        case 'en_cours':
            return 'warning';
        case 'realisee':
            return 'sage';
        case 'annulee':
            return 'danger';
        default:
            return 'neutral';
    }
}
