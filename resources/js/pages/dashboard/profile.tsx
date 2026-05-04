import { Badge, Button, Card, CardBody, CardHeader, PageHeader } from '@/components/ui';
import { Link, usePage } from '@inertiajs/react';
import DashboardLayout from './layout';
import { PageProps as InertiaPageProps } from '@inertiajs/core';

interface AuthUser {
    id: number;
    name: string;
    email: string;
    role?: string;
    has_mfa_enrolled?: boolean;
    requires_mfa?: boolean;
}

interface PageProps extends InertiaPageProps {
    auth: { user: AuthUser };
}

export default function Profile() {
    const { props } = usePage<PageProps>();
    const user = props.auth?.user;

    const initials = user
        ? user.name
              .split(' ')
              .map((n) => n[0])
              .join('')
              .toUpperCase()
              .slice(0, 2)
        : '?';

    return (
        <DashboardLayout title="Mon profil" subtitle="">
            <PageHeader
                title="Mon profil"
                subtitle="Vos informations personnelles et options de sécurité"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Mon profil' }]}
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Identité" />
                    <CardBody>
                        <div className="mb-6 flex items-center gap-5">
                            <div className="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-xl font-semibold text-white">
                                {initials}
                            </div>
                            <div className="min-w-0">
                                <h2 className="truncate font-serif text-xl font-medium text-ink-900 dark:text-white">{user?.name ?? '—'}</h2>
                                <p className="truncate font-mono text-sm text-ink-500 dark:text-ink-400">{user?.email ?? '—'}</p>
                                {user?.role && (
                                    <Badge tone="brand" size="sm" className="mt-2">
                                        {user.role}
                                    </Badge>
                                )}
                            </div>
                        </div>

                        <p className="rounded-xl border border-ink-100 bg-ink-50/50 p-4 text-sm text-ink-600 dark:border-ink-700/60 dark:bg-ink-900/40 dark:text-ink-400">
                            Pour modifier votre nom, votre email ou votre rôle, contactez l'administrateur de votre structure.
                            La gestion des comptes est centralisée pour préserver l'intégrité des audits.
                        </p>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Sécurité" subtitle="Authentification à deux facteurs" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row
                                label="2FA TOTP"
                                value={
                                    user?.has_mfa_enrolled ? (
                                        <Badge tone="sage" size="sm">
                                            Activée
                                        </Badge>
                                    ) : user?.requires_mfa ? (
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
                        </dl>

                        {!user?.has_mfa_enrolled && (
                            <Link href="/user/two-factor-authentication" className="mt-4 block">
                                <Button variant="secondary" className="w-full">
                                    Configurer ma 2FA
                                </Button>
                            </Link>
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
            <dd>{value}</dd>
        </div>
    );
}
