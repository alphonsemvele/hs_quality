import { Badge, Button, Card, CardBody, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface PlanSummary {
    id: string;
    titre: string;
    source: string;
    source_label: string;
    statut: 'ouvert' | 'en_cours' | 'termine' | 'annule';
    statut_label: string;
    echeance: string | null;
    responsable: string | null;
    nb_actions: number;
    nb_actions_realisees: number;
    progression: number;
}

interface Stats {
    total: number;
    en_cours: number;
    termines: number;
    taux_completion: number | null;
}

interface Props {
    plans: PlanSummary[];
    stats: Stats;
}

const STATUT_TONE: Record<string, 'brand' | 'warning' | 'sage' | 'neutral' | 'danger'> = {
    ouvert: 'brand',
    en_cours: 'warning',
    termine: 'sage',
    annule: 'neutral',
};

export default function PlansAmeliorationIndex({ plans = [], stats = { total: 0, en_cours: 0, termines: 0, taux_completion: null } }: Partial<Props>) {
    const canManage = useCan('plans_amelioration.manage');
    return (
        <DashboardLayout title="Plans d'amélioration" subtitle="PAC — Suivi des actions correctives">
            <PageHeader
                title="Plans d'amélioration"
                subtitle="Actions correctives issues des audits, incidents et alertes QVCT"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: "Plans d'amélioration" }]}
                actions={
                    canManage ? (
                        <Link href="/plans-amelioration/create">
                            <Button leadingIcon={<PlusIcon />}>Nouveau plan</Button>
                        </Link>
                    ) : null
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Total" value={stats.total} tone="brand" />
                <KpiCard label="En cours" value={stats.en_cours} tone="warning" />
                <KpiCard label="Terminés" value={stats.termines} tone="sage" />
                <KpiCard
                    label="Taux de complétion"
                    value={stats.taux_completion !== null ? `${stats.taux_completion} %` : '—'}
                    tone="sage"
                    progress={stats.taux_completion ?? 0}
                />
            </div>

            {plans.length > 0 ? (
                <ul className="space-y-3">
                    {plans.map((plan) => (
                        <Link key={plan.id} href={`/plans-amelioration/${plan.id}`}>
                            <Card className="cursor-pointer transition-shadow hover:shadow-md">
                                <CardBody>
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{plan.titre}</h3>
                                                <Badge tone={STATUT_TONE[plan.statut] ?? 'neutral'} size="sm" dot>{plan.statut_label}</Badge>
                                                <Badge tone="neutral" size="xs">{plan.source_label}</Badge>
                                            </div>
                                            <div className="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-ink-500 dark:text-ink-400">
                                                {plan.responsable && <span>Resp. : {plan.responsable}</span>}
                                                {plan.echeance && <span className="font-mono">Échéance : {plan.echeance}</span>}
                                                <span>{plan.nb_actions_realisees}/{plan.nb_actions} action(s)</span>
                                            </div>
                                            <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                                                <div
                                                    className="h-full rounded-full bg-sage-500 transition-all duration-700 dark:bg-sage-400"
                                                    style={{ width: `${plan.progression}%` }}
                                                />
                                            </div>
                                        </div>
                                        <span className="shrink-0 self-center text-sm font-medium text-brand-600 dark:text-brand-400">Voir →</span>
                                    </div>
                                </CardBody>
                            </Card>
                        </Link>
                    ))}
                </ul>
            ) : (
                <Card>
                    <EmptyState
                        icon={<CheckListIcon />}
                        title="Aucun plan d'amélioration"
                        description="Les plans d'amélioration sont générés automatiquement depuis les écarts d'audit et les incidents."
                        action={
                            canManage ? (
                                <Link href="/plans-amelioration/create">
                                    <Button>Créer un plan</Button>
                                </Link>
                            ) : undefined
                        }
                    />
                </Card>
            )}
        </DashboardLayout>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}

function CheckListIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 11l3 3L22 4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
        </svg>
    );
}
