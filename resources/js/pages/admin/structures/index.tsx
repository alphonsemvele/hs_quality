import { Badge, Button, Card, EmptyState, PageHeader, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DashboardLayout from '../../dashboard/layout';

type SortKey = 'code' | 'name' | 'type' | 'tier' | 'status';
type SortDir = 'asc' | 'desc';

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
    const [search, setSearch] = useState('');
    const [sortKey, setSortKey] = useState<SortKey>('name');
    const [sortDir, setSortDir] = useState<SortDir>('asc');

    const filteredSorted = useMemo(() => {
        const needle = search.trim().toLowerCase();
        const filtered = list.filter((s) => {
            if (!needle) return true;
            return (
                s.code.toLowerCase().includes(needle) ||
                s.name.toLowerCase().includes(needle) ||
                (s.type_label ?? s.type).toLowerCase().includes(needle) ||
                (s.tier_label ?? s.tier).toLowerCase().includes(needle)
            );
        });
        const factor = sortDir === 'asc' ? 1 : -1;
        return filtered.slice().sort((a, b) => {
            const av = (a[sortKey] ?? '') as string;
            const bv = (b[sortKey] ?? '') as string;
            return factor * av.localeCompare(bv, 'fr', { sensitivity: 'base' });
        });
    }, [list, search, sortKey, sortDir]);

    const toggleSort = (key: SortKey): void => {
        if (sortKey === key) {
            setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
        } else {
            setSortKey(key);
            setSortDir('asc');
        }
    };

    const sortIndicator = (key: SortKey): string => {
        if (sortKey !== key) return '';
        return sortDir === 'asc' ? ' ↑' : ' ↓';
    };

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

            {list.length > 0 && (
                <Card className="mb-4">
                    <div className="flex flex-wrap items-center gap-3 p-4">
                        <div className="relative flex-1 min-w-[16rem]">
                            <input
                                type="search"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher par code, nom, type ou tier…"
                                className="w-full rounded-lg border border-ink-200 bg-white pl-9 pr-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                            />
                            <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-400">
                                <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                                    <circle cx="11" cy="11" r="8" />
                                    <path d="M21 21l-4.35-4.35" strokeLinecap="round" />
                                </svg>
                            </span>
                        </div>
                        <p className="text-xs text-ink-500 dark:text-ink-400">
                            {filteredSorted.length} affiché{filteredSorted.length > 1 ? 's' : ''} sur {list.length}
                        </p>
                    </div>
                </Card>
            )}

            <Card>
                {list.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th hint="Identifiant court interne (slug) du tenant, utilisé dans les URLs d'administration et les exports comptables. Doit rester stable dans le temps.">
                                    <button type="button" onClick={() => toggleSort('code')} className="font-semibold uppercase tracking-wider hover:text-ink-900 dark:hover:text-white">
                                        Code{sortIndicator('code')}
                                    </button>
                                </Th>
                                <Th hint="Raison sociale de la structure, telle qu'elle apparaît dans les conventions, sur les factures et dans les exports HAS.">
                                    <button type="button" onClick={() => toggleSort('name')} className="font-semibold uppercase tracking-wider hover:text-ink-900 dark:hover:text-white">
                                        Nom{sortIndicator('name')}
                                    </button>
                                </Th>
                                <Th hint="Catégorie d'établissement médico-social : SAAD (Service d'Aide à Domicile), SSIAD (Soins Infirmiers À Domicile), SPASAD (mixte), ESAD (Équipe Spécialisée Alzheimer), CCAS (Centre Communal d'Action Sociale). Détermine les modules métier activés pour ce tenant.">
                                    <button type="button" onClick={() => toggleSort('type')} className="font-semibold uppercase tracking-wider hover:text-ink-900 dark:hover:text-white">
                                        Type{sortIndicator('type')}
                                    </button>
                                </Th>
                                <Th hint="Niveau d'abonnement plateforme : Starter, Pro, Premium. Détermine les quotas (bénéficiaires, utilisateurs, stockage S3) et les modules optionnels (QVCT, audits HAS, API mobile).">
                                    <button type="button" onClick={() => toggleSort('tier')} className="font-semibold uppercase tracking-wider hover:text-ink-900 dark:hover:text-white">
                                        Tier{sortIndicator('tier')}
                                    </button>
                                </Th>
                                <Th hint="État opérationnel du tenant : Actif (en production), Suspendu (impayé / non-conformité), Archivé (résilié). Un tenant suspendu bloque les connexions de ses utilisateurs.">
                                    <button type="button" onClick={() => toggleSort('status')} className="font-semibold uppercase tracking-wider hover:text-ink-900 dark:hover:text-white">
                                        Statut{sortIndicator('status')}
                                    </button>
                                </Th>
                                <Th></Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {filteredSorted.map((s) => (
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
