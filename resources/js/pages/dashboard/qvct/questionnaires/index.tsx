import { Badge, Button, Card, EmptyState, PageHeader, Pagination } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Questionnaire {
    id: string;
    title: string;
    frequency: string | null;
    questions: unknown[] | null;
    is_active: boolean;
    version: number;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    questionnaires: Paginated<Questionnaire>;
}

const FREQUENCY_LABELS: Record<string, string> = {
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    quarterly: 'Trimestrielle',
    biannual: 'Semestrielle',
    annual: 'Annuelle',
};

export default function QvctQuestionnairesIndex({ questionnaires }: Props) {
    const canManage = useCan('qvct.manage');
    const list = questionnaires?.data ?? [];
    const meta = questionnaires;

    return (
        <DashboardLayout title="Questionnaires QVCT" subtitle="Modèles de questionnaires bien-être">
            <PageHeader
                title="Questionnaires QVCT"
                subtitle={`${meta?.total ?? list.length} questionnaire(s)`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'QVCT', href: '/qvct' },
                    { label: 'Questionnaires' },
                ]}
                actions={
                    canManage ? (
                        <Link href="/qvct/questionnaires/create">
                            <Button leadingIcon={<PlusIcon />}>Nouveau questionnaire</Button>
                        </Link>
                    ) : null
                }
            />

            {list.length > 0 ? (
                <ul className="space-y-3">
                    {list.map((q) => {
                        const questionCount = Array.isArray(q.questions) ? q.questions.length : 0;
                        return (
                            <Card key={q.id} className="transition-shadow hover:shadow-md">
                                <div className="flex items-start gap-4 p-5">
                                    <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                        {questionCount}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Link
                                                href={`/qvct/questionnaires/${q.id}`}
                                                className="text-sm font-semibold text-ink-900 hover:text-brand-700 dark:text-white dark:hover:text-brand-300"
                                            >
                                                {q.title}
                                            </Link>
                                            {q.frequency && (
                                                <Badge tone="brand" size="xs">
                                                    {FREQUENCY_LABELS[q.frequency] ?? q.frequency}
                                                </Badge>
                                            )}
                                            <Badge tone={q.is_active ? 'sage' : 'neutral'} size="xs">
                                                {q.is_active ? 'Actif' : 'Archivé'}
                                            </Badge>
                                            <Badge tone="neutral" size="xs">
                                                v{q.version}
                                            </Badge>
                                        </div>
                                        <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                                            {questionCount} question{questionCount !== 1 ? 's' : ''} · Créé le{' '}
                                            {new Date(q.created_at).toLocaleDateString('fr-FR', { dateStyle: 'medium' })}
                                        </p>
                                    </div>
                                    <Link
                                        href={`/qvct/questionnaires/${q.id}`}
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
                        title="Aucun questionnaire"
                        description="Créez votre premier questionnaire QVCT pour lancer des campagnes de bien-être."
                        action={
                            canManage ? (
                                <Link href="/qvct/questionnaires/create">
                                    <Button size="sm">Créer un questionnaire</Button>
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
