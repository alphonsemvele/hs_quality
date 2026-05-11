import { cn } from '@/lib/utils';
import { useState } from 'react';

interface Dimension {
    key: string;
    label: string;
    description: string;
}

interface Cell {
    team: string;
    dimension: string;
    score: number;
}

interface Props {
    teams: string[];
    dimensions: Dimension[];
    cells: Cell[];
    ariaLabel?: string;
}

export function RpsHeatmap({ teams, dimensions, cells, ariaLabel }: Props) {
    const [hover, setHover] = useState<{ team: string; dimension: string } | null>(null);

    const lookup = (team: string, dim: string): number | null => {
        const c = cells.find((x) => x.team === team && x.dimension === dim);
        return c ? c.score : null;
    };

    if (teams.length === 0 || dimensions.length === 0) {
        return <div className="py-10 text-center text-sm text-ink-400">Pas de données</div>;
    }

    return (
        <div className="overflow-x-auto" role="img" aria-label={ariaLabel ?? 'Cartographie RPS par équipe et dimension'}>
            <table className="w-full min-w-[640px] border-separate" style={{ borderSpacing: '4px' }}>
                <thead>
                    <tr>
                        <th className="sticky left-0 z-10 bg-white px-2 py-1.5 text-left text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:bg-ink-800 dark:text-ink-400">
                            Équipe
                        </th>
                        {dimensions.map((d) => (
                            <th
                                key={d.key}
                                title={d.description}
                                className="px-2 py-1.5 text-center text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400"
                            >
                                {d.label}
                            </th>
                        ))}
                        <th className="px-2 py-1.5 text-center text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                            Moy.
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {teams.map((team) => {
                        const rowScores = dimensions
                            .map((d) => lookup(team, d.key))
                            .filter((v): v is number => v !== null);
                        const avg = rowScores.length > 0 ? rowScores.reduce((s, v) => s + v, 0) / rowScores.length : null;
                        return (
                            <tr key={team}>
                                <th className="sticky left-0 z-10 max-w-[160px] truncate bg-white px-2 py-1.5 text-left text-xs font-medium text-ink-800 dark:bg-ink-800 dark:text-ink-100">
                                    {team}
                                </th>
                                {dimensions.map((d) => {
                                    const score = lookup(team, d.key);
                                    const isHover = hover?.team === team && hover.dimension === d.key;
                                    return (
                                        <td
                                            key={d.key}
                                            onMouseEnter={() => setHover({ team, dimension: d.key })}
                                            onMouseLeave={() => setHover(null)}
                                            className={cn(
                                                'relative h-12 rounded-lg text-center transition-all duration-150',
                                                score === null
                                                    ? 'bg-ink-50 dark:bg-ink-700/40'
                                                    : heatColor(score),
                                                isHover && 'scale-[1.04] ring-2 ring-brand-400/60',
                                            )}
                                        >
                                            {score !== null && (
                                                <span
                                                    className={cn(
                                                        'font-mono text-sm font-bold tabular-nums',
                                                        heatText(score),
                                                    )}
                                                >
                                                    {score.toFixed(1)}
                                                </span>
                                            )}
                                        </td>
                                    );
                                })}
                                <td className="px-2 text-center">
                                    <span
                                        className={cn(
                                            'inline-flex h-9 min-w-[44px] items-center justify-center rounded-lg font-mono text-sm font-bold tabular-nums',
                                            avg !== null ? heatColor(avg) : 'bg-ink-50 dark:bg-ink-700/40',
                                            avg !== null ? heatText(avg) : 'text-ink-400',
                                        )}
                                    >
                                        {avg !== null ? avg.toFixed(1) : '—'}
                                    </span>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>

            {/* Legend */}
            <div className="mt-4 flex flex-wrap items-center gap-3 text-[11px]">
                <span className="text-ink-500 dark:text-ink-400">Échelle :</span>
                <LegendChip score={3} label="< 5 critique" />
                <LegendChip score={5.5} label="5-6,5 attention" />
                <LegendChip score={7} label="6,5-8 bon" />
                <LegendChip score={8.5} label="> 8 excellent" />
            </div>
        </div>
    );
}

function LegendChip({ score, label }: { score: number; label: string }) {
    return (
        <span className={cn('inline-flex items-center gap-1.5 rounded-md px-2 py-1', heatColor(score))}>
            <span className={cn('font-mono font-semibold', heatText(score))}>{label}</span>
        </span>
    );
}

function heatColor(score: number): string {
    if (score < 5) return 'bg-danger-100 dark:bg-danger-900/40';
    if (score < 6.5) return 'bg-warning-100 dark:bg-warning-900/40';
    if (score < 8) return 'bg-brand-100 dark:bg-brand-900/40';
    return 'bg-sage-100 dark:bg-sage-900/40';
}

function heatText(score: number): string {
    if (score < 5) return 'text-danger-700 dark:text-danger-300';
    if (score < 6.5) return 'text-warning-700 dark:text-warning-300';
    if (score < 8) return 'text-brand-700 dark:text-brand-200';
    return 'text-sage-700 dark:text-sage-200';
}
