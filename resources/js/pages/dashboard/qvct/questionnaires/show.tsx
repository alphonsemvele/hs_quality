import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link, router } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Question {
    key: string;
    label: string;
    scale: '1-5' | '1-10' | 'yes_no';
    category: string;
}

interface Campaign {
    id: string;
    title: string | null;
    status: string;
    opens_at: string;
    closes_at: string | null;
}

interface Questionnaire {
    id: string;
    title: string;
    frequency: string | null;
    questions: Question[];
    is_active: boolean;
    version: number;
    created_at: string;
    campaigns: Campaign[];
}

interface Props {
    questionnaire: Questionnaire;
}

const FREQUENCY_LABELS: Record<string, string> = {
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    quarterly: 'Trimestrielle',
    biannual: 'Semestrielle',
    annual: 'Annuelle',
};

const SCALE_LABELS: Record<string, string> = {
    '1-5': 'Likert 1–5',
    '1-10': 'Échelle 1–10',
    yes_no: 'Oui / Non',
};

const CAMPAIGN_STATUS_TONE: Record<string, 'neutral' | 'sage' | 'brand' | 'warning'> = {
    draft: 'neutral',
    active: 'sage',
    closed: 'brand',
    archived: 'neutral',
};

const CAMPAIGN_STATUS_LABEL: Record<string, string> = {
    draft: 'Brouillon',
    active: 'En cours',
    closed: 'Clôturée',
    archived: 'Archivée',
};

export default function ShowQvctQuestionnaire({ questionnaire }: Props) {
    const canManage = useCan('qvct.manage');
    const questions = questionnaire.questions ?? [];
    const campaigns = questionnaire.campaigns ?? [];

    const archive = () => router.post(`/qvct/questionnaires/${questionnaire.id}/archive`);
    const destroy = () => {
        if (!window.confirm('Supprimer ce questionnaire ? Cette action est irréversible.')) {
            return;
        }
        router.delete(`/qvct/questionnaires/${questionnaire.id}`);
    };

    return (
        <DashboardLayout title={questionnaire.title} subtitle="Questionnaire QVCT">
            <PageHeader
                title={questionnaire.title}
                subtitle={`${questions.length} question${questions.length !== 1 ? 's' : ''} · v${questionnaire.version}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Questionnaires', href: '/qvct/questionnaires' },
                    { label: questionnaire.title },
                ]}
                actions={
                    canManage ? (
                        <>
                            {questionnaire.is_active && (
                                <Button variant="secondary" onClick={archive}>
                                    Archiver
                                </Button>
                            )}
                            <Button variant="danger" onClick={destroy}>
                                Supprimer
                            </Button>
                            <Link href="/qvct/campaigns/create">
                                <Button>Lancer une campagne</Button>
                            </Link>
                        </>
                    ) : null
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Sidebar */}
                <div className="space-y-4 lg:col-span-1">
                    <Card>
                        <CardHeader title="Informations" />
                        <CardBody className="space-y-3">
                            <MetaRow label="Statut">
                                <Badge tone={questionnaire.is_active ? 'sage' : 'neutral'} size="sm">
                                    {questionnaire.is_active ? 'Actif' : 'Archivé'}
                                </Badge>
                            </MetaRow>
                            <MetaRow label="Fréquence">
                                <span className="text-sm text-ink-900 dark:text-white">
                                    {questionnaire.frequency
                                        ? (FREQUENCY_LABELS[questionnaire.frequency] ?? questionnaire.frequency)
                                        : '—'}
                                </span>
                            </MetaRow>
                            <MetaRow label="Version">
                                <span className="text-sm text-ink-900 dark:text-white">v{questionnaire.version}</span>
                            </MetaRow>
                            <MetaRow label="Questions">
                                <span className="text-sm text-ink-900 dark:text-white">{questions.length}</span>
                            </MetaRow>
                            <MetaRow label="Créé le">
                                <span className="text-sm text-ink-900 dark:text-white">
                                    {new Date(questionnaire.created_at).toLocaleDateString('fr-FR', { dateStyle: 'medium' })}
                                </span>
                            </MetaRow>
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader
                            title="Campagnes récentes"
                            action={
                                <Link
                                    href="/qvct/campaigns"
                                    className="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400"
                                >
                                    Toutes →
                                </Link>
                            }
                        />
                        <CardBody>
                            {campaigns.length > 0 ? (
                                <ul className="space-y-1">
                                    {campaigns.map((c) => {
                                        const label =
                                            c.title ||
                                            new Date(c.opens_at).toLocaleDateString('fr-FR', {
                                                month: 'long',
                                                year: 'numeric',
                                            });
                                        return (
                                            <li key={c.id}>
                                                <Link
                                                    href={`/qvct/campaigns/${c.id}`}
                                                    className="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 hover:bg-ink-50 dark:hover:bg-ink-700/40"
                                                >
                                                    <span className="min-w-0 truncate text-sm text-ink-900 dark:text-white">
                                                        {label}
                                                    </span>
                                                    <Badge
                                                        tone={CAMPAIGN_STATUS_TONE[c.status] ?? 'neutral'}
                                                        size="xs"
                                                    >
                                                        {CAMPAIGN_STATUS_LABEL[c.status] ?? c.status}
                                                    </Badge>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            ) : (
                                <p className="text-sm text-ink-500 dark:text-ink-400">Aucune campagne lancée.</p>
                            )}
                        </CardBody>
                    </Card>
                </div>

                {/* Questions list */}
                <Card className="lg:col-span-2">
                    <CardHeader
                        title="Questions"
                        subtitle={`${questions.length} question${questions.length !== 1 ? 's' : ''}`}
                    />
                    <CardBody>
                        {questions.length > 0 ? (
                            <ol className="space-y-4">
                                {questions.map((q, idx) => (
                                    <li key={q.key} className="flex gap-4">
                                        <div className="flex size-7 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                            {idx + 1}
                                        </div>
                                        <div className="min-w-0 flex-1 space-y-1.5">
                                            <p className="text-sm leading-snug text-ink-900 dark:text-white">
                                                {q.label}
                                            </p>
                                            <div className="flex flex-wrap gap-1.5">
                                                <Badge tone="neutral" size="xs" className="font-mono">
                                                    {q.key}
                                                </Badge>
                                                <Badge tone="brand" size="xs">
                                                    {SCALE_LABELS[q.scale] ?? q.scale}
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
                        ) : (
                            <EmptyState
                                title="Aucune question"
                                description="Ce questionnaire ne contient pas encore de questions."
                            />
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function MetaRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <span className="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                {label}
            </span>
            {children}
        </div>
    );
}
