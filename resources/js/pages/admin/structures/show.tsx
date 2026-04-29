import { Badge, Button, Card, CardBody, CardHeader, PageHeader } from '@/components/ui';
import { Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../dashboard/layout';

interface StructureDetail {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    tier: string;
    tier_label: string;
    status: string;
    status_label: string;
    address: string | null;
    siret: string | null;
    created_at: string | null;
    updated_at: string | null;
}

interface PageFlash {
    password_reset_url?: string;
}

export default function ShowStructure({ structure }: { structure: StructureDetail }) {
    const { props } = usePage<{ flash?: PageFlash }>();
    const passwordResetUrl = props.flash?.password_reset_url;

    const isActive = structure.status === 'active';

    const toggleStatus = () => {
        const path = isActive ? `/admin/structures/${structure.id}/suspend` : `/admin/structures/${structure.id}/reactivate`;
        const message = isActive ? 'Suspendre cette structure ?' : 'Réactiver cette structure ?';
        if (confirm(message)) {
            router.post(path);
        }
    };

    const remove = () => {
        if (confirm("Supprimer définitivement cette structure ? L'opération est irréversible.")) {
            router.delete(`/admin/structures/${structure.id}`);
        }
    };

    return (
        <DashboardLayout title={structure.name} subtitle="">
            <PageHeader
                title={structure.name}
                subtitle={`${structure.type_label} · ${structure.tier_label}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Structures (admin)', href: '/admin/structures' },
                    { label: structure.name },
                ]}
                actions={
                    <>
                        <Button variant={isActive ? 'secondary' : 'primary'} onClick={toggleStatus}>
                            {isActive ? 'Suspendre' : 'Réactiver'}
                        </Button>
                        <Button variant="danger" onClick={remove}>
                            Supprimer
                        </Button>
                    </>
                }
            />

            {passwordResetUrl && (
                <div className="mb-5 rounded-2xl border border-brand-200 bg-brand-50 p-4">
                    <p className="text-sm font-semibold text-brand-700">Lien de réinitialisation du dirigeant</p>
                    <p className="mt-1 text-xs text-brand-700">
                        Communiquez ce lien au dirigeant initial. Il définira son mot de passe puis configurera sa 2FA.
                    </p>
                    <div className="mt-2 flex items-center gap-2">
                        <code className="flex-1 truncate rounded-lg bg-white px-3 py-2 font-mono text-xs text-ink-700">
                            {passwordResetUrl}
                        </code>
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => navigator.clipboard.writeText(passwordResetUrl)}
                        >
                            Copier
                        </Button>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Identité" />
                    <CardBody>
                        <dl className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Field label="Code" value={structure.code} mono />
                            <Field label="Raison sociale" value={structure.name} />
                            <Field label="Type" value={structure.type_label} />
                            <Field label="Tier" value={structure.tier_label} />
                            <Field label="SIRET" value={structure.siret ?? '—'} mono />
                            <Field label="Adresse" value={structure.address ?? '—'} />
                        </dl>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Statut" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row
                                label="État"
                                value={
                                    isActive ? (
                                        <Badge tone="sage" size="sm" dot>
                                            {structure.status_label}
                                        </Badge>
                                    ) : (
                                        <Badge tone="warning" size="sm">
                                            {structure.status_label}
                                        </Badge>
                                    )
                                }
                            />
                            {structure.created_at && (
                                <Row
                                    label="Créée le"
                                    value={<span className="font-mono text-xs">{structure.created_at.slice(0, 10)}</span>}
                                />
                            )}
                            {structure.updated_at && (
                                <Row
                                    label="Mise à jour"
                                    value={<span className="font-mono text-xs">{structure.updated_at.slice(0, 10)}</span>}
                                />
                            )}
                        </dl>
                    </CardBody>
                </Card>
            </div>

            <div className="mt-5">
                <Link href="/admin/structures" className="text-sm text-ink-500 hover:text-brand-600">
                    ← Retour à la liste
                </Link>
            </div>
        </DashboardLayout>
    );
}

function Field({ label, value, mono }: { label: string; value: string; mono?: boolean }) {
    return (
        <div>
            <dt className="text-[11px] font-semibold uppercase tracking-wider text-ink-500">{label}</dt>
            <dd className={mono ? 'mt-1 font-mono text-sm text-ink-900' : 'mt-1 text-sm font-medium text-ink-900'}>{value}</dd>
        </div>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-xs font-semibold uppercase tracking-wider text-ink-500">{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}
