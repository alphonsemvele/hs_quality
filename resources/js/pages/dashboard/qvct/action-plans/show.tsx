import { Badge, Button, Card, CardBody, CardHeader, EmptyState, FormField, Input, PageHeader, Textarea } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Form, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../layout';

interface Item {
    id: string;
    title: string;
    description: string | null;
    responsible: string | null;
    due_date: string | null;
    status: 'pending' | 'in_progress' | 'done' | 'cancelled';
    status_label: string;
    impact_measurement_target: string | null;
    impact_measurement_actual: string | null;
}

interface Plan {
    id: string;
    title: string;
    description: string | null;
    target_quarter: string | null;
    status: 'draft' | 'published' | 'closed';
    status_label: string;
    published_at: string | null;
    closed_at: string | null;
    created_at: string | null;
    created_by: string | null;
    items: Item[];
}

const STATUS_TONE: Record<Plan['status'], 'warning' | 'sage' | 'brand'> = {
    draft: 'warning',
    published: 'sage',
    closed: 'brand',
};

const ITEM_TONE: Record<Item['status'], 'neutral' | 'brand' | 'sage' | 'warning'> = {
    pending: 'neutral',
    in_progress: 'brand',
    done: 'sage',
    cancelled: 'warning',
};

const ITEM_STATUSES: Array<{ value: Item['status']; label: string }> = [
    { value: 'pending', label: 'À démarrer' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'done', label: 'Réalisée' },
    { value: 'cancelled', label: 'Annulée' },
];

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR');
}

export default function QvctActionPlanShow({ plan }: { plan: Plan }) {
    const canManage = useCan('qvct.manage');
    const [showItemForm, setShowItemForm] = useState(false);
    const isDraft = plan.status === 'draft';
    const isClosed = plan.status === 'closed';

    const publish = () => {
        if (!window.confirm('Publier ce plan ? Les actions ne pourront plus être ajoutées ni retirées, seul leur statut et leur impact pourront être mis à jour.')) {
            return;
        }
        router.post(`/qvct/action-plans/${plan.id}/publish`, undefined, { preserveScroll: true });
    };

    const close = () => {
        if (!window.confirm('Clôturer ce plan ? La mesure d\'impact sera figée et plus aucune mise à jour ne sera possible.')) {
            return;
        }
        router.post(`/qvct/action-plans/${plan.id}/close`, undefined, { preserveScroll: true });
    };

    const setItemStatus = (item: Item, status: Item['status']) => {
        router.post(
            `/qvct/action-plan-items/${item.id}/status`,
            { status },
            { preserveScroll: true },
        );
    };

    return (
        <DashboardLayout title={plan.title} subtitle="Plan d'action QVCT">
            <PageHeader
                title={plan.title}
                subtitle={plan.target_quarter ? `Trimestre cible : ${plan.target_quarter}` : 'Plan d\'action QVCT'}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: "Plans d'action", href: '/qvct/action-plans' },
                    { label: plan.title },
                ]}
                actions={
                    <>
                        <Badge tone={STATUS_TONE[plan.status]} size="sm" dot>
                            {plan.status_label}
                        </Badge>
                        {canManage && isDraft && (
                            <Button onClick={publish}>Publier</Button>
                        )}
                        {canManage && plan.status === 'published' && (
                            <Button variant="secondary" onClick={close}>
                                Clôturer
                            </Button>
                        )}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card>
                    <CardHeader title="Synthèse" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row label="Pilote" value={plan.created_by ?? '—'} />
                            <Row label="Créé le" value={formatDate(plan.created_at)} />
                            {plan.published_at && <Row label="Publié le" value={formatDate(plan.published_at)} />}
                            {plan.closed_at && <Row label="Clos le" value={formatDate(plan.closed_at)} />}
                            <Row label="Actions" value={`${plan.items.length}`} />
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader title="Contexte" />
                    <CardBody>
                        {plan.description ? (
                            <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{plan.description}</p>
                        ) : (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">Aucune description renseignée.</p>
                        )}
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Actions du plan"
                        subtitle={`${plan.items.length} action(s)`}
                        action={
                            canManage && isDraft ? (
                                <Button variant="secondary" size="sm" onClick={() => setShowItemForm(!showItemForm)}>
                                    {showItemForm ? 'Annuler' : '+ Ajouter une action'}
                                </Button>
                            ) : undefined
                        }
                    />
                    <CardBody>
                        {showItemForm && canManage && isDraft && (
                            <div className="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                                <Form
                                    action={`/qvct/action-plans/${plan.id}/items`}
                                    method="post"
                                    onSuccess={() => setShowItemForm(false)}
                                    resetOnSuccess
                                >
                                    {({ errors, processing }) => (
                                        <div className="space-y-3">
                                            <FormField label="Titre" htmlFor="item-title" required error={errors.title}>
                                                <Input
                                                    id="item-title"
                                                    name="title"
                                                    required
                                                    maxLength={255}
                                                    placeholder="Ex: Mettre en place un binôme de doublure secteur Nord"
                                                    invalid={!!errors.title}
                                                />
                                            </FormField>
                                            <FormField label="Description" htmlFor="item-desc" error={errors.description}>
                                                <Textarea
                                                    id="item-desc"
                                                    name="description"
                                                    rows={3}
                                                    maxLength={5000}
                                                    invalid={!!errors.description}
                                                />
                                            </FormField>
                                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <FormField label="Échéance (optionnel)" htmlFor="item-due" error={errors.due_date}>
                                                    <Input id="item-due" name="due_date" type="date" invalid={!!errors.due_date} />
                                                </FormField>
                                                <FormField
                                                    label="Cible d'impact (optionnel)"
                                                    htmlFor="item-target"
                                                    error={errors.impact_measurement_target}
                                                >
                                                    <Input
                                                        id="item-target"
                                                        name="impact_measurement_target"
                                                        maxLength={1000}
                                                        placeholder="Ex: +0.5 sur question soutien"
                                                        invalid={!!errors.impact_measurement_target}
                                                    />
                                                </FormField>
                                            </div>
                                            <div className="flex justify-end gap-2">
                                                <Button variant="ghost" size="sm" onClick={() => setShowItemForm(false)}>
                                                    Annuler
                                                </Button>
                                                <Button type="submit" size="sm" loading={processing}>
                                                    Ajouter
                                                </Button>
                                            </div>
                                        </div>
                                    )}
                                </Form>
                            </div>
                        )}

                        {plan.items.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {plan.items.map((item) => (
                                    <li key={item.id} className="py-3">
                                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium text-ink-900 dark:text-white">{item.title}</p>
                                                {item.description && (
                                                    <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">{item.description}</p>
                                                )}
                                                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                                    Responsable : <span className="text-ink-700 dark:text-ink-300">{item.responsible ?? '—'}</span>
                                                    {item.due_date && (
                                                        <>
                                                            {' '}· Échéance : <span className="font-mono">{formatDate(item.due_date)}</span>
                                                        </>
                                                    )}
                                                </p>
                                                {item.impact_measurement_target && (
                                                    <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                                        Cible d'impact : <span className="text-sage-700 dark:text-sage-300">{item.impact_measurement_target}</span>
                                                    </p>
                                                )}
                                            </div>
                                            <div className="flex shrink-0 items-center gap-2">
                                                <Badge tone={ITEM_TONE[item.status]} size="sm">
                                                    {item.status_label}
                                                </Badge>
                                                {canManage && !isClosed && (
                                                    <select
                                                        value={item.status}
                                                        onChange={(e) => setItemStatus(item, e.target.value as Item['status'])}
                                                        className="h-8 rounded-md border border-ink-200 bg-white px-2 text-xs text-ink-700 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                                                    >
                                                        {ITEM_STATUSES.map((s) => (
                                                            <option key={s.value} value={s.value}>
                                                                {s.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                )}
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                title="Aucune action"
                                description={isDraft ? 'Ajoutez les actions concrètes du plan avant de le publier.' : 'Aucune action n\'a été définie sur ce plan.'}
                            />
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
