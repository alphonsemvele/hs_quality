import { Badge, Button, Card, CardBody, EmptyStateRich, KpiCard, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface AuditSummary {
    id: string;
    titre: string;
    referentiel: string;
    referentiel_label: string;
    date_audit: string | null;
    statut: 'planifie' | 'en_cours' | 'termine' | 'annule';
    statut_label: string;
    score: number | null;
    auditeur: string | null;
    nb_ecarts: number;
}

interface Stats {
    total: number;
    en_cours: number;
    termines: number;
    score_moyen: number | null;
}

interface Props {
    audits: AuditSummary[];
    stats: Stats;
}

const STATUT_TONE: Record<string, 'brand' | 'warning' | 'sage' | 'neutral' | 'danger'> = {
    planifie: 'brand',
    en_cours: 'warning',
    termine: 'sage',
    annule: 'neutral',
};

export default function AuditsIndex({ audits = [], stats = { total: 0, en_cours: 0, termines: 0, score_moyen: null } }: Partial<Props>) {
    const canManage = useCan('audits.manage');
    return (
        <DashboardLayout title="Audits & Conformité" subtitle="Évaluations HAS, AFNOR, ISO 9001">
            <PageHeader
                title="Audits & Conformité"
                subtitle="Grilles d'évaluation, scoring et plans d'actions correctifs"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Audits' }]}
                actions={
                    canManage ? (
                        <Link href="/audits/create">
                            <Button leadingIcon={<PlusIcon />}>Nouvel audit</Button>
                        </Link>
                    ) : null
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Total audits" value={stats.total} tone="brand" />
                <KpiCard label="En cours" value={stats.en_cours} tone="warning" />
                <KpiCard label="Terminés" value={stats.termines} tone="sage" />
                <KpiCard
                    label="Score moyen"
                    value={stats.score_moyen !== null ? `${stats.score_moyen} %` : '—'}
                    tone="sage"
                    progress={stats.score_moyen ?? 0}
                />
            </div>

            {audits.length > 0 ? (
                <ul className="space-y-3">
                    {audits.map((audit) => (
                        <Link key={audit.id} href={`/audits/${audit.id}`}>
                            <Card className="cursor-pointer transition-shadow hover:shadow-md">
                                <CardBody>
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{audit.titre}</h3>
                                                <Badge tone={STATUT_TONE[audit.statut] ?? 'neutral'} size="sm" dot>
                                                    {audit.statut_label}
                                                </Badge>
                                                <Badge tone="brand" size="xs">{audit.referentiel_label}</Badge>
                                            </div>
                                            <div className="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-ink-500 dark:text-ink-400">
                                                {audit.date_audit && <span className="font-mono">{audit.date_audit}</span>}
                                                {audit.auditeur && <span>Auditeur : {audit.auditeur}</span>}
                                                {audit.nb_ecarts > 0 && (
                                                    <Badge tone="danger" size="xs">{audit.nb_ecarts} écart(s)</Badge>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex shrink-0 items-center gap-3">
                                            {audit.score !== null && (
                                                <span className="font-mono text-lg font-bold text-ink-900 dark:text-white">
                                                    {audit.score}%
                                                </span>
                                            )}
                                            <span className="text-sm font-medium text-brand-600 dark:text-brand-400">Voir →</span>
                                        </div>
                                    </div>
                                </CardBody>
                            </Card>
                        </Link>
                    ))}
                </ul>
            ) : (
                <Card>
                    <EmptyStateRich
                        icon={<ShieldIcon />}
                        title="Démarrer mon premier audit"
                        description="Préparez votre visite HAS sereinement. Un audit ISO ou AFNOR se configure en moins de 5 minutes via notre wizard guidé."
                        primaryAction={
                            canManage ? (
                                <Link href="/audits/create">
                                    <Button size="lg">Lancer un audit guidé →</Button>
                                </Link>
                            ) : undefined
                        }
                        suggestions={[
                            {
                                icon: '🏥',
                                title: 'Audit HAS',
                                description: '~86 critères. Référentiel principal pour les structures médico-sociales.',
                                tone: 'sage',
                                cta: canManage ? { label: 'Configurer un audit HAS', href: '/audits/create' } : undefined,
                            },
                            {
                                icon: '⚙️',
                                title: 'ISO 9001',
                                description: '~52 critères. Management de la qualité et amélioration continue.',
                                tone: 'brand',
                                cta: canManage ? { label: 'Configurer un audit ISO', href: '/audits/create' } : undefined,
                            },
                            {
                                icon: '🇫🇷',
                                title: 'AFNOR NF X50-056',
                                description: '~64 critères. Qualité des services aux personnes à domicile.',
                                tone: 'warning',
                                cta: canManage ? { label: 'Configurer un audit AFNOR', href: '/audits/create' } : undefined,
                            },
                        ]}
                    />
                </Card>
            )}
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

function ShieldIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4" />
        </svg>
    );
}
