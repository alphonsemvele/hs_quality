import {
    Badge,
    Button,
    Card,
    CardBody,
    CardHeader,
    ConfirmDialog,
    EmptyState,
    InterventionStatusBadge,
    PageHeader,
} from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

type PendingAction =
    | { kind: 'checkout' }
    | { kind: 'cancel' }
    | { kind: 'deletePhoto'; photoId: number }
    | null;
import DashboardLayout from '../layout';

interface Person {
    id: number | string | null;
    name: string;
}

interface CompletedTask {
    id: number;
    planned_task_description: string | null;
    completed_at: string | null;
    notes: string | null;
}

interface Photo {
    id: number;
    original_name: string | null;
    taken_at: string | null;
}

interface Signature {
    id: number;
    signer_type: string;
    signed_at: string | null;
}

interface Intervention {
    id: string;
    intervenant: Person & { email?: string | null };
    beneficiaire: Person;
    care_plan: { id: string; title: string } | null;
    statut: 'planifiee' | 'en_cours' | 'realisee' | 'annulee' | 'non_realisee';
    visit_mode: string;
    planned_date: string | null;
    planned_start_time: string | null;
    planned_end_time: string | null;
    actual_start_at: string | null;
    actual_end_at: string | null;
    duree_minutes: number | null;
    report_text: string;
    cancellation_reason: string | null;
    completed_tasks: CompletedTask[];
    photos: Photo[];
    signatures: Signature[];
}

export default function ShowIntervention({ intervention }: { intervention: Intervention }) {
    const isPlanned = intervention.statut === 'planifiee';
    const isInProgress = intervention.statut === 'en_cours';
    const canAct = useCan('interventions.update');

    const [pending, setPending] = useState<PendingAction>(null);

    const checkIn = () => router.post(`/interventions/${intervention.id}/checkin`);
    const checkOut = () => setPending({ kind: 'checkout' });
    const cancel = () => setPending({ kind: 'cancel' });
    const deletePhoto = (photoId: number) => setPending({ kind: 'deletePhoto', photoId });

    const confirmPending = () => {
        if (!pending) return;
        const done = { onFinish: () => setPending(null) };
        if (pending.kind === 'checkout') {
            router.post(`/interventions/${intervention.id}/checkout`, undefined, done);
        } else if (pending.kind === 'cancel') {
            router.post(`/interventions/${intervention.id}/cancel`, undefined, done);
        } else if (pending.kind === 'deletePhoto') {
            router.delete(`/interventions/${intervention.id}/photos/${pending.photoId}`, done);
        }
    };

    const pendingMeta = (() => {
        if (!pending) return null;
        if (pending.kind === 'checkout') return {
            title: 'Clôturer cette intervention ?',
            description: "Le rapport et les éventuelles photos seront verrouillés. Vous pourrez toujours les consulter mais plus les modifier.",
            confirmLabel: 'Clôturer',
            tone: 'warning' as const,
        };
        if (pending.kind === 'cancel') return {
            title: "Annuler cette intervention ?",
            description: "L'intervention sera marquée comme annulée. Cette action est tracée et visible dans l'historique du bénéficiaire.",
            confirmLabel: 'Confirmer l\'annulation',
            tone: 'danger' as const,
        };
        return {
            title: 'Supprimer cette photo ?',
            description: 'La photo sera définitivement supprimée du rapport. Cette action est irréversible.',
            confirmLabel: 'Supprimer',
            tone: 'danger' as const,
        };
    })();

    return (
        <DashboardLayout title="Détail intervention" subtitle="">
            <PageHeader
                title={intervention.beneficiaire.name || 'Bénéficiaire inconnu'}
                subtitle={`Intervenant : ${intervention.intervenant.name || '—'} · ${intervention.planned_date ?? ''}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Interventions', href: '/interventions' },
                    { label: `#${String(intervention.id).slice(0, 8)}` },
                ]}
                actions={
                    <>
                        <InterventionStatusBadge statut={intervention.statut} />
                        {isPlanned && canAct && (
                            <>
                                <Button variant="secondary" onClick={cancel}>
                                    Annuler
                                </Button>
                                <Link href={`/interventions/${intervention.id}/edit`}>
                                    <Button variant="secondary">Modifier</Button>
                                </Link>
                                <Button onClick={checkIn}>Démarrer (check-in)</Button>
                            </>
                        )}
                        {isInProgress && (
                            <Badge tone="warning" dot>
                                En cours sur le terrain
                            </Badge>
                        )}
                        {isInProgress && canAct && (
                            <Button onClick={checkOut}>Clôturer (check-out)</Button>
                        )}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Synthèse" />
                    <CardBody>
                        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Field label="Intervenant" value={intervention.intervenant.name} sub={intervention.intervenant.email ?? undefined} />
                            <Field label="Bénéficiaire" value={intervention.beneficiaire.name} />
                            <Field
                                label="Date prévue"
                                value={intervention.planned_date ?? '—'}
                                sub={[intervention.planned_start_time, intervention.planned_end_time].filter(Boolean).join(' – ')}
                            />
                            <Field
                                label="Durée réelle"
                                value={intervention.duree_minutes ? `${intervention.duree_minutes} min` : '—'}
                            />
                            <Field label="Plan d'accompagnement" value={intervention.care_plan?.title ?? 'Aucun'} />
                            <Field label="Mode" value={intervention.visit_mode} />
                        </div>

                        {intervention.cancellation_reason && (
                            <div className="mt-6 rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-700/50 dark:bg-danger-900/20">
                                <p className="text-xs font-semibold uppercase tracking-wider text-danger-700 dark:text-danger-300">Motif d'annulation</p>
                                <p className="mt-1 text-sm text-danger-700 dark:text-danger-300">{intervention.cancellation_reason}</p>
                            </div>
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Compte-rendu" />
                    <CardBody>
                        {intervention.report_text ? (
                            <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{intervention.report_text}</p>
                        ) : (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">Aucun compte-rendu encore renseigné.</p>
                        )}
                    </CardBody>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader title="Tâches réalisées" subtitle={`${intervention.completed_tasks.length} tâche(s)`} />
                    <CardBody>
                        {intervention.completed_tasks.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {intervention.completed_tasks.map((t) => (
                                    <li key={t.id} className="flex items-start gap-3 py-3">
                                        <div className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-sage-50 text-sage-600 dark:bg-sage-900/30 dark:text-sage-400">
                                            <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={3} viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium text-ink-900 dark:text-white">{t.planned_task_description ?? 'Tâche'}</p>
                                            {t.notes && <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">{t.notes}</p>}
                                            {t.completed_at && (
                                                <p className="mt-0.5 font-mono text-[11px] text-ink-400 dark:text-ink-500">{t.completed_at}</p>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucune tâche enregistrée" description="Les tâches seront cochées par l'intervenant pendant la visite." />
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader
                        title="Médias & signature"
                        subtitle={`${intervention.photos.length} photo(s) · ${intervention.signatures.length} signature(s)`}
                    />
                    <CardBody>
                        {/* Photo upload form */}
                        {isInProgress && canAct && (
                            <form
                                action={`/interventions/${intervention.id}/photos`}
                                method="post"
                                encType="multipart/form-data"
                                onSubmit={(e) => {
                                    e.preventDefault();
                                    const formData = new FormData(e.currentTarget);
                                    router.post(`/interventions/${intervention.id}/photos`, Object.fromEntries(formData));
                                }}
                                className="mb-4 flex items-center gap-3 rounded-xl border border-dashed border-ink-200 bg-ink-50/50 p-4 dark:border-ink-600 dark:bg-ink-800/50"
                            >
                                <input type="file" name="photo" accept="image/*" required className="flex-1 text-sm text-ink-600 dark:text-ink-400 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-brand-700 dark:file:bg-brand-900/30 dark:file:text-brand-300" />
                                <Button type="submit" size="sm" variant="secondary">Envoyer</Button>
                            </form>
                        )}

                        {intervention.photos.length === 0 && intervention.signatures.length === 0 && !isInProgress ? (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">Aucun média ni signature.</p>
                        ) : (
                            <div className="space-y-3">
                                {intervention.photos.map((p) => (
                                    <div key={p.id} className="flex items-center gap-3 rounded-lg border border-ink-100 p-3 dark:border-ink-700/60">
                                        <div className="flex size-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-400">
                                            <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 7l3-3h12l3 3M21 7v13a1 1 0 01-1 1H4a1 1 0 01-1-1V7m9 4a3 3 0 100 6 3 3 0 000-6z" />
                                            </svg>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-ink-900 dark:text-white">{p.original_name ?? `Photo #${p.id}`}</p>
                                            {p.taken_at && <p className="font-mono text-[11px] text-ink-400 dark:text-ink-500">{p.taken_at}</p>}
                                        </div>
                                        {canAct && (
                                            <button type="button" onClick={() => deletePhoto(p.id)} className="shrink-0 text-xs text-danger-500 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300">Supprimer</button>
                                        )}
                                    </div>
                                ))}
                                {intervention.signatures.map((s) => (
                                    <div key={s.id} className="flex items-center gap-3 rounded-lg border border-ink-100 p-3 dark:border-ink-700/60">
                                        <div className="flex size-9 items-center justify-center rounded-lg bg-sage-50 text-sage-600 dark:bg-sage-900/30 dark:text-sage-400">
                                            <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 17l6-6 4 4 8-8M14 7h7v7" />
                                            </svg>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium text-ink-900 dark:text-white">Signature {s.signer_type}</p>
                                            {s.signed_at && <p className="font-mono text-[11px] text-ink-400 dark:text-ink-500">{s.signed_at}</p>}
                                        </div>
                                    </div>
                                ))}
                            </div>
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

function Field({ label, value, sub }: { label: string; value: string; sub?: string }) {
    return (
        <div>
            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</p>
            <p className="mt-1 text-sm font-medium text-ink-900 dark:text-white">{value}</p>
            {sub && <p className="mt-0.5 font-mono text-xs text-ink-500 dark:text-ink-400">{sub}</p>}
        </div>
    );
}
