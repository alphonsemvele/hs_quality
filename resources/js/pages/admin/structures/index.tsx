import { Badge, Button, Card, EmptyState, PageHeader, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../dashboard/layout';

interface StructureSummary {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    tier: string;
    tier_label: string;
    status: string;
    status_label: string;
    created_at: string | null;
}

interface PaginatedStructures {
    data: StructureSummary[];
    total: number;
}

interface Props {
    structures: PaginatedStructures;
}

export default function StructuresIndex({ structures }: Props) {
    const list = structures?.data ?? [];

    return (
        <DashboardLayout title="Structures (administration plateforme)" subtitle="">
            <PageHeader
                title="Structures"
                subtitle={`${structures?.total ?? list.length} tenants provisionnés sur la plateforme`}
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Structures (admin)' }]}
                actions={
                    <Link href="/admin/structures/create">
                        <Button leadingIcon={<PlusIcon />}>Nouvelle structure</Button>
                    </Link>
                }
            />

            <Card>
                {list.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th hint="Identifiant court interne (slug) du tenant, utilisé dans les URLs d'administration et les exports comptables. Doit rester stable dans le temps.">
                                    Code
                                </Th>
                                <Th hint="Raison sociale de la structure, telle qu'elle apparaît dans les conventions, sur les factures et dans les exports HAS.">
                                    Nom
                                </Th>
                                <Th hint="Catégorie d'établissement médico-social : SAAD (Service d'Aide à Domicile), SSIAD (Soins Infirmiers À Domicile), SPASAD (mixte), ESAD (Équipe Spécialisée Alzheimer), CCAS (Centre Communal d'Action Sociale). Détermine les modules métier activés pour ce tenant.">
                                    Type
                                </Th>
                                <Th hint="Niveau d'abonnement plateforme : Starter, Pro, Premium. Détermine les quotas (bénéficiaires, utilisateurs, stockage S3) et les modules optionnels (QVCT, audits HAS, API mobile).">
                                    Tier
                                </Th>
                                <Th hint="État opérationnel du tenant : Actif (en production), Suspendu (impayé / non-conformité), Archivé (résilié). Un tenant suspendu bloque les connexions de ses utilisateurs.">
                                    Statut
                                </Th>
                                <Th></Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {list.map((s) => (
                                <Tr key={s.id}>
                                    <Td className="font-mono text-xs text-ink-600">{s.code}</Td>
                                    <Td className="font-medium text-ink-900">{s.name}</Td>
                                    <Td>
                                        <Badge tone="brand" size="sm">
                                            {s.type_label || s.type}
                                        </Badge>
                                    </Td>
                                    <Td>
                                        <Badge tone="sage" size="sm">
                                            {s.tier_label || s.tier}
                                        </Badge>
                                    </Td>
                                    <Td>
                                        {s.status === 'active' ? (
                                            <Badge tone="sage" size="sm" dot>
                                                {s.status_label}
                                            </Badge>
                                        ) : (
                                            <Badge tone="warning" size="sm">
                                                {s.status_label}
                                            </Badge>
                                        )}
                                    </Td>
                                    <Td className="text-right">
                                        <Link
                                            href={`/admin/structures/${s.id}`}
                                            className="text-sm font-medium text-brand-600 hover:text-brand-700"
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
                        title="Aucune structure"
                        description="Créez le premier tenant pour démarrer."
                        action={
                            <Link href="/admin/structures/create">
                                <Button>Créer une structure</Button>
                            </Link>
                        }
                    />
                )}
            </Card>
        </DashboardLayout>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}
