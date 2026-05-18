import { Badge, Button, Card, CardBody, CardHeader, ConfirmDialog, EmptyState, PageHeader, PageToc } from '@/components/ui';
import { Form, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

type AuditPending = { kind: 'finaliser' } | { kind: 'deleteEcart'; ecartId: number } | null;

interface Ecart {
    id: number;
    critere: string;
    constat: string;
    gravite: 'mineur' | 'majeur' | 'critique';
    gravite_label: string;
    action_corrective: string | null;
}

interface Audit {
    id: string;
    titre: string;
    referentiel: string;
    referentiel_label: string;
    description: string | null;
    date_audit: string | null;
    statut: 'planifie' | 'en_cours' | 'termine' | 'annule';
    statut_label: string;
    score: number | null;
    auditeur: string | null;
    finalized_at: string | null;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    is_terminal: boolean;
    ecarts: Ecart[];
    created_at: string | null;
}

interface GraviteOption {
    value: 'mineur' | 'majeur' | 'critique';
    label: string;
}

interface Capabilities {
    execute: boolean;
    finalize: boolean;
    cancel: boolean;
    update?: boolean;
}

interface Props {
    audit: Audit | null;
    gravites: GraviteOption[];
    can: Capabilities;
}

const STATUT_TONE: Record<string, 'brand' | 'warning' | 'sage' | 'neutral' | 'danger'> = {
    planifie: 'brand',
    en_cours: 'warning',
    termine: 'sage',
    annule: 'neutral',
};

const GRAVITE_TONE: Record<string, 'neutral' | 'warning' | 'danger'> = {
    mineur: 'neutral',
    majeur: 'warning',
    critique: 'danger',
};

export default function AuditShow({ audit, gravites = [], can = { execute: false, finalize: false, cancel: false } }: Props) {
    const [showEcartForm, setShowEcartForm] = useState(false);
    const [showCancelForm, setShowCancelForm] = useState(false);

    if (!audit) {
        return (
            <DashboardLayout title="Audit introuvable" subtitle="">
                <Card>
                    <EmptyState
                        title="Audit introuvable"
                        description="Cet audit n'existe pas ou n'est pas encore disponible."
                        action={
                            <Link href="/audits">
                                <Button variant="secondary">Retour aux audits</Button>
                            </Link>
                        }
                    />
                </Card>
            </DashboardLayout>
        );
    }

    const [pending, setPending] = useState<AuditPending>(null);
    const finaliser = () => setPending({ kind: 'finaliser' });
    const deleteEcart = (ecartId: number) => setPending({ kind: 'deleteEcart', ecartId });

    const confirmPending = () => {
        if (!pending) return;
        const done = { onFinish: () => setPending(null) };
        if (pending.kind === 'finaliser') {
            router.post(`/audits/${audit.id}/finaliser`, undefined, done);
        } else {
            router.delete(`/audits/${audit.id}/ecarts/${pending.ecartId}`, done);
        }
    };

    const pendingMeta = pending?.kind === 'finaliser'
        ? {
            title: 'Finaliser cet audit ?',
            description: "Le score sera calculé à partir des écarts saisis. L'audit ne pourra plus être modifié et un PDF horodaté sera généré en arrière-plan.",
            confirmLabel: 'Finaliser',
            tone: 'warning' as const,
        }
        : pending?.kind === 'deleteEcart'
            ? {
                title: 'Supprimer cet écart ?',
                description: "L'écart sera retiré du registre. Cette action est irréversible avant la finalisation de l'audit.",
                confirmLabel: 'Supprimer',
                tone: 'danger' as const,
            }
            : null;

    return (
        <DashboardLayout title={audit.titre} subtitle="">
            <PageHeader
                title={audit.titre}
                subtitle={`${audit.referentiel_label} · ${audit.date_audit ?? 'Non planifié'}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: audit.titre },
                ]}
                actions={
                    <>
                        <Badge tone={STATUT_TONE[audit.statut] ?? 'neutral'} size="sm" dot>
                            {audit.statut_label}
                        </Badge>
                        {audit.score !== null && (
                            <span className="font-mono text-lg font-bold text-ink-900 dark:text-white">{audit.score}%</span>
                        )}
                        {!audit.is_terminal && can.update && (
                            <Link href={`/audits/${audit.id}/edit`}>
                                <Button variant="secondary">Modifier</Button>
                            </Link>
                        )}
                        {!audit.is_terminal && can.finalize && (
                            <Button onClick={finaliser}>Finaliser</Button>
                        )}
                        {!audit.is_terminal && can.cancel && (
                            <Button variant="secondary" onClick={() => setShowCancelForm(!showCancelForm)}>
                                {showCancelForm ? 'Annuler' : 'Annuler audit'}
                            </Button>
                        )}
                    </>
                }
            />

            {showCancelForm && !audit.is_terminal && (
                <div className="mb-5 rounded-2xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-700/50 dark:bg-warning-900/20">
                    <Form action={`/audits/${audit.id}/cancel`} method="post" onSuccess={() => setShowCancelForm(false)}>
                        {({ processing }) => (
                            <div className="space-y-3">
                                <label className="block">
                                    <span className="text-xs font-semibold uppercase tracking-wider text-warning-700 dark:text-warning-300">Motif d'annulation (optionnel)</span>
                                    <input type="text" name="reason" maxLength={500} className="mt-1.5 h-10 w-full rounded-lg border border-warning-200 bg-white px-3 text-sm dark:border-warning-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                </label>
                                <div className="flex justify-end gap-2">
                                    <Button variant="ghost" size="sm" onClick={() => setShowCancelForm(false)}>Retour</Button>
                                    <Button type="submit" variant="danger" loading={processing}>Confirmer l'annulation</Button>
                                </div>
                            </div>
                        )}
                    </Form>
                </div>
            )}

            {audit.cancellation_reason && (
                <div className="mb-5 rounded-2xl border border-ink-200 bg-ink-50 p-4 text-sm text-ink-700 dark:border-ink-700/60 dark:bg-ink-800/60 dark:text-ink-300">
                    <span className="font-semibold">Motif d'annulation :</span> {audit.cancellation_reason}
                </div>
            )}

            <PageToc
                items={[
                    { id: 'audit-description', label: 'Description' },
                    { id: 'audit-synthese', label: 'Synthèse' },
                    { id: 'audit-ecarts', label: 'Écarts identifiés' },
                ]}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card id="audit-description" className="scroll-mt-24 lg:col-span-2">
                    <CardHeader title="Description" />
                    <CardBody>
                        {audit.description ? (
                            <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{audit.description}</p>
                        ) : (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">Aucune description renseignée.</p>
                        )}
                    </CardBody>
                </Card>

                <Card id="audit-synthese" className="scroll-mt-24">
                    <CardHeader title="Synthèse" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row label="Référentiel" value={audit.referentiel_label} />
                            <Row label="Date" value={audit.date_audit ?? '—'} />
                            <Row label="Auditeur" value={audit.auditeur ?? '—'} />
                            <Row label="Score" value={audit.score !== null ? `${audit.score} %` : '—'} />
                            <Row label="Écarts" value={`${audit.ecarts.length}`} />
                            {audit.finalized_at && <Row label="Finalisé" value={audit.finalized_at} />}
                        </dl>
                    </CardBody>
                </Card>

                <Card id="audit-ecarts" className="scroll-mt-24 lg:col-span-3">
                    <CardHeader
                        title="Écarts identifiés"
                        subtitle={`${audit.ecarts.length} écart(s)`}
                        action={
                            !audit.is_terminal && can.execute ? (
                                <Button variant="secondary" size="sm" onClick={() => setShowEcartForm(!showEcartForm)}>
                                    {showEcartForm ? 'Annuler' : '+ Ajouter un écart'}
                                </Button>
                            ) : undefined
                        }
                    />
                    <CardBody>
                        {showEcartForm && !audit.is_terminal && can.execute && (
                            <div className="mb-5 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                                <Form action={`/audits/${audit.id}/ecarts`} method="post" onSuccess={() => setShowEcartForm(false)} resetOnSuccess>
                                    {({ errors, processing }) => (
                                        <div className="space-y-3">
                                            <label className="block">
                                                <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Critère évalué *</span>
                                                <input type="text" name="critere" required maxLength={255} placeholder="Ex: Traçabilité des transmissions" className="mt-1.5 h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                                {errors.critere && <p className="mt-1 text-xs text-danger-600">{errors.critere}</p>}
                                            </label>
                                            <label className="block">
                                                <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Constat *</span>
                                                <textarea name="constat" required rows={3} maxLength={5000} placeholder="Description précise du constat…" className="mt-1.5 w-full rounded-lg border border-brand-200 bg-white px-3 py-2.5 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                                {errors.constat && <p className="mt-1 text-xs text-danger-600">{errors.constat}</p>}
                                            </label>
                                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <label className="block">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Gravité *</span>
                                                    <select name="gravite" required defaultValue="mineur" className="mt-1.5 h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100">
                                                        {gravites.map((g) => (
                                                            <option key={g.value} value={g.value}>{g.label}</option>
                                                        ))}
                                                    </select>
                                                </label>
                                                <label className="block">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Action corrective</span>
                                                    <input type="text" name="action_corrective" maxLength={2000} placeholder="Action immédiate (optionnel)" className="mt-1.5 h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                                </label>
                                            </div>
                                            <label className="flex items-center gap-2">
                                                <input type="checkbox" name="create_pac" value="1" className="size-4 rounded border-ink-300 text-brand-600 dark:border-ink-600 dark:bg-ink-800" />
                                                <span className="text-sm text-ink-700 dark:text-ink-300">
                                                    Générer un plan d'amélioration (recommandé pour les écarts majeurs et critiques)
                                                </span>
                                            </label>
                                            <div className="flex justify-end gap-2">
                                                <Button variant="ghost" size="sm" onClick={() => setShowEcartForm(false)}>Annuler</Button>
                                                <Button type="submit" size="sm" loading={processing}>Ajouter l'écart</Button>
                                            </div>
                                        </div>
                                    )}
                                </Form>
                            </div>
                        )}

                        {audit.ecarts.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {audit.ecarts.map((e) => (
                                    <li key={e.id} className="py-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium text-ink-900 dark:text-white">{e.critere}</p>
                                                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">{e.constat}</p>
                                                {e.action_corrective && (
                                                    <p className="mt-1 text-xs text-sage-700 dark:text-sage-300">→ {e.action_corrective}</p>
                                                )}
                                            </div>
                                            <Badge tone={GRAVITE_TONE[e.gravite] ?? 'neutral'} size="sm" dot>{e.gravite_label}</Badge>
                                            {!audit.is_terminal && can.execute && (
                                                <button type="button" onClick={() => deleteEcart(e.id)} className="shrink-0 text-xs text-danger-500 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300">
                                                    Supprimer
                                                </button>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucun écart identifié" description="Saisissez les écarts au fil de l'audit. Ils alimentent automatiquement le score final." />
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
