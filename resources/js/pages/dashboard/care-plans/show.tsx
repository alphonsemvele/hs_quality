import {
    Badge,
    Button,
    Card,
    CardBody,
    CardHeader,
    CarePlanStatusBadge,
    EmptyState,
    PageHeader,
} from '@/components/ui';
import { useCan } from '@/lib/can';
import { Form, Link, router } from '@inertiajs/react';
import { FormField, Input, Textarea } from '@/components/ui';
import { useState } from 'react';
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
    const tasks = unwrap<PlannedTask[]>(p.tasks ?? []) ?? [];
    const canManage = useCan('beneficiaries.update');

    const [showArchive, setShowArchive] = useState(false);
    const [showAddTask, setShowAddTask] = useState(false);

    const copyPlan = () => {
        if (confirm('Dupliquer ce plan d\'accompagnement ?')) {
            router.post(`/care-plans/${p.id}/copy`);
        }
    };
    const deleteTask = (taskId: number) => {
        if (confirm('Supprimer cette tâche ?')) {
            router.delete(`/tasks/${taskId}`);
        }
    };

    const activate = () => {
        if (confirm("Activer ce plan ? Tout plan déjà actif pour ce bénéficiaire sera automatiquement archivé.")) {
            router.post(`/care-plans/${p.id}/activate`);
        }
    };

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

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Objectifs" subtitle="Donnée chiffrée — accès tracé" />
                    <CardBody>
                        {p.objectives ? (
                            <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{p.objectives}</p>
                        ) : (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">Aucun objectif renseigné.</p>
                        )}
                    </CardBody>
                </Card>

                <Card>
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

                <Card className="lg:col-span-3">
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
                                {tasks.map((t) => (
                                    <li key={t.id} className="flex items-start gap-4 py-3">
                                        <div className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                            {t.task_order}
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
                                ))}
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
