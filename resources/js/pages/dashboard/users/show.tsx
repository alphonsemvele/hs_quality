import { Badge, Button, Card, CardBody, CardHeader, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link, router, usePage } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface UserDetail {
    id: number;
    first_name: string;
    last_name: string;
    name: string;
    email: string;
    type: string;
    type_label: string;
    status: string;
    has_mfa_enrolled: boolean;
    requires_mfa: boolean;
    pending_invite: boolean;
    phone: string | null;
    employee_number: string | null;
    created_at: string | null;
}

interface PageFlash {
    invitation_url?: string;
}

export default function ShowUser({ user }: { user: UserDetail }) {
    const { props } = usePage<{ flash?: PageFlash }>();
    const invitationUrl = props.flash?.invitation_url;
    const canManage = useCan('users.manage');

    const isActive = user.status === 'active';

    const toggleStatus = () => {
        const path = isActive ? `/users/${user.id}/deactivate` : `/users/${user.id}/reactivate`;
        const message = isActive ? 'Désactiver cet utilisateur ?' : 'Réactiver cet utilisateur ?';
        if (confirm(message)) {
            router.post(path);
        }
    };

    return (
        <DashboardLayout title={user.name} subtitle="">
            <PageHeader
                title={user.name}
                subtitle={user.type_label}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Utilisateurs', href: '/users' },
                    { label: user.name },
                ]}
                actions={
                    canManage ? (
                        <Button variant={isActive ? 'secondary' : 'primary'} onClick={toggleStatus}>
                            {isActive ? 'Désactiver' : 'Réactiver'}
                        </Button>
                    ) : null
                }
            />

            {invitationUrl && (
                <div className="mb-5 rounded-2xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                    <p className="text-sm font-semibold text-brand-700 dark:text-brand-300">Lien d'invitation généré</p>
                    <p className="mt-1 text-xs text-brand-700 dark:text-brand-300">
                        Communiquez ce lien à l'utilisateur. Il expirera après usage.
                    </p>
                    <div className="mt-2 flex items-center gap-2">
                        <code className="flex-1 truncate rounded-lg bg-white px-3 py-2 font-mono text-xs text-ink-700 dark:bg-ink-800 dark:text-ink-300">
                            {invitationUrl}
                        </code>
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => {
                                navigator.clipboard.writeText(invitationUrl);
                            }}
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
                            <Field label="Prénom" value={user.first_name} />
                            <Field label="Nom" value={user.last_name} />
                            <Field label="Email" value={user.email} mono />
                            <Field label="Téléphone" value={user.phone ?? '—'} mono />
                            <Field label="N° employé" value={user.employee_number ?? '—'} mono />
                            <Field label="Rôle" value={user.type_label} />
                        </dl>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Sécurité & accès" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row
                                label="Statut compte"
                                value={
                                    isActive ? (
                                        <Badge tone="sage" size="sm" dot>
                                            Actif
                                        </Badge>
                                    ) : (
                                        <Badge tone="neutral" size="sm">
                                            Désactivé
                                        </Badge>
                                    )
                                }
                            />
                            <Row
                                label="Invitation"
                                value={
                                    user.pending_invite ? (
                                        <Badge tone="warning" size="sm">
                                            En attente
                                        </Badge>
                                    ) : (
                                        <Badge tone="sage" size="sm">
                                            Acceptée
                                        </Badge>
                                    )
                                }
                            />
                            <Row
                                label="2FA TOTP"
                                value={
                                    user.has_mfa_enrolled ? (
                                        <Badge tone="sage" size="sm">
                                            Activée
                                        </Badge>
                                    ) : user.requires_mfa ? (
                                        <Badge tone="danger" size="sm">
                                            Non enrôlée
                                        </Badge>
                                    ) : (
                                        <Badge tone="neutral" size="sm">
                                            Optionnelle
                                        </Badge>
                                    )
                                }
                            />
                            {user.created_at && (
                                <Row
                                    label="Créé le"
                                    value={<span className="font-mono text-xs">{user.created_at.slice(0, 10)}</span>}
                                />
                            )}
                        </dl>
                    </CardBody>
                </Card>
            </div>

            <div className="mt-5">
                <Link href="/users" className="text-sm text-ink-500 hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                    ← Retour à la liste
                </Link>
            </div>
        </DashboardLayout>
    );
}

function Field({ label, value, mono }: { label: string; value: string; mono?: boolean }) {
    return (
        <div>
            <dt className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</dt>
            <dd className={mono ? 'mt-1 font-mono text-sm text-ink-900 dark:text-white' : 'mt-1 text-sm font-medium text-ink-900 dark:text-white'}>{value}</dd>
        </div>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</dt>
            <dd>{value}</dd>
        </div>
    );
}
