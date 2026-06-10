import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link, router } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface WeakSignal {
    id: string;
}

interface Campaign {
    id: string;
    title: string;
    status: string;
    opens_at: string | null;
    closes_at: string | null;
    closed_at: string | null;
    target_team: string | null;
    questionnaire?: { id: string; title: string; frequency: string } | null;
    // Eloquent serializes the launchedBy relation under `launched_by`,
    // overwriting the FK; falls back to the integer FK when not loaded.
    launched_by?: { first_name: string; last_name: string } | number | null;
    weak_signals?: WeakSignal[];
}

interface Props {
    campaign: Campaign;
    response_count: number;
}

const FREQUENCY_LABELS: Record<string, string> = {
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    quarterly: 'Trimestrielle',
};

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

export default function QvctCampaignShow({ campaign, response_count }: Props) {
    const canManage = useCan('qvct.manage');
    const meta = STATUS_META[campaign.status] ?? { label: campaign.status, tone: 'neutral' as const };
    const launcher = typeof campaign.launched_by === 'object' && campaign.launched_by !== null ? campaign.launched_by : null;
    const launcherName = launcher ? `${launcher.first_name} ${launcher.last_name}`.trim() : '—';
    const weakSignals = campaign.weak_signals ?? [];
    const isActive = campaign.status === 'active';

    const close = () => {
        if (!window.confirm('Clôturer cette campagne ? La détection des signaux faibles sera déclenchée et les réponses ne seront plus acceptées.')) {
            return;
        }
        router.post(`/qvct/campaigns/${campaign.id}/close`, undefined, { preserveScroll: true });
    };

    return (
        <DashboardLayout title={campaign.title} subtitle="Campagne QVCT">
            <PageHeader
                title={campaign.title}
                subtitle={`${formatDate(campaign.opens_at)} → ${formatDate(campaign.closes_at)}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Campagnes', href: '/qvct/campaigns' },
                    { label: campaign.title },
                ]}
                actions={
                    <>
                        <Badge tone={meta.tone} size="sm" dot>
                            {meta.label}
                        </Badge>
                        {canManage && isActive && <Button onClick={close}>Clôturer la campagne</Button>}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Synthèse" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row
                                label="Questionnaire"
                                value={
                                    campaign.questionnaire ? (
                                        <Link
                                            href={`/qvct/questionnaires/${campaign.questionnaire.id}`}
                                            className="text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                        >
                                            {campaign.questionnaire.title}
                                        </Link>
                                    ) : (
                                        '—'
                                    )
                                }
                            />
                            <Row
                                label="Cadence"
                                value={
                                    campaign.questionnaire
                                        ? (FREQUENCY_LABELS[campaign.questionnaire.frequency] ?? campaign.questionnaire.frequency)
                                        : '—'
                                }
                            />
                            <Row label="Ouverture" value={formatDate(campaign.opens_at)} />
                            <Row label="Clôture prévue" value={formatDate(campaign.closes_at)} />
                            {campaign.closed_at && <Row label="Clôturée le" value={formatDate(campaign.closed_at)} />}
                            <Row label="Équipe ciblée" value={campaign.target_team ? campaign.target_team : 'Toute la structure'} />
                            <Row label="Lancée par" value={launcherName} />
                            <Row label="Réponses reçues" value={`${response_count}`} />
                        </dl>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Signaux faibles" subtitle={`${weakSignals.length} détecté(s)`} />
                    <CardBody>
                        {weakSignals.length > 0 ? (
                            <div className="space-y-3">
                                <p className="text-sm text-ink-600 dark:text-ink-300">
                                    {weakSignals.length} signal(aux) faible(s) ont été détectés à la clôture de cette campagne.
                                </p>
                                <Link href="/qvct/weak-signals">
                                    <Button variant="secondary" size="sm">
                                        Voir les signaux faibles
                                    </Button>
                                </Link>
                            </div>
                        ) : (
                            <EmptyState
                                title="Aucun signal faible"
                                description={
                                    isActive
                                        ? 'Les signaux faibles sont détectés automatiquement à la clôture de la campagne.'
                                        : "Aucun signal faible n'a été détecté pour cette campagne."
                                }
                            />
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
            <dt className="text-xs font-semibold tracking-wider text-ink-500 uppercase dark:text-ink-400">{label}</dt>
            <dd className="text-sm font-medium text-ink-900 dark:text-white">{value}</dd>
        </div>
    );
}
