import { Badge, Button, Card, EmptyState, PageHeader, Pagination, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Question {
    key: string;
    label: string;
    scale: string;
    category?: string | null;
}

interface Questionnaire {
    id: string;
    title: string;
    version: number;
    frequency: string;
    questions: Question[] | null;
    is_active: boolean;
    created_at: string | null;
}

interface Paginated {
    data: Questionnaire[];
    current_page?: number;
    last_page?: number;
    total?: number;
}

interface Props {
    questionnaires: Paginated;
}

const FREQUENCY_LABELS: Record<string, string> = {
    weekly: 'Hebdomadaire',
    monthly: 'Mensuelle',
    quarterly: 'Trimestrielle',
};

function frequencyLabel(value: string): string {
    return FREQUENCY_LABELS[value] ?? value;
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('fr-FR');
}

export default function QvctQuestionnairesIndex({ questionnaires }: Props) {
    const list = questionnaires?.data ?? [];
    const canManage = useCan('qvct.manage');

    return (
        <DashboardLayout title="Questionnaires QVCT" subtitle="Modèles de baromètre">
            <PageHeader
                title="Questionnaires QVCT"
                subtitle="Modèles de baromètre réutilisés pour lancer des campagnes auprès des équipes."
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'QVCT', href: '/qvct' }, { label: 'Questionnaires' }]}
                actions={
                    canManage ? (
                        <Link href="/qvct/questionnaires/create">
                            <Button leadingIcon={<PlusIcon />}>Nouveau questionnaire</Button>
                        </Link>
                    ) : null
                }
            />

            <Card>
                {list.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th>Titre</Th>
                                <Th>Cadence</Th>
                                <Th>Questions</Th>
                                <Th>Statut</Th>
                                <Th>Créé le</Th>
                                <Th></Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {list.map((q) => (
                                <Tr key={q.id}>
                                    <Td>
                                        <Link
                                            href={`/qvct/questionnaires/${q.id}`}
                                            className="font-medium text-ink-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400"
                                        >
                                            {q.title}
                                        </Link>
                                        <p className="mt-0.5 font-mono text-[11px] text-ink-400 dark:text-ink-500">v{q.version}</p>
                                    </Td>
                                    <Td className="text-sm text-ink-600 dark:text-ink-300">{frequencyLabel(q.frequency)}</Td>
                                    <Td className="text-sm text-ink-600 dark:text-ink-300">{q.questions?.length ?? 0}</Td>
                                    <Td>
                                        {q.is_active ? (
                                            <Badge tone="sage" size="sm" dot>
                                                Actif
                                            </Badge>
                                        ) : (
                                            <Badge tone="neutral" size="sm">
                                                Archivé
                                            </Badge>
                                        )}
                                    </Td>
                                    <Td className="font-mono text-xs text-ink-500 dark:text-ink-400">{formatDate(q.created_at)}</Td>
                                    <Td className="text-right">
                                        <Link
                                            href={`/qvct/questionnaires/${q.id}`}
                                            className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
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
                        title="Aucun questionnaire"
                        description="Créez un premier modèle de baromètre QVCT pour lancer des campagnes auprès de vos équipes."
                        action={
                            canManage ? (
                                <Link href="/qvct/questionnaires/create">
                                    <Button>Nouveau questionnaire</Button>
                                </Link>
                            ) : undefined
                        }
                    />
                )}
                {list.length > 0 && (
                    <div className="px-4 pb-4">
                        <Pagination
                            currentPage={questionnaires?.current_page ?? 1}
                            lastPage={questionnaires?.last_page ?? 1}
                            total={questionnaires?.total ?? list.length}
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
