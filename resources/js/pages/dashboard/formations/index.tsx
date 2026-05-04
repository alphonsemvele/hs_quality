import { Badge, Button, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Formation {
    id: string;
    intervenant: string;
    initials: string;
    intitule: string;
    organisme: string | null;
    date_obtention: string | null;
    date_expiration: string | null;
    statut: 'valide' | 'expire_bientot' | 'expiree';
    statut_label: string;
    type: string;
}

interface Stats {
    total: number;
    a_jour: number;
    expirant_bientot: number;
    expirees: number;
}

interface Props {
    formations: Formation[];
    stats: Stats;
}

const STATUT_TONE: Record<string, 'sage' | 'warning' | 'danger'> = {
    valide: 'sage',
    expire_bientot: 'warning',
    expiree: 'danger',
};

export default function FormationsIndex({ formations = [], stats = { total: 0, a_jour: 0, expirant_bientot: 0, expirees: 0 } }: Partial<Props>) {
    return (
        <DashboardLayout title="Formations" subtitle="Habilitations, certifications et plan de formation">
            <PageHeader
                title="Formations & Habilitations"
                subtitle="Suivi des certifications, alertes d'expiration et plan de formation"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Formations' }]}
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Total" value={stats.total} tone="brand" />
                <KpiCard label="À jour" value={stats.a_jour} tone="sage" />
                <KpiCard label="Expire bientôt" value={stats.expirant_bientot} tone="warning" />
                <KpiCard label="Expirées" value={stats.expirees} tone="danger" />
            </div>

            <Card>
                {formations.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th>Intervenant</Th>
                                <Th>Formation</Th>
                                <Th>Organisme</Th>
                                <Th>Obtenue le</Th>
                                <Th>Expire le</Th>
                                <Th>Statut</Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {formations.map((f) => (
                                <Tr key={f.id}>
                                    <Td>
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                                {f.initials}
                                            </div>
                                            <span className="font-medium text-ink-900 dark:text-white">{f.intervenant}</span>
                                        </div>
                                    </Td>
                                    <Td>
                                        <div>
                                            <p className="text-sm font-medium text-ink-900 dark:text-white">{f.intitule}</p>
                                            <p className="text-xs text-ink-500 dark:text-ink-400">{f.type}</p>
                                        </div>
                                    </Td>
                                    <Td>{f.organisme ?? '—'}</Td>
                                    <Td className="font-mono text-xs">{f.date_obtention ?? '—'}</Td>
                                    <Td className="font-mono text-xs">{f.date_expiration ?? '—'}</Td>
                                    <Td>
                                        <Badge tone={STATUT_TONE[f.statut] ?? 'neutral'} size="sm" dot>
                                            {f.statut_label}
                                        </Badge>
                                    </Td>
                                </Tr>
                            ))}
                        </TBody>
                    </Table>
                ) : (
                    <EmptyState
                        icon={<AcademicIcon />}
                        title="Aucune formation enregistrée"
                        description="Les habilitations et certifications de vos intervenants apparaîtront ici."
                    />
                )}
            </Card>
        </DashboardLayout>
    );
}

function AcademicIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M22 10v6M2 10l10-5 10 5-10 5z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M6 12v5c3 3 9 3 12 0v-5" />
        </svg>
    );
}
