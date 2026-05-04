import {
    Badge,
    Button,
    Card,
    EmptyState,
    InterventionStatusBadge,
    PageHeader,
    Pagination,
    TBody,
    THead,
    Table,
    Td,
    Th,
    Tr,
} from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

type Statut = 'planifiee' | 'en_cours' | 'realisee' | 'annulee' | 'non_realisee';

interface Intervention {
    id: number | string;
    initials: string;
    intervenant: string;
    beneficiaire: string;
    structure: string;
    date_heure_debut: string;
    date_heure_fin: string | null;
    duree_minutes: number | null;
    statut: Statut;
    compte_rendu: string | null;
    sync_offline: boolean;
}

interface Props {
    interventions: Intervention[];
    total: number;
    pagination: { current_page: number; last_page: number; per_page: number };
    stats: { planifiees: number; en_cours: number; realisees: number; annulees: number };
    filters: { status: string | null };
}

export default function Interventions({
    interventions = [],
    total = 0,
    pagination = { current_page: 1, last_page: 1, per_page: 20 },
    stats = { planifiees: 0, en_cours: 0, realisees: 0, annulees: 0 },
    filters = { status: null },
}: Partial<Props>) {
    const activeFilter = filters.status;
    return (
        <DashboardLayout title="Interventions" subtitle="Suivi des interventions à domicile">
            <PageHeader
                title="Interventions"
                subtitle={`${total} intervention(s) au total`}
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Interventions' }]}
                actions={
                    <Link href="/interventions/create">
                        <Button leadingIcon={<PlusIcon />}>Nouvelle intervention</Button>
                    </Link>
                }
            />

            {/* Filtres / stats */}
            <div className="mb-5 flex flex-wrap items-center gap-2">
                <FilterChip label="Toutes" count={total} active={!activeFilter} href="/interventions" />
                <FilterChip label="En cours" count={stats.en_cours} active={activeFilter === 'en_cours'} href="/interventions?status=en_cours" />
                <FilterChip label="Planifiées" count={stats.planifiees} active={activeFilter === 'planifiee'} href="/interventions?status=planifiee" />
                <FilterChip label="Réalisées" count={stats.realisees} active={activeFilter === 'realisee'} href="/interventions?status=realisee" />
                <FilterChip label="Annulées" count={stats.annulees} active={activeFilter === 'annulee'} href="/interventions?status=annulee" />
            </div>

            <Card>
                {interventions.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th>Intervenant</Th>
                                <Th>Bénéficiaire</Th>
                                <Th>Date & heure</Th>
                                <Th>Durée</Th>
                                <Th>Statut</Th>
                                <Th>CR</Th>
                                <Th></Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {interventions.map((i) => (
                                <Tr key={i.id}>
                                    <Td>
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                                {i.initials}
                                            </div>
                                            <span className="font-medium text-ink-900 dark:text-white">{i.intervenant}</span>
                                        </div>
                                    </Td>
                                    <Td>{i.beneficiaire}</Td>
                                    <Td className="font-mono text-xs text-ink-600 dark:text-ink-400">{i.date_heure_debut}</Td>
                                    <Td className="font-mono text-xs">
                                        {i.duree_minutes ? `${i.duree_minutes} min` : '—'}
                                    </Td>
                                    <Td>
                                        <InterventionStatusBadge statut={i.statut} />
                                    </Td>
                                    <Td>
                                        <div className="flex items-center gap-1.5">
                                            {i.compte_rendu ? (
                                                <span className="text-sage-600 dark:text-sage-400">✓</span>
                                            ) : (
                                                <span className="text-ink-300 dark:text-ink-600">—</span>
                                            )}
                                            {i.sync_offline && (
                                                <Badge tone="warning" size="xs">
                                                    offline
                                                </Badge>
                                            )}
                                        </div>
                                    </Td>
                                    <Td className="text-right">
                                        <Link
                                            href={`/interventions/${i.id}`}
                                            className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                        >
                                            Voir →
                                        </Link>
                                    </Td>
                                </Tr>
                            ))}
                        </TBody>
                    </Table>
                ) : (
                    <EmptyState
                        title="Aucune intervention"
                        description="Planifiez votre première intervention pour démarrer le suivi terrain."
                        action={
                            <Link href="/interventions/create">
                                <Button>Nouvelle intervention</Button>
                            </Link>
                        }
                    />
                )}
                <div className="px-4 pb-4">
                    <Pagination
                        currentPage={pagination.current_page}
                        lastPage={pagination.last_page}
                        total={total}
                        perPage={pagination.per_page}
                    />
                </div>
            </Card>
        </DashboardLayout>
    );
}

function FilterChip({ label, count, active, href }: { label: string; count: number; active?: boolean; href: string }) {
    return (
        <Link
            href={href}
            preserveState
            className={
                active
                    ? 'inline-flex items-center gap-1.5 rounded-lg bg-ink-900 px-3.5 py-1.5 text-xs font-semibold text-white dark:bg-white dark:text-ink-900'
                    : 'inline-flex items-center gap-1.5 rounded-lg border border-ink-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-ink-600 hover:border-ink-300 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-300 dark:hover:border-ink-500'
            }
        >
            {label}
            <span className={active ? 'text-white/70 dark:text-ink-900/60' : 'text-ink-400 dark:text-ink-500'}>({count})</span>
        </Link>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}
