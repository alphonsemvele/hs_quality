import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

type Status = 'pending' | 'processing' | 'ready' | 'failed' | 'expired';

interface ExportRow {
    id: string;
    status: Status;
    status_label: string;
    created_at: string;
    processed_at: string | null;
    expires_at: string | null;
    archive_size_bytes: number | null;
    is_downloadable: boolean;
    failure_reason: string | null;
}

interface DeletionStatus {
    id: string;
    status: 'pending' | 'cancelled' | 'processed' | 'failed';
    status_label: string;
    requested_at: string | null;
    effective_at: string | null;
    can_cancel: boolean;
}

interface Props {
    requests: ExportRow[];
    pendingCount: number;
    deletion: DeletionStatus | null;
}

const STATUS_TONE: Record<Status, 'sage' | 'warning' | 'brand' | 'danger' | 'neutral'> = {
    pending: 'brand',
    processing: 'brand',
    ready: 'sage',
    failed: 'danger',
    expired: 'neutral',
};

export default function GdprPage({ requests = [], pendingCount = 0, deletion = null }: Partial<Props>) {
    const [submittingExport, setSubmittingExport] = useState(false);
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [deleteAck, setDeleteAck] = useState(false);
    const [submittingDelete, setSubmittingDelete] = useState(false);
    const [cancellingDelete, setCancellingDelete] = useState(false);

    const cancelDeletion = () => {
        router.post(
            '/dashboard/profile/gdpr/delete-account/cancel',
            {},
            {
                preserveScroll: true,
                onStart: () => setCancellingDelete(true),
                onFinish: () => setCancellingDelete(false),
            },
        );
    };

    const requestExport = () => {
        router.post(
            '/dashboard/profile/gdpr/export',
            {},
            {
                preserveScroll: true,
                onStart: () => setSubmittingExport(true),
                onFinish: () => setSubmittingExport(false),
            },
        );
    };

    const requestDeletion = () => {
        router.post(
            '/dashboard/profile/gdpr/delete-account',
            { confirm: true },
            {
                preserveScroll: true,
                onStart: () => setSubmittingDelete(true),
                onFinish: () => {
                    setSubmittingDelete(false);
                    setConfirmDelete(false);
                    setDeleteAck(false);
                },
            },
        );
    };

    return (
        <DashboardLayout title="Mes données — RGPD" subtitle="Exercer vos droits sur vos données personnelles">
            <PageHeader
                title="Mes données personnelles"
                subtitle="Exercez vos droits RGPD : accès, portabilité, effacement"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Mon profil', href: '/dashboard/profile' },
                    { label: 'Mes données' },
                ]}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Export section */}
                <Card className="lg:col-span-2">
                    <CardHeader
                        title="Export de mes données"
                        subtitle="RGPD article 15 (accès) & 20 (portabilité)"
                    />
                    <CardBody className="space-y-5">
                        <p className="text-sm leading-relaxed text-ink-700 dark:text-ink-300">
                            Vous pouvez à tout moment demander une copie complète des données personnelles
                            que QualitéDomicile détient à votre sujet. L'archive est générée sous quelques
                            minutes au format ZIP (fichiers JSON) et reste disponible 7 jours.
                        </p>

                        <div className="rounded-xl border border-ink-100 bg-ink-50/40 p-4 text-xs text-ink-600 dark:border-ink-700/60 dark:bg-ink-900/30 dark:text-ink-400">
                            <p className="font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                Ce qui est inclus
                            </p>
                            <ul className="mt-2 list-inside list-disc space-y-1">
                                <li>Vos informations de compte (nom, email, fonction…)</li>
                                <li>Les notifications reçues sur la plateforme</li>
                                <li>La liste de vos jetons d'accès API</li>
                            </ul>
                            <p className="mt-3 text-[11px] text-ink-500 dark:text-ink-400">
                                Les données des bénéficiaires que vous accompagnez restent la propriété de
                                votre structure et ne sont pas incluses dans votre export personnel.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <Button
                                onClick={requestExport}
                                disabled={submittingExport || pendingCount > 0}
                            >
                                {submittingExport
                                    ? 'Demande en cours…'
                                    : pendingCount > 0
                                        ? 'Une demande est déjà en traitement'
                                        : 'Demander un export'}
                            </Button>
                            {pendingCount === 0 && (
                                <span className="text-xs text-ink-500 dark:text-ink-400">
                                    Vous serez notifié par email dès que l'archive sera prête.
                                </span>
                            )}
                        </div>

                        {requests.length > 0 ? (
                            <div className="-mx-6 -mb-5 mt-2 border-t border-ink-100 dark:border-ink-700/60">
                                <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                    {requests.map((r) => (
                                        <RequestRow key={r.id} request={r} />
                                    ))}
                                </ul>
                            </div>
                        ) : (
                            <EmptyState
                                icon={<ArchiveIcon />}
                                title="Aucun export à ce jour"
                                description="Cliquez sur « Demander un export » pour générer votre première archive."
                            />
                        )}
                    </CardBody>
                </Card>

                {/* Deletion section */}
                <Card className="border-danger-200 bg-danger-50/30 dark:border-danger-700/40 dark:bg-danger-900/15">
                    <CardHeader
                        title="Effacement de mon compte"
                        subtitle="RGPD article 17"
                    />
                    <CardBody className="space-y-4">
                        <p className="text-sm leading-relaxed text-danger-900/90 dark:text-danger-200/90">
                            Vous pouvez demander la suppression définitive de votre compte. Vos données
                            d'identité seront anonymisées sous 30 jours, sauf annulation de votre part
                            durant ce délai.
                        </p>
                        <p className="text-xs leading-relaxed text-danger-900/70 dark:text-danger-200/70">
                            Les traces d'activité légalement obligatoires (intervention chez un bénéficiaire,
                            déclaration d'incident, journal d'audit) sont conservées par votre structure
                            conformément au Code de la santé publique, mais ne seront plus rattachées à
                            votre identité.
                        </p>

                        {deletion && deletion.status === 'pending' ? (
                            <div className="space-y-3 rounded-xl border border-danger-200 bg-white p-3 dark:border-danger-700/60 dark:bg-ink-800">
                                <p className="text-sm font-semibold text-danger-900 dark:text-danger-200">
                                    Demande en cours
                                </p>
                                <p className="text-xs text-ink-600 dark:text-ink-300">
                                    Effective le{' '}
                                    {deletion.effective_at
                                        ? new Date(deletion.effective_at).toLocaleDateString('fr-FR')
                                        : '—'}
                                    .{' '}
                                    {deletion.can_cancel
                                        ? 'Vous pouvez encore annuler tant que le délai n\'est pas écoulé.'
                                        : 'Le délai d\'annulation est dépassé.'}
                                </p>
                                {deletion.can_cancel && (
                                    <Button
                                        variant="secondary"
                                        onClick={cancelDeletion}
                                        disabled={cancellingDelete}
                                    >
                                        {cancellingDelete
                                            ? 'Annulation…'
                                            : 'Annuler ma demande d\'effacement'}
                                    </Button>
                                )}
                            </div>
                        ) : !confirmDelete ? (
                            <Button variant="danger" onClick={() => setConfirmDelete(true)}>
                                Demander l'effacement
                            </Button>
                        ) : (
                            <div className="space-y-3 rounded-xl border border-danger-200 bg-white p-3 dark:border-danger-700/60 dark:bg-ink-800">
                                <label className="flex items-start gap-2 text-xs leading-relaxed text-ink-700 dark:text-ink-200">
                                    <input
                                        type="checkbox"
                                        checked={deleteAck}
                                        onChange={(e) => setDeleteAck(e.target.checked)}
                                        className="mt-0.5 size-4 rounded border-ink-300 text-danger-600 focus:ring-danger-500"
                                    />
                                    <span>
                                        Je comprends que cette demande déclenche un compte à rebours de
                                        30 jours après lequel mon identité sera anonymisée de manière
                                        irréversible.
                                    </span>
                                </label>
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        variant="danger"
                                        onClick={requestDeletion}
                                        disabled={!deleteAck || submittingDelete}
                                    >
                                        {submittingDelete ? 'Envoi…' : 'Confirmer la demande'}
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        onClick={() => {
                                            setConfirmDelete(false);
                                            setDeleteAck(false);
                                        }}
                                    >
                                        Annuler
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function RequestRow({ request }: { request: ExportRow }) {
    const createdAt = new Date(request.created_at).toLocaleString('fr-FR');
    const expiresAt = request.expires_at ? new Date(request.expires_at).toLocaleString('fr-FR') : null;
    const sizeKb = request.archive_size_bytes ? Math.round(request.archive_size_bytes / 1024) : null;

    return (
        <li className="flex flex-col gap-2 px-6 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0">
                <p className="font-mono text-xs font-medium text-ink-900 dark:text-white">
                    {request.id.slice(0, 8)}
                </p>
                <p className="text-[11px] text-ink-500 dark:text-ink-400">
                    Demandé le {createdAt}
                    {expiresAt && request.status === 'ready' && ` · Expire le ${expiresAt}`}
                    {sizeKb && ` · ${sizeKb} Ko`}
                </p>
                {request.failure_reason && (
                    <p className="mt-1 text-[11px] text-danger-700 dark:text-danger-300">
                        {request.failure_reason}
                    </p>
                )}
            </div>
            <div className="flex items-center gap-2">
                <Badge tone={STATUS_TONE[request.status]} size="sm" dot>
                    {request.status_label}
                </Badge>
                {request.is_downloadable && (
                    <a
                        href={`/dashboard/profile/gdpr/export/${request.id}/download`}
                        className="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-brand-700"
                    >
                        <DownloadIcon /> Télécharger
                    </a>
                )}
            </div>
        </li>
    );
}

function ArchiveIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h18v4H3z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M5 7v13h14V7" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M10 12h4" />
        </svg>
    );
}

function DownloadIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
        </svg>
    );
}
