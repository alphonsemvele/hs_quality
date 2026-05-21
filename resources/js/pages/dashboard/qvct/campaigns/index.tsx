import { Badge, Button, Card, EmptyState, KpiCard, PageHeader, Pagination } from '@/components/ui';
import { useCan } from '@/lib/can';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Campaign {
    id: string;
    title: string | null;
    status: 'draft' | 'active' | 'closed' | 'archived';
    opens_at: string;
    closes_at: string | null;
    target_team: string | null;
    questionnaire: { id: string; title: string; frequency: string | null } | null;
    responses_count: number;
    weak_signals_count: number;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    campaigns: Paginated<Campaign>;
}

const STATUS_TONE: Record<Campaign['status'], 'neutral' | 'sage' | 'brand' | 'warning'> = {
    draft: 'neutral',
    active: 'sage',
    closed: 'brand',
    archived: 'neutral',
};

const STATUS_LABEL: Record<Campaign['status'], string> = {
    draft: 'Brouillon',
    active: 'En cours',
    closed: 'Clôturée',
    archived: 'Archivée',
};

const FREQUENCY_LABELS: Record<string, string> = {
    weekly: 'Hebdo.',
    monthly: 'Mensuelle',
    quarterly: 'Trim.',
    biannual: 'Semest.',
    annual: 'Annuelle',
};

export default function QvctCampaignsIndex({ campaigns }: Props) {
    const canManage = useCan('qvct.manage');
    const list = campaigns?.data ?? [];
    const meta = campaigns;

    const totalResponses = list.reduce((sum, c) => sum + c.responses_count, 0);
    const totalSignals = list.reduce((sum, c) => sum + c.weak_signals_count, 0);
    const activeCount = list.filter((c) => c.status === 'active').length;
    const closedCount = list.filter((c) => c.status === 'closed').length;

    return (
        <DashboardLayout title="Campagnes QVCT" subtitle="Suivi des collectes de bien-être">
            <PageHeader
                title="Campagnes QVCT"
                subtitle={`${meta?.total ?? list.length} campagne${(meta?.total ?? list.length) !== 1 ? 's' : ''}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Campagnes' },
                ]}
                actions={
                    canManage ? (
                        <Link href="/qvct/campaigns/create">
                            <Button leadingIcon={<PlusIcon />}>Lancer une campagne</Button>
                        </Link>
                    ) : null
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="En cours" value={activeCount} tone="sage" />
                <KpiCard label="Clôturées" value={closedCount} tone="brand" />
                <KpiCard label="Réponses totales" value={totalResponses} tone="neutral" />
                <KpiCard label="Signaux faibles" value={totalSignals} tone="warning" />
            </div>

            {list.length > 0 ? (
                <ul className="space-y-3">
                    {list.map((c) => {
                        const displayTitle =
                            c.title ||
                            new Date(c.opens_at).toLocaleDateString('fr-FR', {
                                month: 'long',
                                year: 'numeric',
                            });

                        return (
                            <Card key={c.id} className="transition-shadow hover:shadow-md">
                                <div className="flex items-start gap-4 p-5">
                                    <div
                                        className={cn(
                                            'flex size-11 shrink-0 items-center justify-center rounded-xl text-sm font-semibold',
                                            c.status === 'active'
                                                ? 'bg-sage-50 text-sage-700 dark:bg-sage-900/30 dark:text-sage-300'
                                                : 'bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300',
                                        )}
                                    >
                                        {c.responses_count}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link
                                                href={`/qvct/campaigns/${c.id}`}
                                                className="text-sm font-semibold text-ink-900 hover:text-brand-700 dark:text-white dark:hover:text-brand-300"
                                            >
                                                {displayTitle}
                                            </Link>
                                            <Badge tone={STATUS_TONE[c.status]} size="xs">
                                                {STATUS_LABEL[c.status]}
                                            </Badge>
                                            {c.weak_signals_count > 0 && (
                                                <Badge tone="warning" size="xs">
                                                    {c.weak_signals_count} signal
                                                    {c.weak_signals_count > 1 ? 'aux' : ''}
                                                </Badge>
                                            )}
                                        </div>
                                        <div className="mt-1 flex flex-wrap gap-3 text-xs text-ink-500 dark:text-ink-400">
                                            {c.questionnaire && (
                                                <span>
                                                    {c.questionnaire.title}
                                                    {c.questionnaire.frequency &&
                                                        ` · ${FREQUENCY_LABELS[c.questionnaire.frequency] ?? c.questionnaire.frequency}`}
                                                </span>
                                            )}
                                            <span>
                                                {new Date(c.opens_at).toLocaleDateString('fr-FR', {
                                                    dateStyle: 'medium',
                                                })}
                                                {c.closes_at &&
                                                    ` → ${new Date(c.closes_at).toLocaleDateString('fr-FR', { dateStyle: 'medium' })}`}
                                            </span>
                                            {c.target_team && <span>Équipe : {c.target_team}</span>}
                                            <span>
                                                {c.responses_count} réponse{c.responses_count !== 1 ? 's' : ''}
                                            </span>
                                        </div>
                                    </div>
                                    <Link
                                        href={`/qvct/campaigns/${c.id}`}
                                        className="shrink-0 text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                    >
                                        Voir →
                                    </Link>
                                </div>
                            </Card>
                        );
                    })}
                </ul>
            ) : (
                <Card>
                    <EmptyState
                        title="Aucune campagne"
                        description="Lancez votre première campagne QVCT pour collecter les retours anonymes de vos équipes."
                        action={
                            canManage ? (
                                <Link href="/qvct/campaigns/create">
                                    <Button size="sm">Lancer une campagne</Button>
                                </Link>
                            ) : null
                        }
                    />
                </Card>
            )}

            <Pagination
                currentPage={meta?.current_page ?? 1}
                lastPage={meta?.last_page ?? 1}
                total={meta?.total ?? list.length}
                perPage={meta?.per_page ?? 20}
            />
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
