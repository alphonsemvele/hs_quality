import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Action {
    id: number;
    description: string;
    responsable: string | null;
    echeance: string | null;
    statut: string;
    realise_at: string | null;
}

interface Plan {
    id: string;
    titre: string;
    source: string;
    source_label: string;
    constat: string | null;
    statut: string;
    statut_label: string;
    responsable: string | null;
    echeance: string | null;
    progression: number;
    actions: Action[];
    created_at: string | null;
}

interface Props {
    plan: Plan | null;
}

export default function PlanAmeliorationShow({ plan }: Props) {
    if (!plan) {
        return (
            <DashboardLayout title="Plan introuvable" subtitle="">
                <Card>
                    <EmptyState
                        title="Plan introuvable"
                        description="Ce plan d'amélioration n'existe pas ou n'est pas encore disponible."
                        action={<Link href="/plans-amelioration"><Button variant="secondary">Retour</Button></Link>}
                    />
                </Card>
            </DashboardLayout>
        );
    }

    return (
        <DashboardLayout title={plan.titre} subtitle="">
            <PageHeader
                title={plan.titre}
                subtitle={`${plan.source_label} · Échéance : ${plan.echeance ?? 'Non définie'}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: "Plans d'amélioration", href: '/plans-amelioration' },
                    { label: plan.titre },
                ]}
                actions={<Badge tone="brand" size="sm">{plan.statut_label}</Badge>}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Constat / Écart" />
                    <CardBody>
                        {plan.constat ? (
                            <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{plan.constat}</p>
                        ) : (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">Aucun constat renseigné.</p>
                        )}
                        <div className="mt-4">
                            <p className="mb-1 text-xs text-ink-500 dark:text-ink-400">Progression globale</p>
                            <div className="h-2 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                                <div className="h-full rounded-full bg-sage-500 transition-all duration-700 dark:bg-sage-400" style={{ width: `${plan.progression}%` }} />
                            </div>
                            <p className="mt-1 text-right text-xs font-medium text-ink-600 dark:text-ink-400">{plan.progression} %</p>
                        </div>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Synthèse" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row label="Source" value={plan.source_label} />
                            <Row label="Responsable" value={plan.responsable ?? '—'} />
                            <Row label="Échéance" value={plan.echeance ?? '—'} />
                            <Row label="Actions" value={`${plan.actions.length}`} />
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader title="Actions correctives" subtitle={`${plan.actions.length} action(s)`} />
                    <CardBody>
                        {plan.actions.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {plan.actions.map((a) => (
                                    <li key={a.id} className="py-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium text-ink-900 dark:text-white">{a.description}</p>
                                                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                                    {a.responsable && <>Resp. : <span className="text-ink-700 dark:text-ink-300">{a.responsable}</span></>}
                                                    {a.echeance && <> · Échéance : <span className="font-mono">{a.echeance}</span></>}
                                                </p>
                                            </div>
                                            <Badge tone={a.realise_at ? 'sage' : 'warning'} size="sm">
                                                {a.realise_at ? 'Réalisée' : a.statut || 'En cours'}
                                            </Badge>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucune action définie" description="Ajoutez des actions correctives pour traiter l'écart identifié." />
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</dt>
            <dd className="text-sm font-medium text-ink-900 dark:text-white">{value}</dd>
        </div>
    );
}
