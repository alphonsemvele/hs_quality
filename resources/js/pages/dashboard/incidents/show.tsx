import {
    Badge,
    Button,
    Card,
    CardBody,
    CardHeader,
    EmptyState,
    IncidentGraviteBadge,
    IncidentStatusBadge,
    PageHeader,
} from '@/components/ui';
import { Form, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface ActionCorrective {
    id: number;
    description: string;
    echeance: string | null;
    statut: string;
    responsable: string | null;
    realise_at: string | null;
}

interface Suivi {
    id: number;
    note: string;
    author: string | null;
    created_at: string | null;
}

interface Incident {
    id: string;
    declarant: { id: number | null; name: string };
    assignee: { id: number; name: string } | null;
    beneficiaire: { id: string; name: string } | null;
    categorie: string;
    gravite: 'mineur' | 'significatif' | 'grave' | 'critique';
    statut: 'declare' | 'en_analyse' | 'plan_actions' | 'clos';
    description: string;
    lieu: string | null;
    occurred_at: string;
    avec_deces: boolean;
    avec_hospitalisation: boolean;
    avec_blessure_physique: boolean;
    analyse_causes: string;
    closed_at: string | null;
    notifie_responsable: boolean;
    notifie_autorites: boolean;
    actions_correctives: ActionCorrective[];
    suivis: Suivi[];
}

export default function ShowIncident({ incident }: { incident: Incident }) {
    const [showAnalysisForm, setShowAnalysisForm] = useState(false);
    const [showActionForm, setShowActionForm] = useState(false);

    const startAnalyse = () => router.post(`/incidents/${incident.id}/assign`, { assigned_to: incident.declarant.id });
    const closeIncident = () => {
        if (confirm('Confirmer la clôture de cet incident ?')) {
            router.post(`/incidents/${incident.id}/close`);
        }
    };

    const isClosed = incident.statut === 'clos';

    return (
        <DashboardLayout title={incident.categorie} subtitle="Détail incident">
            <PageHeader
                title={incident.categorie}
                subtitle={`Déclaré par ${incident.declarant.name} · ${incident.occurred_at}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Incidents', href: '/incidents' },
                    { label: `#${String(incident.id).slice(0, 8)}` },
                ]}
                actions={
                    <>
                        <IncidentGraviteBadge gravite={incident.gravite} />
                        <IncidentStatusBadge statut={incident.statut} />
                        {!isClosed && (
                            <>
                                {incident.statut === 'declare' && (
                                    <Button variant="secondary" onClick={startAnalyse}>
                                        Démarrer l'analyse
                                    </Button>
                                )}
                                <Button variant="danger" onClick={closeIncident}>
                                    Clôturer
                                </Button>
                            </>
                        )}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Description des faits" />
                    <CardBody>
                        <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                            {incident.description || <span className="italic text-ink-500 dark:text-ink-400">Aucune description.</span>}
                        </p>

                        {(incident.avec_deces || incident.avec_hospitalisation || incident.avec_blessure_physique) && (
                            <div className="mt-5 flex flex-wrap gap-2">
                                {incident.avec_deces && (
                                    <Badge tone="danger" size="sm">
                                        Décès
                                    </Badge>
                                )}
                                {incident.avec_hospitalisation && (
                                    <Badge tone="danger" size="sm">
                                        Hospitalisation
                                    </Badge>
                                )}
                                {incident.avec_blessure_physique && (
                                    <Badge tone="warning" size="sm">
                                        Blessure physique
                                    </Badge>
                                )}
                            </div>
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Synthèse" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row label="Survenu le" value={incident.occurred_at} />
                            <Row label="Lieu" value={incident.lieu ?? '—'} />
                            <Row label="Bénéficiaire" value={incident.beneficiaire?.name ?? '—'} />
                            <Row label="Déclaré par" value={incident.declarant.name} />
                            <Row label="Assigné à" value={incident.assignee?.name ?? 'Non assigné'} />
                            {incident.closed_at && <Row label="Clôturé le" value={incident.closed_at} />}
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader
                        title="Actions correctives"
                        subtitle={`${incident.actions_correctives.length} action(s)`}
                        action={
                            !isClosed && (
                                <Button variant="secondary" size="sm" onClick={() => setShowActionForm(!showActionForm)}>
                                    {showActionForm ? 'Annuler' : '+ Nouvelle action'}
                                </Button>
                            )
                        }
                    />
                    <CardBody>
                        {showActionForm && (
                            <div className="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                                <Form action={`/incidents/${incident.id}/actions`} method="post" onSuccess={() => setShowActionForm(false)} resetOnSuccess>
                                    {({ processing }) => (
                                        <div className="space-y-3">
                                            <label className="block">
                                                <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Description de l'action</span>
                                                <textarea name="description" required rows={3} maxLength={2000} placeholder="Action corrective à mettre en oeuvre…" className="mt-1.5 w-full rounded-lg border border-brand-200 bg-white px-3 py-2.5 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
                                            </label>
                                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                <label className="block">
                                                    <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">Responsable</span>
                                                    <input type="text" name="responsable" maxLength={150} placeholder="Nom" className="mt-1.5 h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100" />
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
                        {incident.actions_correctives.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {incident.actions_correctives.map((a) => (
                                    <li key={a.id} className="py-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium text-ink-900 dark:text-white">{a.description}</p>
                                                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                                    Responsable : <span className="text-ink-700 dark:text-ink-300">{a.responsable ?? '—'}</span>
                                                    {a.echeance && (
                                                        <>
                                                            {' '}· Échéance : <span className="font-mono">{a.echeance}</span>
                                                        </>
                                                    )}
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
                            <EmptyState title="Aucune action corrective" description="Définissez des actions concrètes pour traiter la cause racine." />
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Notifications" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row
                                label="Responsable hiérarchique"
                                value={
                                    <Badge tone={incident.notifie_responsable ? 'sage' : 'neutral'} size="sm">
                                        {incident.notifie_responsable ? 'Notifié' : 'En attente'}
                                    </Badge>
                                }
                            />
                            <Row
                                label="ARS"
                                value={
                                    <Badge tone={incident.notifie_autorites ? 'sage' : 'neutral'} size="sm">
                                        {incident.notifie_autorites
                                            ? 'Notifiée'
                                            : ['grave', 'critique'].includes(incident.gravite)
                                              ? 'À notifier (24h)'
                                              : 'Non requise'}
                                    </Badge>
                                }
                            />
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Analyse 5-pourquoi"
                        subtitle="Cause racine identifiée"
                        action={
                            !isClosed &&
                            !incident.analyse_causes && (
                                <Button variant="secondary" size="sm" onClick={() => setShowAnalysisForm(!showAnalysisForm)}>
                                    {showAnalysisForm ? 'Annuler' : '+ Démarrer'}
                                </Button>
                            )
                        }
                    />
                    <CardBody>
                        {showAnalysisForm && !incident.analyse_causes && (
                            <div className="mb-4 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                                <Form action={`/incidents/${incident.id}/analyse`} method="post" onSuccess={() => setShowAnalysisForm(false)}>
                                    {({ processing }) => (
                                        <div className="space-y-3">
                                            <label className="block">
                                                <span className="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">
                                                    Analyse des causes racines (5-pourquoi)
                                                </span>
                                                <textarea
                                                    name="analyse_causes"
                                                    required
                                                    rows={5}
                                                    maxLength={5000}
                                                    placeholder="Pourquoi 1 : …&#10;Pourquoi 2 : …&#10;Pourquoi 3 : …&#10;Pourquoi 4 : …&#10;Pourquoi 5 : cause racine identifiée"
                                                    className="mt-1.5 w-full rounded-lg border border-brand-200 bg-white px-3 py-2.5 text-sm dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100"
                                                />
                                            </label>
                                            <div className="flex justify-end gap-2">
                                                <Button variant="ghost" size="sm" onClick={() => setShowAnalysisForm(false)}>Annuler</Button>
                                                <Button type="submit" size="sm" loading={processing}>Enregistrer l'analyse</Button>
                                            </div>
                                        </div>
                                    )}
                                </Form>
                            </div>
                        )}
                        {incident.analyse_causes ? (
                            <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{incident.analyse_causes}</p>
                        ) : !showAnalysisForm ? (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">L'analyse 5-pourquoi n'a pas encore été renseignée.</p>
                        ) : null}
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader title="Journal de suivi" subtitle={`${incident.suivis.length} entrée(s)`} />
                    <CardBody>
                        {incident.suivis.length > 0 ? (
                            <ul className="space-y-3">
                                {incident.suivis.map((s) => (
                                    <li key={s.id} className="rounded-xl border border-ink-100 bg-ink-50/50 p-4 dark:border-ink-700/60 dark:bg-ink-900/40">
                                        <div className="flex items-center justify-between text-xs text-ink-500 dark:text-ink-400">
                                            <span className="font-medium text-ink-700 dark:text-ink-300">{s.author ?? 'Utilisateur'}</span>
                                            {s.created_at && <span className="font-mono">{s.created_at}</span>}
                                        </div>
                                        <p className="mt-2 text-sm text-ink-700 dark:text-ink-300">{s.note}</p>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucun suivi" description="Notez les avancées et observations au fil du traitement de l'incident." />
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
