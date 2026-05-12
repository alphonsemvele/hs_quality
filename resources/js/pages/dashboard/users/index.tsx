import { QuickAddInviteModal } from '@/components/quick-add';
import { Badge, Button, Card, EmptyState, PageHeader, Pagination, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface UserSummary {
    id: number;
    name: string;
    email: string;
    type: string;
    type_label: string;
    status: string;
    has_mfa_enrolled: boolean;
    requires_mfa: boolean;
    pending_invite: boolean;
}

interface PaginatedUsers {
    data: UserSummary[];
    total: number;
    current_page?: number;
    last_page?: number;
}

interface Props {
    users: PaginatedUsers;
    roles: { value: string; label: string }[];
}

export default function UsersIndex({ users }: Props) {
    const list = users?.data ?? [];
    const canManage = useCan('users.manage');
    const [showInvite, setShowInvite] = useState(false);

    return (
        <DashboardLayout title="Utilisateurs" subtitle="Gestion des membres de votre structure">
            <PageHeader
                title="Utilisateurs"
                subtitle={`${users?.total ?? list.length} membres dans votre structure`}
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Utilisateurs' }]}
                actions={
                    canManage ? (
                        <Button leadingIcon={<PlusIcon />} onClick={() => setShowInvite(true)}>
                            Inviter un utilisateur
                        </Button>
                    ) : null
                }
            />

            <Card>
                {list.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th>Nom</Th>
                                <Th>Rôle</Th>
                                <Th>Email</Th>
                                <Th>Statut</Th>
                                <Th>2FA</Th>
                                <Th></Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {list.map((u) => (
                                <Tr key={u.id}>
                                    <Td>
                                        <div className="flex items-center gap-3">
                                            <Avatar name={u.name} />
                                            <div>
                                                <p className="font-medium text-ink-900 dark:text-white">{u.name}</p>
                                                {u.pending_invite && (
                                                    <Badge tone="warning" size="xs" className="mt-0.5">
                                                        Invitation en attente
                                                    </Badge>
                                                )}
                                            </div>
                                        </div>
                                    </Td>
                                    <Td>
                                        <Badge tone="brand" size="sm">
                                            {u.type_label || u.type}
                                        </Badge>
                                    </Td>
                                    <Td className="font-mono text-xs text-ink-500 dark:text-ink-400">{u.email}</Td>
                                    <Td>
                                        {u.status === 'active' ? (
                                            <Badge tone="sage" size="sm" dot>
                                                Actif
                                            </Badge>
                                        ) : (
                                            <Badge tone="neutral" size="sm">
                                                {u.status}
                                            </Badge>
                                        )}
                                    </Td>
                                    <Td>
                                        {u.requires_mfa && !u.has_mfa_enrolled ? (
                                            <Badge tone="danger" size="sm">
                                                Non enrôlée
                                            </Badge>
                                        ) : u.has_mfa_enrolled ? (
                                            <Badge tone="sage" size="sm">
                                                ✓
                                            </Badge>
                                        ) : (
                                            <Badge tone="neutral" size="sm">
                                                Optionnelle
                                            </Badge>
                                        )}
                                    </Td>
                                    <Td className="text-right">
                                        <Link
                                            href={`/users/${u.id}`}
                                            className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                        >
                                            Détail →
                                        </Link>
                                    </Td>
                                </Tr>
                            ))}
                        </TBody>
                    </Table>
                ) : (
                    <EmptyState
                        title="Aucun utilisateur"
                        description="Invitez les premiers membres de votre équipe (coordinateurs, intervenants, référent qualité)."
                        action={
                            canManage ? (
                                <Button onClick={() => setShowInvite(true)}>Inviter un utilisateur</Button>
                            ) : undefined
                        }
                    />
                )}
                <div className="px-4 pb-4">
                    <Pagination
                        currentPage={users?.current_page ?? 1}
                        lastPage={users?.last_page ?? 1}
                        total={users?.total ?? list.length}
                        perPage={25}
                    />
                </div>
            </Card>

            <QuickAddInviteModal open={showInvite} onClose={() => setShowInvite(false)} />
        </DashboardLayout>
    );
}

function Avatar({ name }: { name: string }) {
    const initials = name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
    return (
        <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-semibold text-white">
            {initials || '?'}
        </div>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}
