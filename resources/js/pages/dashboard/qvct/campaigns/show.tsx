import { Badge, Button, Card, CardBody, CardHeader, KpiCard, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link, router } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface WeakSignal {
    id: string;
    signal_type: string;
    team_tag: string | null;
    severity: number | null;
    details: Record<string, unknown> | null;
    acknowledged_at: string | null;
    created_at: string;
}

interface Campaign {
    id: string;
    title: string | null;
    status: 'draft' | 'active' | 'closed' | 'archived';
    opens_at: string;
    closes_at: string | null;
    closed_at: string | null;
    target_team: string | null;
    questionnaire: {
        id: string;
        title: string;
        frequency: string | null;
        questions: Array<{ key: string; label: string; scale: string; category: string }>;
    } | null;
    launched_by: { id: number; first_name: string; last_name: string } | null;
    weak_signals: WeakSignal[];
}

interface Props {
    campaign: Campaign;
    response_count: number;
}

const STATUS_TONE: Record<string, 'neutral' | 'sage' | 'brand' | 'warning'> = {
    draft: 'neutral',
    active: 'sage',
    closed: 'brand',
    archived: 'neutral',
};

const STATUS_LABEL: Record<string, string> = {
    draft: 'Brouillon',
    active: 'En cours',
    closed: 'Clôturée',
    archived: 'Archivée',
};

const SIGNAL_TYPE_LABELS: Record<string, string> = {
    baisse_morale: 'Baisse de morale',
    surcharge: 'Surcharge de travail',
    conflit_relationnel: 'Conflit relationnel',
    isolement_professionnel: 'Isolement professionnel',
};

const FREQUENCY_LABELS: Record<string, string> = {
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    quarterly: 'Trimestrielle',
    biannual: 'Semestrielle',
    annual: 'Annuelle',
};

function severityTone(s: number | null): 'neutral' | 'warning' | 'danger' {
    if (!s || s < 4) return 'neutral';
    if (s < 7) return 'warning';
    return 'danger';
}

export default function ShowQvctCampaign({ campaign, response_count }: Props) {
    const canManage = useCan('qvct.manage');
    const isActive = campaign.status === 'active';
    const signals = campaign.weak_signals ?? [];
    const questions = campaign.questionnaire?.questions ?? [];
    const unacknowledged = signals.filter((s) => s.acknowledged_at === null).length;

    const closeCampaign = () => {
        if (
            !window.confirm(
                'Clôturer cette campagne ? La détection des signaux faibles s\'exécutera automatiquement.',
            )
        ) {
            return;
        }
        router.post(`/qvct/campaigns/${campaign.id}/close`);
    };

    const displayTitle =
        campaign.title ||
        new Date(campaign.opens_at).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });

    return (
        <DashboardLayout title={displayTitle} subtitle="Campagne QVCT">
            <PageHeader
                title={displayTitle}
                subtitle={campaign.questionnaire?.title ?? 'Campagne QVCT'}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Campagnes', href: '/qvct/campaigns' },
                    { label: displayTitle },
                ]}
                actions={
                    <>
                        <Badge tone={STATUS_TONE[campaign.status]} size="sm">
                            {STATUS_LABEL[campaign.status]}
                        </Badge>
                        {isActive && canManage && (
                            <Button variant="secondary" onClick={closeCampaign}>
                                Clôturer la campagne
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Réponses" value={response_count} tone="brand" />
                <KpiCard label="Signaux détectés" value={signals.length} tone={signals.length > 0 ? 'warning' : 'neutral'} />
                <KpiCard
                    label="Non traités"
                    value={unacknowledged}
                    tone={unacknowledged > 0 ? 'danger' : 'neutral'}
                />
                <KpiCard label="Questions" value={questions.length} tone="neutral" />
            </div>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Sidebar */}
                <div className="space-y-4 lg:col-span-1">
                    <Card>
                        <CardHeader title="Informations" />
                        <CardBody className="space-y-3">
                            <MetaRow label="Statut">
                                <Badge tone={STATUS_TONE[campaign.status]} size="sm">
                                    {STATUS_LABEL[campaign.status]}
                                </Badge>
                            </MetaRow>
                            {campaign.questionnaire && (
                                <MetaRow label="Questionnaire">
                                    <Link
                                        href={`/qvct/questionnaires/${campaign.questionnaire.id}`}
                                        className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400"
                                    >
                                        {campaign.questionnaire.title}
                                    </Link>
                                </MetaRow>
                            )}
                            {campaign.questionnaire?.frequency && (
                                <MetaRow label="Fréquence">
                                    <span className="text-sm text-ink-900 dark:text-white">
                                        {FREQUENCY_LABELS[campaign.questionnaire.frequency] ??
                                            campaign.questionnaire.frequency}
                                    </span>
                                </MetaRow>
                            )}
                            <MetaRow label="Ouverture">
                                <span className="text-sm text-ink-900 dark:text-white">
                                    {new Date(campaign.opens_at).toLocaleDateString('fr-FR', {
                                        dateStyle: 'medium',
                                    })}
                                </span>
                            </MetaRow>
                            {campaign.closes_at && (
                                <MetaRow label="Clôture prévue">
                                    <span className="text-sm text-ink-900 dark:text-white">
                                        {new Date(campaign.closes_at).toLocaleDateString('fr-FR', {
                                            dateStyle: 'medium',
                                        })}
                                    </span>
                                </MetaRow>
                            )}
                            {campaign.target_team && (
                                <MetaRow label="Équipe cible">
                                    <span className="text-sm text-ink-900 dark:text-white">
                                        {campaign.target_team}
                                    </span>
                                </MetaRow>
                            )}
                            {campaign.launched_by && (
                                <MetaRow label="Lancée par">
                                    <span className="text-sm text-ink-900 dark:text-white">
                                        {campaign.launched_by.first_name} {campaign.launched_by.last_name}
                                    </span>
                                </MetaRow>
                            )}
                            {campaign.closed_at && (
                                <MetaRow label="Clôturée le">
                                    <span className="text-sm text-ink-900 dark:text-white">
                                        {new Date(campaign.closed_at).toLocaleDateString('fr-FR', {
                                            dateStyle: 'medium',
                                        })}
                                    </span>
                                </MetaRow>
                            )}
                        </CardBody>
                    </Card>
                </div>

                {/* Main */}
                <div className="space-y-5 lg:col-span-2">
                    {/* Weak signals */}
                    <Card>
                        <CardHeader
                            title="Signaux faibles"
                            subtitle={
                                signals.length > 0
                                    ? `${signals.length} signal${signals.length > 1 ? 'aux' : ''} détecté${signals.length > 1 ? 's' : ''}`
                                    : 'Détection automatique à la clôture'
                            }
                        />
                        <CardBody>
                            {signals.length > 0 ? (
                                <ul className="space-y-3">
                                    {signals.map((s) => (
                                        <li
                                            key={s.id}
                                            className="rounded-xl border border-ink-100 p-3 dark:border-ink-700/60"
                                        >
                                            <div className="flex items-start gap-3">
                                                <div className="min-w-0 flex-1 space-y-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge
                                                            tone={severityTone(s.severity)}
                                                            size="xs"
                                                        >
                                                            {SIGNAL_TYPE_LABELS[s.signal_type] ?? s.signal_type}
                                                        </Badge>
                                                        {s.team_tag && (
                                                            <Badge tone="neutral" size="xs">
                                                                {s.team_tag}
                                                            </Badge>
                                                        )}
                                                        {s.severity !== null && (
                                                            <Badge tone={severityTone(s.severity)} size="xs">
                                                                Sévérité {s.severity}/10
                                                            </Badge>
                                                        )}
                                                        {s.acknowledged_at !== null && (
                                                            <Badge tone="sage" size="xs">
                                                                Traité
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </div>
                                                <span className="shrink-0 font-mono text-[10px] text-ink-400 dark:text-ink-500">
                                                    {new Date(s.created_at).toLocaleDateString('fr-FR')}
                                                </span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-ink-500 dark:text-ink-400">
                                    {campaign.status === 'closed'
                                        ? 'Aucun signal faible détecté lors de cette campagne.'
                                        : 'Les signaux faibles seront détectés automatiquement à la clôture de la campagne.'}
                                </p>
                            )}
                        </CardBody>
                    </Card>

                    {/* Questions preview */}
                    {questions.length > 0 && (
                        <Card>
                            <CardHeader
                                title="Questions du questionnaire"
                                subtitle={`${questions.length} question${questions.length > 1 ? 's' : ''}`}
                            />
                            <CardBody>
                                <ol className="space-y-3">
                                    {questions.map((q, idx) => (
                                        <li key={q.key} className="flex gap-3">
                                            <span className="flex size-5 shrink-0 items-center justify-center rounded bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                                {idx + 1}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm text-ink-900 dark:text-white">{q.label}</p>
                                                <div className="mt-1 flex flex-wrap gap-1.5">
                                                    <Badge tone="neutral" size="xs" className="font-mono">
                                                        {q.scale}
                                                    </Badge>
                                                    {q.category && (
                                                        <Badge tone="sage" size="xs">
                                                            {q.category}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                            </CardBody>
                        </Card>
                    )}
                </div>
            </div>
        </DashboardLayout>
    );
}

function MetaRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-3">
            <span className="shrink-0 text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                {label}
            </span>
            <div className="text-right">{children}</div>
        </div>
    );
}
