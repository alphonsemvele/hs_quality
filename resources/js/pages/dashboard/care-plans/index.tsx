import { Button, Card, CardBody, CarePlanStatusBadge, EmptyState, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
}

interface Plan {
    id: string;
    title: string;
    status: 'draft' | 'active' | 'archived';
    status_label: string;
    is_active: boolean;
    start_date: string | null;
    end_date: string | null;
    tasks_count?: number;
}

function unwrap<T>(value: { data: T } | T): T {
    if (value && typeof value === 'object' && 'data' in (value as object)) {
        return (value as { data: T }).data;
    }
    return value as T;
}

interface Props {
    beneficiary: { data: Beneficiary } | Beneficiary;
    plans: { data: Plan[] };
}

export default function CarePlansIndex({ beneficiary, plans }: Props) {
    const b = unwrap<Beneficiary>(beneficiary);
    const list = plans?.data ?? [];
    const canManage = useCan('beneficiaries.update');

    return (
        <DashboardLayout title={`Plans · ${b.full_name}`} subtitle="">
            <PageHeader
                title="Plans d'accompagnement"
                subtitle={`${b.full_name} · ${list.length} plan(s)`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: b.full_name, href: `/beneficiaries/${b.id}` },
                    { label: "Plans d'accompagnement" },
                ]}
                actions={
                    canManage ? (
                        <Link href={`/beneficiaries/${b.id}/care-plans/create`}>
                            <Button leadingIcon={<PlusIcon />}>Nouveau plan</Button>
                        </Link>
                    ) : null
                }
            />

            {list.length > 0 ? (
                <ul className="space-y-3">
                    {list.map((plan) => (
                        <Link key={plan.id} href={`/care-plans/${plan.id}`}>
                            <Card className="cursor-pointer transition-shadow hover:shadow-md">
                                <CardBody>
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-base font-semibold text-ink-900 dark:text-white">{plan.title}</h3>
                                                <CarePlanStatusBadge statut={plan.status} />
                                                {plan.tasks_count !== undefined && (
                                                    <span className="text-xs text-ink-500 dark:text-ink-400">{plan.tasks_count} tâche(s)</span>
                                                )}
                                            </div>
                                            <p className="mt-1 font-mono text-xs text-ink-500 dark:text-ink-400">
                                                {plan.start_date ?? '?'} → {plan.end_date ?? 'sans terme'}
                                            </p>
                                        </div>
                                        <span className="self-center text-sm font-medium text-brand-600 dark:text-brand-400">Voir →</span>
                                    </div>
                                </CardBody>
                            </Card>
                        </Link>
                    ))}
                </ul>
            ) : (
                <Card>
                    <EmptyState
                        title="Aucun plan d'accompagnement"
                        description={`Créez le premier plan pour ${b.full_name}.`}
                        action={
                            canManage ? (
                                <Link href={`/beneficiaries/${b.id}/care-plans/create`}>
                                    <Button>Nouveau plan</Button>
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
