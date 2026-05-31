import { Badge, Button, Card, EmptyState, PageHeader, Pagination, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Campaign {
    id: string;
    title: string;
    status: string;
    opens_at: string | null;
    closes_at: string | null;
    target_team: string | null;
    responses_count?: number;
    weak_signals_count?: number;
    questionnaire?: { id: string; title: string; frequency: string } | null;
}

interface Paginated {
    data: Campaign[];
    current_page?: number;
    last_page?: number;
    total?: number;
}

const STATUS_META: Record<string, { label: string; tone: 'sage' | 'warning' | 'brand' | 'neutral' }> = {
    draft: { label: 'Brouillon', tone: 'warning' },
    active: { label: 'En cours', tone: 'sage' },
    closed: { label: 'Clôturée', tone: 'brand' },
    archived: { label: 'Archivée', tone: 'neutral' },
};

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR');
}

export default function QvctCampaignsIndex({ campaigns }: { campaigns: Paginated }) {
    const list = campaigns?.data ?? [];
    const canManage = useCan('qvct.manage');

    return (
        <DashboardLayout title="Campagnes QVCT" subtitle="Baromètres lancés">
            <PageHeader
                title="Campagnes QVCT"
                subtitle="Suivez les baromètres envoyés aux équipes et les signaux faibles détectés à leur clôture."
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'QVCT', href: '/qvct' }, { label: 'Campagnes' }]}
                actions={
                    canManage ? (
                        <Link href="/qvct/campaigns/create">
                            <Button leadingIcon={<PlusIcon />}>Nouvelle campagne</Button>
                        </Link>
                    ) : null
                }
            />

            <Card>
                {list.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th>Campagne</Th>
                                <Th>Questionnaire</Th>
                                <Th>Statut</Th>
                                <Th>Période</Th>
                                <Th>Réponses</Th>
                                <Th>Signaux</Th>
                                <Th></Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {list.map((c) => {
                                const meta = STATUS_META[c.status] ?? { label: c.status, tone: 'neutral' as const };
                                return (
                                    <Tr key={c.id}>
                                        <Td>
                                            <Link
                                                href={`/qvct/campaigns/${c.id}`}
                                                className="font-medium text-ink-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400"
                                            >
                                                {c.title}
                                            </Link>
                                        </Td>
                                        <Td className="text-sm text-ink-600 dark:text-ink-300">{c.questionnaire?.title ?? '—'}</Td>
                                        <Td>
                                            <Badge tone={meta.tone} size="sm" dot>
                                                {meta.label}
                                            </Badge>
                                        </Td>
                                        <Td className="font-mono text-xs text-ink-500 dark:text-ink-400">
                                            {formatDate(c.opens_at)} → {formatDate(c.closes_at)}
                                        </Td>
                                        <Td className="text-sm text-ink-600 dark:text-ink-300">{c.responses_count ?? 0}</Td>
                                        <Td className="text-sm text-ink-600 dark:text-ink-300">
                                            {c.weak_signals_count && c.weak_signals_count > 0 ? (
                                                <Badge tone="danger" size="sm">
                                                    {c.weak_signals_count}
                                                </Badge>
                                            ) : (
                                                '0'
                                            )}
                                        </Td>
                                        <Td className="text-right">
                                            <Link
                                                href={`/qvct/campaigns/${c.id}`}
                                                className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                            >
                                                Détail →
                                            </Link>
                                        </Td>
                                    </Tr>
                                );
                            })}
                        </TBody>
                    </Table>
                ) : (
                    <EmptyState
                        title="Aucune campagne"
                        description="Lancez une campagne à partir d'un questionnaire actif pour recueillir le ressenti de vos équipes."
                        action={
                            canManage ? (
                                <Link href="/qvct/campaigns/create">
                                    <Button>Nouvelle campagne</Button>
                                </Link>
                            ) : undefined
                        }
                    />
                )}
                {list.length > 0 && (
                    <div className="px-4 pb-4">
                        <Pagination
                            currentPage={campaigns?.current_page ?? 1}
                            lastPage={campaigns?.last_page ?? 1}
                            total={campaigns?.total ?? list.length}
                            perPage={20}
                        />
                    </div>
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
