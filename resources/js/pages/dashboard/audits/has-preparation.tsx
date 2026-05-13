import { Badge, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Run {
    id: string;
    title: string;
    finalised_at: string | null;
}

interface AnalysisItem {
    item_id: string;
    title: string;
    position: number;
    score: number;
    max_points: number;
    conformity_pct: number;
    status: 'conforme' | 'a_ameliorer' | 'non_conforme' | string;
    gap: number;
    response_id: string | null;
    pac_action_ids: string[];
}

interface Analysis {
    run_id?: string;
    run_title?: string;
    overall_readiness_pct?: number;
    conformity_threshold_pct?: number;
    items?: AnalysisItem[];
    summary?: { conforme: number; a_ameliorer: number; non_conforme: number; total: number };
    error?: string;
}

interface Props {
    runs: Run[];
    selected_run_id: string | null;
    analysis: Analysis | null;
}

const STATUS_META: Record<string, { label: string; tone: 'sage' | 'warning' | 'danger'; priority: number }> = {
    conforme: { label: 'Conforme', tone: 'sage', priority: 3 },
    a_ameliorer: { label: 'À améliorer', tone: 'warning', priority: 2 },
    non_conforme: { label: 'Non conforme', tone: 'danger', priority: 1 },
};

export default function HasPreparation({ runs = [], selected_run_id = null, analysis = null }: Partial<Props>) {
    const selectRun = (id: string) => router.get('/audits/has-preparation', { run_id: id }, { preserveScroll: true, preserveState: true });
    const summary = analysis?.summary;
    const readiness = analysis?.overall_readiness_pct ?? 0;
    const threshold = analysis?.conformity_threshold_pct ?? 70;

    return (
        <DashboardLayout title="Guide de préparation HAS" subtitle="Analyse des écarts et priorisation">
            <PageHeader
                title="Préparation visite HAS"
                subtitle="Analysez les écarts d'un audit HAS finalisé pour prioriser vos plans d'amélioration"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: 'Préparation HAS' },
                ]}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-4">
                {/* Run selector sidebar */}
                <Card className="lg:col-span-1 lg:sticky lg:top-20 lg:self-start">
                    <CardHeader title="Audits HAS" subtitle={`${runs.length} finalisé${runs.length > 1 ? 's' : ''}`} />
                    <CardBody className="px-2 py-2">
                        {runs.length > 0 ? (
                            <ul className="flex flex-col gap-0.5">
                                {runs.map((r) => {
                                    const active = r.id === selected_run_id;
                                    return (
                                        <li key={r.id}>
                                            <button
                                                type="button"
                                                onClick={() => selectRun(r.id)}
                                                className={cn(
                                                    'w-full rounded-lg px-3 py-2 text-left transition-colors',
                                                    active
                                                        ? 'bg-brand-50 text-brand-900 dark:bg-brand-900/30 dark:text-brand-100'
                                                        : 'text-ink-700 hover:bg-ink-50 dark:text-ink-200 dark:hover:bg-ink-700/40',
                                                )}
                                            >
                                                <p className="truncate text-sm font-medium">{r.title}</p>
                                                {r.finalised_at && (
                                                    <p className="font-mono text-[10px] text-ink-500 dark:text-ink-400">
                                                        Finalisé le {new Date(r.finalised_at).toLocaleDateString('fr-FR')}
                                                    </p>
                                                )}
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <EmptyState title="Aucun audit HAS finalisé" description="Finalisez un audit HAS pour obtenir une analyse." />
                        )}
                    </CardBody>
                </Card>

                {/* Analysis */}
                <div className="lg:col-span-3">
                    {analysis === null && runs.length === 0 && (
                        <Card>
                            <EmptyState
                                icon={<ShieldIcon />}
                                title="Pas encore de préparation possible"
                                description="Le guide nécessite au moins un audit HAS finalisé. Lancez et finalisez un audit avec la grille HAS."
                            />
                        </Card>
                    )}

                    {analysis?.error && (
                        <Card>
                            <CardBody>
                                <p className="text-sm text-danger-700 dark:text-danger-400">{analysis.error}</p>
                            </CardBody>
                        </Card>
                    )}

                    {analysis && !analysis.error && summary && (
                        <>
                            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                                <KpiCard
                                    label="Score de préparation"
                                    value={`${readiness.toFixed(1)} %`}
                                    sub={`Cible HAS : ${threshold} %`}
                                    progress={readiness}
                                    tone={readiness >= threshold ? 'sage' : readiness >= 40 ? 'warning' : 'danger'}
                                />
                                <KpiCard
                                    label="Conformes"
                                    value={summary.conforme}
                                    sub={`${Math.round((summary.conforme / Math.max(summary.total, 1)) * 100)}% des critères`}
                                    tone="sage"
                                />
                                <KpiCard
                                    label="À améliorer"
                                    value={summary.a_ameliorer}
                                    sub={`${Math.round((summary.a_ameliorer / Math.max(summary.total, 1)) * 100)}% des critères`}
                                    tone="warning"
                                />
                                <KpiCard
                                    label="Non conformes"
                                    value={summary.non_conforme}
                                    sub={`${Math.round((summary.non_conforme / Math.max(summary.total, 1)) * 100)}% des critères`}
                                    tone={summary.non_conforme > 0 ? 'danger' : 'sage'}
                                />
                            </div>

                            <Card>
                                <CardHeader
                                    title="Écarts priorisés"
                                    subtitle={`${analysis.items?.length ?? 0} critères — triés par criticité puis par écart décroissant`}
                                />
                                <CardBody className="px-0">
                                    <ol className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                        {analysis.items?.map((it) => {
                                            const meta = STATUS_META[it.status] ?? { label: it.status, tone: 'neutral' as const, priority: 9 };
                                            return (
                                                <li key={it.item_id} className="px-5 py-4">
                                                    <div className="flex flex-wrap items-start gap-3">
                                                        <Badge tone={meta.tone} size="sm" dot>
                                                            {meta.label}
                                                        </Badge>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="text-sm font-medium text-ink-900 dark:text-white">{it.title}</p>
                                                            <div className="mt-1.5 flex flex-wrap items-center gap-3 text-[11px] text-ink-500 dark:text-ink-400">
                                                                <span className="font-mono">
                                                                    {it.score.toFixed(1)} / {it.max_points.toFixed(1)} pts
                                                                </span>
                                                                <span className={cn(
                                                                    'font-mono font-semibold',
                                                                    it.conformity_pct >= threshold
                                                                        ? 'text-sage-700 dark:text-sage-400'
                                                                        : it.conformity_pct >= 40
                                                                            ? 'text-warning-700 dark:text-warning-400'
                                                                            : 'text-danger-700 dark:text-danger-400',
                                                                )}>
                                                                    {it.conformity_pct.toFixed(0)}%
                                                                </span>
                                                                {it.pac_action_ids.length > 0 && (
                                                                    <Badge tone="brand" size="xs">
                                                                        {it.pac_action_ids.length} action{it.pac_action_ids.length > 1 ? 's' : ''} PAC déjà en cours
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                            <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                                                                <div
                                                                    className={cn(
                                                                        'h-full rounded-full transition-all duration-500',
                                                                        it.conformity_pct >= threshold
                                                                            ? 'bg-sage-500'
                                                                            : it.conformity_pct >= 40
                                                                                ? 'bg-warning-500'
                                                                                : 'bg-danger-500',
                                                                    )}
                                                                    style={{ width: `${Math.max(it.conformity_pct, 3)}%` }}
                                                                />
                                                            </div>
                                                        </div>
                                                        {it.gap > 0 && (
                                                            <span className="shrink-0 rounded-md bg-danger-50 px-2 py-1 font-mono text-xs font-semibold text-danger-700 dark:bg-danger-900/30 dark:text-danger-300">
                                                                −{it.gap.toFixed(1)} pt{it.gap > 1 ? 's' : ''}
                                                            </span>
                                                        )}
                                                    </div>
                                                </li>
                                            );
                                        })}
                                    </ol>
                                </CardBody>
                            </Card>

                            <div className="mt-5 rounded-xl border border-brand-200 bg-brand-50/40 p-4 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                                <p className="font-semibold text-brand-900 dark:text-brand-200">📋 Plan d'action recommandé</p>
                                <ul className="mt-2 space-y-1 text-brand-900/80 dark:text-brand-200/80">
                                    <li>1. <strong>Non conformes (rouge)</strong> — priorité absolue, créez un PAC par item avec deadlines courtes (≤ 30j)</li>
                                    <li>2. <strong>À améliorer (orange)</strong> — actions correctives sur 60-90 jours, preuves à constituer</li>
                                    <li>3. <strong>Conformes (vert)</strong> — maintenez vos pratiques, documentez les preuves d'effectivité</li>
                                    <li>4. Visez ≥ {threshold}% global avant la visite HAS (norme évaluation interne)</li>
                                </ul>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path d="M9 12l2 2 4-4" />
        </svg>
    );
}
