import { Badge, HoverCard } from '@/components/ui';
import { Link } from '@inertiajs/react';

interface Props {
    title: string;
    referentiel?: string | null;
    statusLabel?: string | null;
    statusTone?: 'sage' | 'warning' | 'brand' | 'neutral';
    runDate?: string | null;
    progress?: number | null;
    href?: string;
}

function formatDate(iso: string): string {
    try {
        return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    } catch {
        return iso;
    }
}

export function AuditHoverCard({ title, referentiel, statusLabel, statusTone = 'brand', runDate, progress, href }: Props) {
    const clamped = progress !== null && progress !== undefined ? Math.max(0, Math.min(100, progress)) : null;

    return (
        <HoverCard
            trigger={
                <span className="cursor-default font-medium text-ink-900 underline decoration-dotted decoration-brand-300 underline-offset-4 hover:decoration-brand-500 dark:text-white dark:decoration-brand-700">
                    {title}
                </span>
            }
            ariaLabel={`Aperçu de l'audit ${title}`}
        >
            <div className="space-y-2.5">
                <div className="flex items-start gap-3">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-white">
                        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
                            <path d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">{title}</p>
                        {referentiel && (
                            <p className="text-[11px] text-ink-500 dark:text-ink-400">Référentiel : {referentiel}</p>
                        )}
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-1.5">
                    {statusLabel && (
                        <Badge tone={statusTone} size="xs" dot>
                            {statusLabel}
                        </Badge>
                    )}
                    {runDate && (
                        <span className="font-mono text-[10px] text-ink-500 dark:text-ink-400">{formatDate(runDate)}</span>
                    )}
                </div>

                {clamped !== null && (
                    <div>
                        <div className="flex items-center justify-between text-[11px] text-ink-500 dark:text-ink-400">
                            <span>Avancement</span>
                            <span className="font-mono">{clamped}%</span>
                        </div>
                        <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                            <div
                                className="h-full rounded-full bg-brand-500 transition-[width] duration-300 ease-out dark:bg-brand-400"
                                style={{ width: `${clamped}%` }}
                            />
                        </div>
                    </div>
                )}

                {href && (
                    <Link
                        href={href}
                        className="block rounded-md border border-ink-200 px-2 py-1.5 text-center text-[11px] font-medium text-brand-700 hover:border-brand-300 hover:bg-brand-50 dark:border-ink-700 dark:text-brand-300 dark:hover:border-brand-600 dark:hover:bg-brand-900/20"
                    >
                        Ouvrir l'audit →
                    </Link>
                )}
            </div>
        </HoverCard>
    );
}
