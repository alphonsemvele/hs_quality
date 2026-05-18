import { PacBurndown } from '@/components/PacBurndown';
import { useTrackRecent } from '@/components/RecentlyViewed';
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

type ActionsView = 'list' | 'kanban';

export default function PlanAmeliorationShow({ plan, can = { update: false, close: false, cancel: false } }: Props) {
    const [showActionForm, setShowActionForm] = useState(false);
    const [showCloseForm, setShowCloseForm] = useState(false);
    const [showCancelForm, setShowCancelForm] = useState(false);
    const [actionsView, setActionsView] = useState<ActionsView>('list');
    const [responsableFilter, setResponsableFilter] = useState<string | null>(null);

    useTrackRecent(
        plan ? { kind: 'plan-amelioration', id: plan.id, label: plan.titre, href: `/plans-amelioration/${plan.id}` } : null,
    );

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

                {plan.actions.length > 0 && (
                    <Card className="lg:col-span-3">
                        <CardHeader title="Burndown 30 jours" subtitle="Évolution des actions ouvertes et clôturées" />
                        <CardBody>
                            <PacBurndown actions={plan.actions} />
                        </CardBody>
                    </Card>
                )}

                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Actions correctives"
                        subtitle={`${plan.actions.length} action(s)`}
                        action={
                            <div className="flex items-center gap-2">
                                <ViewToggle view={actionsView} onChange={setActionsView} />
                                {!plan.is_terminal && can.update && (
                                    <Button variant="secondary" size="sm" onClick={() => setShowActionForm(!showActionForm)}>
                                        {showActionForm ? 'Annuler' : '+ Nouvelle action'}
                                    </Button>
                                )}
                            </div>
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

                        {plan.actions.length === 0 ? (
                            <EmptyState title="Aucune action définie" description="Ajoutez des actions correctives concrètes pour traiter l'écart identifié." />
                        ) : (
                            <>
                                <ResponsableFilter
                                    actions={plan.actions}
                                    selected={responsableFilter}
                                    onChange={setResponsableFilter}
                                />
                                {actionsView === 'list' ? (
                                    <ActionsList
                                        actions={filterByResponsable(plan.actions, responsableFilter)}
                                        canMutate={!plan.is_terminal && can.update}
                                        onMarkDone={markActionDone}
                                        onDelete={deleteAction}
                                    />
                                ) : (
                                    <ActionsKanban
                                        actions={filterByResponsable(plan.actions, responsableFilter)}
                                        canMutate={!plan.is_terminal && can.update}
                                        onMarkDone={markActionDone}
                                        onDelete={deleteAction}
                                    />
                                )}
                            </>
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

// ─── Responsable filter ─────────────────────────────────────────────────────
function filterByResponsable(actions: Action[], responsable: string | null): Action[] {
    if (responsable === null) return actions;
    if (responsable === '__unassigned__') return actions.filter((a) => !a.responsable);
    return actions.filter((a) => a.responsable === responsable);
}

function ResponsableFilter({
    actions,
    selected,
    onChange,
}: {
    actions: Action[];
    selected: string | null;
    onChange: (value: string | null) => void;
}) {
    const owners = Array.from(new Set(actions.map((a) => a.responsable).filter((r): r is string => Boolean(r))));
    const hasUnassigned = actions.some((a) => !a.responsable);

    if (owners.length === 0 && !hasUnassigned) return null;

    return (
        <div className="mb-4 flex flex-wrap items-center gap-2">
            <span className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                Responsable
            </span>
            <FilterChip active={selected === null} onClick={() => onChange(null)} label={`Tous (${actions.length})`} />
            {owners.map((owner) => (
                <FilterChip
                    key={owner}
                    active={selected === owner}
                    onClick={() => onChange(owner)}
                    label={`${owner} (${actions.filter((a) => a.responsable === owner).length})`}
                />
            ))}
            {hasUnassigned && (
                <FilterChip
                    active={selected === '__unassigned__'}
                    onClick={() => onChange('__unassigned__')}
                    label={`Sans responsable (${actions.filter((a) => !a.responsable).length})`}
                />
            )}
        </div>
    );
}

function FilterChip({ active, onClick, label }: { active: boolean; onClick: () => void; label: string }) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            className={
                active
                    ? 'inline-flex items-center rounded-full bg-ink-900 px-3 py-1 text-xs font-semibold text-white dark:bg-white dark:text-ink-900'
                    : 'inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1 text-xs font-medium text-ink-700 transition-colors hover:border-ink-400 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200'
            }
        >
            {label}
        </button>
    );
}

// ─── Toggle Liste/Kanban ────────────────────────────────────────────────────
function ViewToggle({ view, onChange }: { view: ActionsView; onChange: (v: ActionsView) => void }) {
    return (
        <div className="inline-flex rounded-full border border-ink-200 bg-white p-0.5 text-xs font-medium dark:border-ink-700 dark:bg-ink-800">
            <button
                type="button"
                onClick={() => onChange('list')}
                aria-pressed={view === 'list'}
                className={
                    view === 'list'
                        ? 'rounded-full bg-ink-900 px-3 py-1 text-white dark:bg-white dark:text-ink-900'
                        : 'rounded-full px-3 py-1 text-ink-600 transition-colors hover:text-ink-900 dark:text-ink-300 dark:hover:text-white'
                }
            >
                Liste
            </button>
            <button
                type="button"
                onClick={() => onChange('kanban')}
                aria-pressed={view === 'kanban'}
                className={
                    view === 'kanban'
                        ? 'rounded-full bg-ink-900 px-3 py-1 text-white dark:bg-white dark:text-ink-900'
                        : 'rounded-full px-3 py-1 text-ink-600 transition-colors hover:text-ink-900 dark:text-ink-300 dark:hover:text-white'
                }
            >
                Kanban
            </button>
        </div>
    );
}

// ─── Actions: vue liste (préservée) ─────────────────────────────────────────
interface ActionsViewProps {
    actions: Action[];
    canMutate: boolean;
    onMarkDone: (id: number) => void;
    onDelete: (id: number) => void;
}

function ActionsList({ actions, canMutate, onMarkDone, onDelete }: ActionsViewProps) {
    return (
        <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
            {actions.map((a) => (
                <li key={a.id} className="py-3">
                    <div className="flex items-start justify-between gap-3">
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium text-ink-900 dark:text-white">{a.description}</p>
                            <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                {a.responsable && (
                                    <>
                                        Resp. : <span className="text-ink-700 dark:text-ink-300">{a.responsable}</span>
                                    </>
                                )}
                                {a.echeance && (
                                    <>
                                        {' · '}Échéance : <span className="font-mono">{a.echeance}</span>
                                    </>
                                )}
                                {a.realise_at && (
                                    <>
                                        {' · '}Réalisée le <span className="font-mono">{a.realise_at}</span>
                                    </>
                                )}
                            </p>
                        </div>
                        <div className="flex shrink-0 items-center gap-2">
                            <Badge tone={a.statut === 'realisee' ? 'sage' : a.statut === 'annulee' ? 'neutral' : 'warning'} size="sm">
                                {a.statut_label}
                            </Badge>
                            {canMutate && a.statut !== 'realisee' && a.statut !== 'annulee' && (
                                <button
                                    type="button"
                                    onClick={() => onMarkDone(a.id)}
                                    className="text-xs text-sage-600 hover:text-sage-700 dark:text-sage-400 dark:hover:text-sage-300"
                                >
                                    ✓ Marquer réalisée
                                </button>
                            )}
                            {canMutate && (
                                <button
                                    type="button"
                                    onClick={() => onDelete(a.id)}
                                    className="text-xs text-danger-500 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300"
                                >
                                    Supprimer
                                </button>
                            )}
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}

// ─── Actions: vue Kanban (visualisation par colonnes de statut) ──────────────
const KANBAN_COLUMNS: Array<{ key: Action['statut']; label: string; accent: string; dot: string }> = [
    {
        key: 'planifiee',
        label: 'Planifiée',
        accent: 'border-brand-200 bg-brand-50/60 dark:border-brand-700/40 dark:bg-brand-900/20',
        dot: 'bg-brand-500',
    },
    {
        key: 'en_cours',
        label: 'En cours',
        accent: 'border-warning-200 bg-warning-50/60 dark:border-warning-700/40 dark:bg-warning-900/20',
        dot: 'bg-warning-500',
    },
    {
        key: 'realisee',
        label: 'Réalisée',
        accent: 'border-sage-200 bg-sage-50/60 dark:border-sage-700/40 dark:bg-sage-900/20',
        dot: 'bg-sage-500',
    },
    {
        key: 'annulee',
        label: 'Annulée',
        accent: 'border-ink-200 bg-ink-50/60 dark:border-ink-700/40 dark:bg-ink-800/40',
        dot: 'bg-ink-400',
    },
];

function ActionsKanban({ actions, canMutate, onMarkDone, onDelete }: ActionsViewProps) {
    const grouped: Record<Action['statut'], Action[]> = {
        planifiee: [],
        en_cours: [],
        realisee: [],
        annulee: [],
    };
    for (const a of actions) {
        grouped[a.statut].push(a);
    }

    return (
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {KANBAN_COLUMNS.map((col) => (
                <div
                    key={col.key}
                    className={
                        'flex h-full flex-col gap-2 rounded-2xl border p-3 ' + col.accent
                    }
                >
                    <div className="flex items-center justify-between px-1">
                        <div className="flex items-center gap-2">
                            <span className={'size-2 rounded-full ' + col.dot} />
                            <h4 className="text-xs font-semibold uppercase tracking-wider text-ink-700 dark:text-ink-200">
                                {col.label}
                            </h4>
                        </div>
                        <span className="font-mono text-xs text-ink-500 dark:text-ink-400">
                            {grouped[col.key].length}
                        </span>
                    </div>

                    <ul className="flex flex-col gap-2">
                        {grouped[col.key].length === 0 ? (
                            <li className="rounded-xl border border-dashed border-ink-200 px-3 py-4 text-center text-[11px] text-ink-400 dark:border-ink-700 dark:text-ink-500">
                                Aucune action
                            </li>
                        ) : (
                            grouped[col.key].map((a) => (
                                <KanbanCard
                                    key={a.id}
                                    action={a}
                                    canMutate={canMutate}
                                    onMarkDone={onMarkDone}
                                    onDelete={onDelete}
                                />
                            ))
                        )}
                    </ul>
                </div>
            ))}
        </div>
    );
}

function urgencyClass(echeance: string | null, statut: Action['statut']): string {
    if (!echeance || statut === 'realisee' || statut === 'annulee') return '';
    const date = new Date(echeance);
    if (Number.isNaN(date.getTime())) return '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const daysLeft = Math.floor((date.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
    if (daysLeft < 0) return 'border-l-4 border-l-danger-500';
    if (daysLeft <= 3) return 'border-l-4 border-l-danger-400';
    if (daysLeft <= 7) return 'border-l-4 border-l-warning-400';
    return '';
}

function KanbanCard({ action, canMutate, onMarkDone, onDelete }: { action: Action; canMutate: boolean; onMarkDone: (id: number) => void; onDelete: (id: number) => void }) {
    return (
        <li
            className={
                'rounded-xl border border-ink-100 bg-white p-3 shadow-sm transition-shadow hover:shadow-md dark:border-ink-700/60 dark:bg-ink-800 ' +
                urgencyClass(action.echeance, action.statut)
            }
        >
            <p className="text-sm font-medium leading-snug text-ink-900 dark:text-white">
                {action.description}
            </p>

            <dl className="mt-2 space-y-1 text-[11px] text-ink-500 dark:text-ink-400">
                {action.responsable && (
                    <div className="flex items-center gap-1.5">
                        <span className="font-semibold uppercase tracking-wider">Resp.</span>
                        <span className="text-ink-700 dark:text-ink-300">{action.responsable}</span>
                    </div>
                )}
                {action.echeance && (
                    <div className="flex items-center gap-1.5">
                        <span className="font-semibold uppercase tracking-wider">Éch.</span>
                        <span className="font-mono text-ink-700 dark:text-ink-300">{action.echeance}</span>
                    </div>
                )}
                {action.realise_at && (
                    <div className="flex items-center gap-1.5">
                        <span className="font-semibold uppercase tracking-wider">Faite</span>
                        <span className="font-mono text-ink-700 dark:text-ink-300">{action.realise_at}</span>
                    </div>
                )}
            </dl>

            {canMutate && (action.statut === 'planifiee' || action.statut === 'en_cours') && (
                <div className="mt-3 flex items-center justify-end gap-3 border-t border-ink-100 pt-2 dark:border-ink-700/60">
                    <button
                        type="button"
                        onClick={() => onMarkDone(action.id)}
                        className="text-[11px] font-medium text-sage-600 hover:text-sage-700 dark:text-sage-400 dark:hover:text-sage-300"
                    >
                        ✓ Réalisée
                    </button>
                    <button
                        type="button"
                        onClick={() => onDelete(action.id)}
                        className="text-[11px] font-medium text-danger-500 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300"
                    >
                        Supprimer
                    </button>
                </div>
            )}
        </li>
    );
}
