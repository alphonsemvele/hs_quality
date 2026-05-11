import { cn } from '@/lib/utils';

export type WeakSignalType = 'burnout_risk' | 'autonomy_loss' | 'rps_cluster' | 'engagement_drop' | 'other';

interface Props {
    type: WeakSignalType;
    team?: string | null;
    score?: number | null;
    detectedAt: string;
    acknowledged?: boolean;
    onAcknowledge?: () => void;
    onOpen?: () => void;
}

const TYPE_META: Record<
    WeakSignalType,
    { label: string; tone: 'danger' | 'warning' | 'brand'; emoji: string; description: string }
> = {
    burnout_risk: {
        label: 'Risque de burnout',
        tone: 'danger',
        emoji: '🔥',
        description: 'Plusieurs réponses indiquent une fatigue inhabituelle et une perte de sens.',
    },
    autonomy_loss: {
        label: "Perte d'autonomie perçue",
        tone: 'warning',
        emoji: '⚠️',
        description: 'Les répondants signalent un sentiment de contrôle réduit sur leur travail.',
    },
    rps_cluster: {
        label: 'Cluster RPS détecté',
        tone: 'danger',
        emoji: '📍',
        description: 'Concentration de réponses négatives sur une équipe ou un horaire.',
    },
    engagement_drop: {
        label: "Baisse d'engagement",
        tone: 'warning',
        emoji: '📉',
        description: 'Diminution significative des indicateurs d\'engagement sur la période.',
    },
    other: {
        label: 'Signal faible',
        tone: 'brand',
        emoji: '🔔',
        description: 'Le détecteur a identifié un pattern qui mérite une attention.',
    },
};

const TONE_CLASSES = {
    danger: {
        ring: 'ring-danger-200 dark:ring-danger-800/40',
        bg: 'bg-danger-50/40 dark:bg-danger-900/15',
        accent: 'text-danger-700 dark:text-danger-300',
        chip: 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-300',
    },
    warning: {
        ring: 'ring-warning-200 dark:ring-warning-800/40',
        bg: 'bg-warning-50/40 dark:bg-warning-900/15',
        accent: 'text-warning-700 dark:text-warning-300',
        chip: 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-300',
    },
    brand: {
        ring: 'ring-brand-200 dark:ring-brand-800/40',
        bg: 'bg-brand-50/40 dark:bg-brand-900/15',
        accent: 'text-brand-700 dark:text-brand-300',
        chip: 'bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300',
    },
};

export function WeakSignalCard({ type, team, score, detectedAt, acknowledged, onAcknowledge, onOpen }: Props) {
    const meta = TYPE_META[type];
    const t = TONE_CLASSES[meta.tone];

    return (
        <article
            className={cn(
                'group relative rounded-2xl border border-ink-100 p-4 ring-1 transition-all duration-200 dark:border-ink-700/60',
                t.ring,
                t.bg,
                acknowledged && 'opacity-60',
            )}
        >
            <div className="flex items-start gap-3">
                <span aria-hidden className="flex size-9 shrink-0 items-center justify-center rounded-xl bg-white text-xl shadow-sm dark:bg-ink-800">
                    {meta.emoji}
                </span>
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <h3 className={cn('text-sm font-semibold', t.accent)}>{meta.label}</h3>
                        {acknowledged && (
                            <span className="inline-flex items-center gap-1 rounded-full bg-ink-100 px-2 py-0.5 text-[10px] font-medium text-ink-600 dark:bg-ink-700 dark:text-ink-300">
                                ✓ Pris en compte
                            </span>
                        )}
                    </div>
                    <p className="mt-1 text-xs leading-relaxed text-ink-600 dark:text-ink-300">{meta.description}</p>

                    <div className="mt-3 flex flex-wrap items-center gap-2 text-[11px]">
                        {team && (
                            <span className="inline-flex items-center gap-1 rounded-md bg-white px-2 py-0.5 font-medium text-ink-700 dark:bg-ink-800 dark:text-ink-200">
                                <DotIcon /> {team}
                            </span>
                        )}
                        {score !== null && score !== undefined && (
                            <span className={cn('rounded-md px-2 py-0.5 font-mono font-semibold tabular-nums', t.chip)}>
                                Score : {score.toFixed(1)}
                            </span>
                        )}
                        <span className="font-mono text-ink-500 dark:text-ink-400">{detectedAt}</span>
                    </div>
                </div>
            </div>

            {(onAcknowledge || onOpen) && !acknowledged && (
                <div className="mt-3 flex gap-2 border-t border-ink-100/60 pt-3 dark:border-ink-700/40">
                    {onOpen && (
                        <button
                            type="button"
                            onClick={onOpen}
                            className="rounded-lg px-3 py-1.5 text-xs font-medium text-ink-700 hover:bg-white dark:text-ink-200 dark:hover:bg-ink-800"
                        >
                            Voir le détail
                        </button>
                    )}
                    {onAcknowledge && (
                        <button
                            type="button"
                            onClick={onAcknowledge}
                            className={cn(
                                'rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
                                'bg-white text-ink-900 hover:bg-ink-50 dark:bg-ink-800 dark:text-white dark:hover:bg-ink-700',
                            )}
                        >
                            Prendre en compte
                        </button>
                    )}
                </div>
            )}
        </article>
    );
}

function DotIcon() {
    return <span aria-hidden className="size-1.5 rounded-full bg-current" />;
}
