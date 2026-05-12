import { Badge, Button, Card, CardBody, CardHeader, ConfirmDialog, EmptyState, PageHeader } from '@/components/ui';
import { Form, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

type PacPending = { kind: 'markDone'; actionId: number } | { kind: 'deleteAction'; actionId: number } | null;

interface Action {
    id: number;
    description: string;
    responsable: string | null;
    echeance: string | null;
    statut: 'planifiee' | 'en_cours' | 'realisee' | 'annulee';
    statut_label: string;
    realise_at: string | null;
}

interface Plan {
    id: string;
    titre: string;
    source: string;
    source_label: string;
    source_id: string | null;
    constat: string | null;
    statut: 'ouvert' | 'en_cours' | 'termine' | 'annule';
    statut_label: string;
    responsable: string | null;
    echeance: string | null;
    progression: number;
    is_terminal: boolean;
    closed_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    actions: Action[];
    created_at: string | null;
}

interface Capabilities {
    update: boolean;
    close: boolean;
    cancel: boolean;
}

interface Props {
    plan: Plan | null;
    can: Capabilities;
}

const STATUT_TONE: Record<string, 'brand' | 'warning' | 'sage' | 'neutral'> = {
    ouvert: 'brand',
    en_cours: 'warning',
    termine: 'sage',
    annule: 'neutral',
};

export default function PlanAmeliorationShow({ plan, can = { update: false, close: false, cancel: false } }: Props) {
    const [showActionForm, setShowActionForm] = useState(false);
    const [showCloseForm, setShowCloseForm] = useState(false);
    const [showCancelForm, setShowCancelForm] = useState(false);

    if (!plan) {
        return (
            <DashboardLayout title="Plan introuvable" subtitle="">
                <Card>
                    <EmptyState
                        title="Plan introuvable"
                        description="Ce plan d'amélioration n'existe pas ou n'est pas encore disponible."
                        action={
                            <Link href="/plans-amelioration">
                                <Button variant="secondary">Retour</Button>
                            </Link>
                        }
                    />
                </Card>
            </DashboardLayout>
        );
    }

    const [pending, setPending] = useState<PacPending>(null);

    const markActionDone = (actionId: number) => setPending({ kind: 'markDone', actionId });
    const deleteAction = (actionId: number) => setPending({ kind: 'deleteAction', actionId });

    const confirmPending = () => {
        if (!pending) return;
        const done = { onFinish: () => setPending(null) };
        if (pending.kind === 'markDone') {
            router.post(`/plans-amelioration/${plan.id}/actions/${pending.actionId}/done`, undefined, done);
        } else {
            router.delete(`/plans-amelioration/${plan.id}/actions/${pending.actionId}`, done);
        }
    };

    const pendingMeta = pending?.kind === 'markDone'
        ? {
            title: 'Marquer cette action comme réalisée ?',
            description: "L'action sera comptabilisée dans le taux de complétion du plan d'amélioration.",
            confirmLabel: 'Marquer réalisée',
            tone: 'info' as const,
        }
        : pending?.kind === 'deleteAction'
            ? {
                title: 'Supprimer cette action ?',
                description: "L'action sera définitivement retirée du plan. Le taux de complétion sera recalculé.",
                confirmLabel: 'Supprimer',
                tone: 'danger' as const,
            }
            : null;

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
                actions={
                    <>
                        <Badge tone={STATUT_TONE[plan.statut] ?? 'neutral'} size="sm" dot>{plan.statut_label}</Badge>
                        {!plan.is_terminal && can.update && (
                            <Link href={`/plans-amelioration/${plan.id}/edit`}>
                                <Button variant="secondary">Modifier</Button>
                            </Link>
                        )}
                        {!plan.is_terminal && can.close && (
                            <Button onClick={() => setShowCloseForm(!showCloseForm)}>
                                {showCloseForm ? 'Annuler' : 'Clôturer'}
                            </Button>
                        )}
                        {!plan.is_terminal && can.cancel && (
                            <Button variant="secondary" onClick={() => setShowCancelForm(!showCancelForm)}>
                                {showCancelForm ? 'Retour' : 'Annuler plan'}
                            </Button>
                        )}
                    </>
                }
            />

            {showCloseForm && !plan.is_terminal && (
                <div className="mb-5 rounded-2xl border border-sage-200 bg-sage-50 p-4 dark:border-sage-700/50 dark:bg-sage-900/20">
                    <Form action={`/plans-amelioration/${plan.id}/close`} method="post" onSuccess={() => setShowCloseForm(false)}>
                        {({ processing }) => (
                            <div className="space-y-3">
                                <p className="text-sm text-sage-700 dark:text-sage-300">
                                    Clôturer ce plan ? Les actions encore ouvertes resteront historiques.
                                </p>
                                <label className="block">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-sage-700 dark:text-sage-300">Note de clôture (optionnel)</span>
                                    <input type="text" name="reason" maxLength={500} className="mt-1.5 h-10 w-full rounded-lg border border-sage-200 bg-white px-3 text-sm dark:border-sage-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                </label>
                                <div className="flex justify-end gap-2">
                                    <Button variant="ghost" size="sm" onClick={() => setShowCloseForm(false)}>Retour</Button>
                                    <Button type="submit" loading={processing}>Confirmer la clôture</Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>
            )}

            {showCancelForm && !plan.is_terminal && (
                <div className="mb-5 rounded-2xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-700/50 dark:bg-warning-900/20">
                    <Form action={`/plans-amelioration/${plan.id}/cancel`} method="post" onSuccess={() => setShowCancelForm(false)}>
                        {({ processing }) => (
                            <div className="space-y-3">
                                <label className="block">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-warning-700 dark:text-warning-300">Motif d'annulation (optionnel)</span>
                                    <input type="text" name="reason" maxLength={500} className="mt-1.5 h-10 w-full rounded-lg border border-warning-200 bg-white px-3 text-sm dark:border-warning-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                </label>
                                <div className="flex justify-end gap-2">
                                    <Button variant="ghost" size="sm" onClick={() => setShowCancelForm(false)}>Retour</Button>
                                    <Button type="submit" variant="danger" loading={processing}>Confirmer</Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>
            )}

            {plan.cancellation_reason && (
                <div className="mb-5 rounded-2xl border border-ink-200 bg-ink-50 p-4 text-sm text-ink-700 dark:border-ink-700/60 dark:bg-ink-800/60 dark:text-ink-300">
                    <span className="font-semibold">Motif d'annulation :</span> {plan.cancellation_reason}
                </div>
            )}

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
                            {plan.closed_at && <Row label="Clôturé" value={plan.closed_at} />}
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Actions correctives"
                        subtitle={`${plan.actions.length} action(s)`}
                        action={
                            !plan.is_terminal && can.update ? (
                                <Button variant="secondary" size="sm" onClick={() => setShowActionForm(!showActionForm)}>
                                    {showActionForm ? 'Annuler' : '+ Nouvelle action'}
                                </Button>
                            ) : undefined
                        }
                    />
                    <CardBody>
                        {showActionForm && !plan.is_terminal && can.update && (
                            <div className="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                                <Form action={`/plans-amelioration/${plan.id}/actions`} method="post" onSuccess={() => setShowActionForm(false)} resetOnSuccess>
                                    {({ errors, processing }) => (
                                        <div className="space-y-3">
                                            <label className="block">
                                                <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Description de l'action *</span>
                                                <textarea name="description" required rows={3} maxLength={2000} placeholder="Action concrète à mettre en œuvre…" className="mt-1.5 w-full rounded-lg border border-brand-200 bg-white px-3 py-2.5 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                                {errors.description && <p className="mt-1 text-xs text-danger-600">{errors.description}</p>}
                                            </label>
                                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <label className="block">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Responsable</span>
                                                    <input type="text" name="responsable" maxLength={150} placeholder="Nom du responsable" className="mt-1.5 h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                                </label>
                                                <label className="block">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Échéance</span>
                                                    <input type="date" name="echeance" className="mt-1.5 h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                                </label>
                                            </div>
                                            <div className="flex justify-end gap-2">
                                                <Button variant="ghost" size="sm" onClick={() => setShowActionForm(false)}>Annuler</Button>
                                                <Button type="submit" size="sm" loading={processing}>Ajouter</Button>
                                            </div>
                                        </div>
                                    )}
                                </Form>
                            </div>
                        )}

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
                                                    {a.realise_at && <> · Réalisée le <span className="font-mono">{a.realise_at}</span></>}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-2">
                                                <Badge tone={a.statut === 'realisee' ? 'sage' : a.statut === 'annulee' ? 'neutral' : 'warning'} size="sm">
                                                    {a.statut_label}
                                                </Badge>
                                                {!plan.is_terminal && can.update && a.statut !== 'realisee' && a.statut !== 'annulee' && (
                                                    <button type="button" onClick={() => markActionDone(a.id)} className="text-xs text-sage-600 hover:text-sage-700 dark:text-sage-400 dark:hover:text-sage-300">
                                                        ✓ Marquer réalisée
                                                    </button>
                                                )}
                                                {!plan.is_terminal && can.update && (
                                                    <button type="button" onClick={() => deleteAction(a.id)} className="text-xs text-danger-500 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300">
                                                        Supprimer
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucune action définie" description="Ajoutez des actions correctives concrètes pour traiter l'écart identifié." />
                        )}
                    </CardBody>
                </Card>
            </div>

            {pendingMeta && (
                <ConfirmDialog
                    open={pending !== null}
                    onClose={() => setPending(null)}
                    onConfirm={confirmPending}
                    title={pendingMeta.title}
                    description={pendingMeta.description}
                    confirmLabel={pendingMeta.confirmLabel}
                    tone={pendingMeta.tone}
                />
            )}
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
