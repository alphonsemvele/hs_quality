import { moveItem, useReorderable } from '@/lib/reorderable';
import {
    Badge,
    Button,
    Card,
    CardBody,
    CardHeader,
    CarePlanStatusBadge,
    ConfirmDialog,
    EmptyState,
    PageHeader,
    PageToc,
} from '@/components/ui';
import { cn } from '@/lib/utils';

type PendingAction = { kind: 'copy' } | { kind: 'activate' } | { kind: 'deleteTask'; taskId: number } | null;
import { useCan } from '@/lib/can';
import { Form, Link, router } from '@inertiajs/react';
import { FormField, Input, Textarea } from '@/components/ui';
import { useEffect, useState } from 'react';
import DashboardLayout from '../layout';

interface PlannedTask {
    id: number;
    title: string;
    description: string | null;
    frequency: string;
    frequency_label: string;
    duration_minutes: number | null;
    task_order: number;
    mandatory: boolean;
}

interface Plan {
    id: string;
    title: string;
    objectives: string | null;
    start_date: string | null;
    end_date: string | null;
    status: 'draft' | 'active' | 'archived';
    status_label: string;
    is_active: boolean;
    is_archived: boolean;
    archived_at: string | null;
    archived_reason: string | null;
    created_by?: { id: number; name: string };
    tasks?: PlannedTask[] | { data: PlannedTask[] };
}

interface Beneficiary {
    id: string;
    full_name: string;
}

function unwrap<T>(value: { data: T } | T): T {
    if (value && typeof value === 'object' && 'data' in (value as object)) {
        return (value as { data: T }).data;
    }
    return value as T;
}

export default function CarePlanShow({ plan, beneficiary }: { plan: { data: Plan } | Plan; beneficiary: { data: Beneficiary } | Beneficiary }) {
    const p = unwrap<Plan>(plan);
    const b = unwrap<Beneficiary>(beneficiary);
    const initialTasks = unwrap<PlannedTask[]>(p.tasks ?? []) ?? [];
    const [tasks, setTasks] = useState<PlannedTask[]>(initialTasks);

    // Re-sync optimistic state when Inertia delivers fresh server state
    // (post-create/delete). Comparing by `id|task_order` join detects both
    // membership changes and reorder commits.
    const signature = initialTasks.map((t) => `${t.id}:${t.task_order}`).join('|');
    useEffect(() => {
        setTasks(initialTasks);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [signature]);

    const persistOrder = (orderedIds: number[]) => {
        router.post(
            `/care-plans/${p.id}/tasks/reorder`,
            { order: orderedIds },
            { preserveScroll: true, preserveState: true },
        );
    };

    const reorderTasks = (fromId: string, toId: string) => {
        const fromIdx = tasks.findIndex((t) => String(t.id) === fromId);
        const toIdx = tasks.findIndex((t) => String(t.id) === toId);
        if (fromIdx === -1 || toIdx === -1) return;
        const next = moveItem(tasks, fromIdx, toIdx);
        setTasks(next);
        persistOrder(next.map((t) => t.id));
    };

    const moveTask = (id: number, direction: -1 | 1) => {
        const idx = tasks.findIndex((t) => t.id === id);
        if (idx === -1) return;
        const target = idx + direction;
        if (target < 0 || target >= tasks.length) return;
        const next = moveItem(tasks, idx, target);
        setTasks(next);
        persistOrder(next.map((t) => t.id));
    };

    const { draggedId, overId, bindItem } = useReorderable(reorderTasks);
    const canManage = useCan('beneficiaries.update');

    const [showArchive, setShowArchive] = useState(false);
    const [showAddTask, setShowAddTask] = useState(false);
    const [pending, setPending] = useState<PendingAction>(null);

    const copyPlan = () => setPending({ kind: 'copy' });
    const deleteTask = (taskId: number) => setPending({ kind: 'deleteTask', taskId });
    const activate = () => setPending({ kind: 'activate' });

    const confirmPending = () => {
        if (!pending) return;
        const done = { onFinish: () => setPending(null) };
        if (pending.kind === 'copy') {
            router.post(`/care-plans/${p.id}/copy`, undefined, done);
        } else if (pending.kind === 'activate') {
            router.post(`/care-plans/${p.id}/activate`, undefined, done);
        } else if (pending.kind === 'deleteTask') {
            router.delete(`/tasks/${pending.taskId}`, done);
        }
    };

    const pendingMeta = (() => {
        if (!pending) return null;
        if (pending.kind === 'copy') return {
            title: "Dupliquer ce plan d'accompagnement ?",
            description: "Une copie du plan sera créée en brouillon, avec toutes ses tâches. Vous pourrez ensuite l'activer pour un autre bénéficiaire.",
            confirmLabel: 'Dupliquer',
            tone: 'info' as const,
        };
        if (pending.kind === 'activate') return {
            title: 'Activer ce plan ?',
            description: "Tout plan déjà actif pour ce bénéficiaire sera automatiquement archivé. L'historique reste consultable.",
            confirmLabel: 'Activer',
            tone: 'warning' as const,
        };
        return {
            title: 'Supprimer cette tâche ?',
            description: 'La tâche sera retirée du plan. Les visites futures ne devront plus la cocher.',
            confirmLabel: 'Supprimer',
            tone: 'danger' as const,
        };
    })();

    return (
        <DashboardLayout title={p.title} subtitle="">
            <PageHeader
                title={p.title}
                subtitle={`Bénéficiaire : ${b.full_name} · ${p.start_date ?? '?'} → ${p.end_date ?? 'sans terme'}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: b.full_name, href: `/beneficiaries/${b.id}` },
                    { label: 'Plans', href: `/beneficiaries/${b.id}/care-plans` },
                    { label: p.title },
                ]}
                actions={
                    <>
                        <CarePlanStatusBadge statut={p.status} />
                        {p.status === 'draft' && canManage && (
                            <Button variant="secondary" onClick={activate}>
                                Activer
                            </Button>
                        )}
                        {!p.is_archived && canManage && (
                            <Button variant="secondary" onClick={() => setShowArchive(!showArchive)}>
                                {showArchive ? 'Annuler' : 'Archiver'}
                            </Button>
                        )}
                        {canManage && (
                            <Button variant="secondary" onClick={copyPlan}>Dupliquer</Button>
                        )}
                        {!p.is_archived && canManage && (
                            <Link href={`/care-plans/${p.id}/edit`}>
                                <Button>Modifier</Button>
                            </Link>
                        )}
                    </>
                }
            />

            {showArchive && (
                <div className="mb-5 rounded-2xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-700/50 dark:bg-warning-900/20">
                    <Form action={`/care-plans/${p.id}/archive`} method="post">
                        {({ processing }) => (
                            <div className="space-y-3">
                                <label className="block">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-warning-700 dark:text-warning-300">
                                        Motif d'archivage (optionnel)
                                    </span>
                                    <input
                                        type="text"
                                        name="reason"
                                        maxLength={500}
                                        className="mt-1.5 h-10 w-full rounded-lg border border-warning-200 bg-white px-3 text-sm dark:border-warning-700/50 dark:bg-ink-800 dark:text-ink-100"
                                    />
                                </label>
                                <div className="flex justify-end gap-2">
                                    <Button variant="ghost" size="sm" onClick={() => setShowArchive(false)}>
                                        Annuler
                                    </Button>
                                    <Button type="submit" variant="danger" loading={processing}>
                                        Confirmer l'archivage
                                    </Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>
            )}

            <PageToc
                items={[
                    { id: 'plan-objectifs', label: 'Objectifs' },
                    { id: 'plan-metadonnees', label: 'Métadonnées' },
                    { id: 'plan-taches', label: 'Tâches planifiées' },
                ]}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card id="plan-objectifs" className="scroll-mt-24 lg:col-span-2">
                    <CardHeader title="Objectifs" subtitle="Donnée chiffrée — accès tracé" />
                    <CardBody>
                        {p.objectives ? (
                            <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{p.objectives}</p>
                        ) : (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">Aucun objectif renseigné.</p>
                        )}
                    </CardBody>
                </Card>

                <Card id="plan-metadonnees" className="scroll-mt-24">
                    <CardHeader title="Métadonnées" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row label="Statut" value={<CarePlanStatusBadge statut={p.status} />} />
                            <Row label="Début" value={<span className="font-mono text-xs">{p.start_date ?? '—'}</span>} />
                            <Row label="Fin" value={<span className="font-mono text-xs">{p.end_date ?? 'sans terme'}</span>} />
                            <Row label="Tâches" value={`${tasks.length}`} />
                            {p.created_by && <Row label="Créé par" value={p.created_by.name} />}
                            {p.archived_at && (
                                <Row
                                    label="Archivé"
                                    value={<span className="font-mono text-xs">{p.archived_at.slice(0, 10)}</span>}
                                />
                            )}
                        </dl>
                    </CardBody>
                </Card>

                <Card id="plan-taches" className="scroll-mt-24 lg:col-span-3">
                    <CardHeader
                        title="Tâches planifiées"
                        subtitle={`${tasks.length} tâche(s)`}
                        action={
                            !p.is_archived && canManage ? (
                                <Button variant="secondary" size="sm" onClick={() => setShowAddTask(!showAddTask)}>
                                    {showAddTask ? 'Annuler' : '+ Ajouter une tâche'}
                                </Button>
                            ) : undefined
                        }
                    />
                    <CardBody>
                        {showAddTask && canManage && (
                            <div className="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                                <Form action={`/care-plans/${p.id}/tasks`} method="post" onSuccess={() => setShowAddTask(false)} resetOnSuccess>
                                    {({ errors, processing }) => (
                                        <div className="space-y-3">
                                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <label className="block">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Titre *</span>
                                                    <input type="text" name="title" required maxLength={255} placeholder="Ex: Aide à la toilette" className="mt-1.5 h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                                </label>
                                                <label className="block">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Fréquence</span>
                                                    <select name="frequency" defaultValue="quotidienne" className="mt-1.5 h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100">
                                                        <option value="quotidienne">Quotidienne</option>
                                                        <option value="hebdomadaire">Hebdomadaire</option>
                                                        <option value="bi_hebdomadaire">Bi-hebdomadaire</option>
                                                        <option value="mensuelle">Mensuelle</option>
                                                        <option value="ponctuelle">Ponctuelle</option>
                                                    </select>
                                                </label>
                                            </div>
                                            <label className="block">
                                                <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Description</span>
                                                <textarea name="description" rows={2} maxLength={1000} placeholder="Instructions pour l'intervenant…" className="mt-1.5 w-full rounded-lg border border-brand-200 bg-white px-3 py-2.5 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                            </label>
                                            <div className="flex items-center gap-4">
                                                <label className="block">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Durée (min)</span>
                                                    <input type="number" name="duration_minutes" min={1} max={480} className="mt-1.5 h-10 w-24 rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                                </label>
                                                <label className="flex items-center gap-2 pt-5">
                                                    <input type="checkbox" name="mandatory" value="1" className="size-4 rounded border-ink-300 text-brand-600 dark:border-ink-600 dark:bg-ink-800" />
                                                    <span className="text-sm text-ink-700 dark:text-ink-300">Obligatoire</span>
                                                </label>
                                            </div>
                                            <div className="flex justify-end gap-2">
                                                <Button variant="ghost" size="sm" onClick={() => setShowAddTask(false)}>Annuler</Button>
                                                <Button type="submit" size="sm" loading={processing}>Ajouter la tâche</Button>
                                            </div>
                                        </div>
                                    )}
                                </Form>
                            </div>
                        )}
                        {tasks.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {tasks.map((t, idx) => {
                                    const reorderProps = !p.is_archived && canManage ? bindItem(String(t.id)) : {};
                                    const isDragged = String(t.id) === draggedId;
                                    const isDropTarget = String(t.id) === overId && !isDragged;
                                    return (
                                        <li
                                            key={t.id}
                                            {...reorderProps}
                                            className={cn(
                                                'flex items-start gap-3 py-3 transition-all',
                                                isDragged && 'opacity-40',
                                                isDropTarget && 'border-t-2 border-brand-500 pt-3.5',
                                                !p.is_archived && canManage && 'cursor-move',
                                            )}
                                        >
                                            {!p.is_archived && canManage && (
                                                <div className="flex shrink-0 flex-col items-center gap-0.5 self-stretch">
                                                    <button
                                                        type="button"
                                                        onClick={() => moveTask(t.id, -1)}
                                                        disabled={idx === 0}
                                                        aria-label="Monter cette tâche"
                                                        className="rounded p-0.5 text-ink-300 transition-colors hover:bg-ink-100 hover:text-ink-600 disabled:opacity-30 dark:text-ink-600 dark:hover:bg-ink-700 dark:hover:text-ink-200"
                                                    >
                                                        <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                                            <polyline points="18 15 12 9 6 15" strokeLinecap="round" strokeLinejoin="round" />
                                                        </svg>
                                                    </button>
                                                    <span aria-hidden className="flex size-5 cursor-move items-center justify-center text-ink-400 dark:text-ink-600" title="Glisser pour réordonner">
                                                        <svg className="size-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                            <circle cx="9" cy="6" r="1.5" /><circle cx="15" cy="6" r="1.5" />
                                                            <circle cx="9" cy="12" r="1.5" /><circle cx="15" cy="12" r="1.5" />
                                                            <circle cx="9" cy="18" r="1.5" /><circle cx="15" cy="18" r="1.5" />
                                                        </svg>
                                                    </span>
                                                    <button
                                                        type="button"
                                                        onClick={() => moveTask(t.id, 1)}
                                                        disabled={idx === tasks.length - 1}
                                                        aria-label="Descendre cette tâche"
                                                        className="rounded p-0.5 text-ink-300 transition-colors hover:bg-ink-100 hover:text-ink-600 disabled:opacity-30 dark:text-ink-600 dark:hover:bg-ink-700 dark:hover:text-ink-200"
                                                    >
                                                        <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                                            <polyline points="6 9 12 15 18 9" strokeLinecap="round" strokeLinejoin="round" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            )}
                                            <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                                {idx + 1}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <p className="text-sm font-medium text-ink-900 dark:text-white">{t.title}</p>
                                                    {t.mandatory && (
                                                        <Badge tone="danger" size="xs">
                                                            Obligatoire
                                                        </Badge>
                                                    )}
                                                    <Badge tone="brand" size="xs">
                                                        {t.frequency_label}
                                                    </Badge>
                                                    {t.duration_minutes && (
                                                        <span className="text-xs text-ink-500 dark:text-ink-400">{t.duration_minutes} min</span>
                                                    )}
                                                </div>
                                                {t.description && <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">{t.description}</p>}
                                            </div>
                                            {!p.is_archived && canManage && (
                                                <button type="button" onClick={() => deleteTask(t.id)} className="shrink-0 text-xs text-danger-500 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300">
                                                    Supprimer
                                                </button>
                                            )}
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <EmptyState
                                title="Aucune tâche planifiée"
                                description="Ajoutez les tâches récurrentes que les intervenants devront cocher pendant chaque visite."
                            />
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
