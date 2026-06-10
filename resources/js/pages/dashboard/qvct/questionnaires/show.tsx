import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link, router } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Question {
    key: string;
    label: string;
    scale: string;
    category?: string | null;
}

interface Campaign {
    id: string;
    title: string;
    status: string;
    opens_at: string | null;
    closes_at: string | null;
}

interface Questionnaire {
    id: string;
    title: string;
    version: number;
    frequency: string;
    questions: Question[] | null;
    is_active: boolean;
    created_at: string | null;
    campaigns?: Campaign[];
}

const FREQUENCY_LABELS: Record<string, string> = {
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    quarterly: 'Trimestrielle',
};

const SCALE_LABELS: Record<string, string> = {
    '1-5': 'Échelle 1 – 5',
    '1-10': 'Échelle 1 – 10',
    yes_no: 'Oui / Non',
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

export default function QvctQuestionnaireShow({ questionnaire }: { questionnaire: Questionnaire }) {
    const canManage = useCan('qvct.manage');
    const questions = questionnaire.questions ?? [];
    const campaigns = questionnaire.campaigns ?? [];

    const archive = () => {
        if (!window.confirm('Archiver ce questionnaire ? Il ne pourra plus servir à lancer de nouvelles campagnes.')) {
            return;
        }
        router.post(`/qvct/questionnaires/${questionnaire.id}/archive`, undefined, { preserveScroll: true });
    };

    return (
        <DashboardLayout title={questionnaire.title} subtitle="Questionnaire QVCT">
            <PageHeader
                title={questionnaire.title}
                subtitle={`Version ${questionnaire.version} · ${FREQUENCY_LABELS[questionnaire.frequency] ?? questionnaire.frequency}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Questionnaires', href: '/qvct/questionnaires' },
                    { label: questionnaire.title },
                ]}
                actions={
                    <>
                        {questionnaire.is_active ? (
                            <Badge tone="sage" size="sm" dot>
                                Actif
                            </Badge>
                        ) : (
                            <Badge tone="neutral" size="sm">
                                Archivé
                            </Badge>
                        )}
                        {canManage && questionnaire.is_active && (
                            <>
                                <Link href="/qvct/campaigns/create">
                                    <Button>Lancer une campagne</Button>
                                </Link>
                                <Button variant="secondary" onClick={archive}>
                                    Archiver
                                </Button>
                            </>
                        )}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card>
                    <CardHeader title="Métadonnées" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row label="Cadence" value={FREQUENCY_LABELS[questionnaire.frequency] ?? questionnaire.frequency} />
                            <Row label="Version" value={`v${questionnaire.version}`} />
                            <Row label="Questions" value={`${questions.length}`} />
                            <Row label="Campagnes lancées" value={`${campaigns.length}`} />
                            <Row label="Créé le" value={formatDate(questionnaire.created_at)} />
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader title="Questions" subtitle={`${questions.length} question(s)`} />
                    <CardBody>
                        {questions.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {questions.map((q, idx) => (
                                    <li key={q.key || idx} className="flex items-start gap-3 py-3">
                                        <div className="flex size-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                            {idx + 1}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium text-ink-900 dark:text-white">{q.label}</p>
                                            <div className="mt-1 flex flex-wrap items-center gap-2">
                                                <Badge tone="brand" size="xs">
                                                    {SCALE_LABELS[q.scale] ?? q.scale}
                                                </Badge>
                                                {q.category && (
                                                    <Badge tone="neutral" size="xs">
                                                        {q.category}
                                                    </Badge>
                                                )}
                                                <span className="font-mono text-[11px] text-ink-400 dark:text-ink-500">{q.key}</span>
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucune question" description="Ce questionnaire ne contient aucune question." />
                        )}
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader title="Campagnes liées" subtitle={`${campaigns.length} campagne(s)`} />
                    <CardBody>
                        {campaigns.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {campaigns.map((c) => {
                                    const meta = STATUS_META[c.status] ?? { label: c.status, tone: 'neutral' as const };
                                    return (
                                        <li key={c.id} className="flex items-center justify-between gap-3 py-3">
                                            <div className="min-w-0 flex-1">
                                                <Link
                                                    href={`/qvct/campaigns/${c.id}`}
                                                    className="text-sm font-medium text-ink-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400"
                                                >
                                                    {c.title}
                                                </Link>
                                                <p className="mt-0.5 font-mono text-[11px] text-ink-400 dark:text-ink-500">
                                                    {formatDate(c.opens_at)} → {formatDate(c.closes_at)}
                                                </p>
                                            </div>
                                            <Badge tone={meta.tone} size="sm" dot>
                                                {meta.label}
                                            </Badge>
                                        </li>
                                    );
                                })}
                            </ul>
                        ) : (
                            <EmptyState title="Aucune campagne" description="Aucune campagne n'a encore été lancée à partir de ce questionnaire." />
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
