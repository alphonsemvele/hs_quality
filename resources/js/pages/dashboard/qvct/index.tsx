import { Badge, Button, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Campagne {
    id: string;
    titre: string;
    date_debut: string;
    date_fin: string | null;
    statut: 'active' | 'terminee' | 'planifiee';
    statut_label: string;
    taux_participation: number;
    score_moyen: number | null;
    nb_reponses: number;
    nb_alertes: number;
}

interface Stats {
    score_moyen: number | null;
    taux_participation: number | null;
    alertes_actives: number;
    derniere_campagne: string | null;
}

interface Props {
    campagnes: Campagne[];
    stats: Stats;
}

const STATUT_TONE: Record<string, 'sage' | 'warning' | 'brand'> = {
    active: 'sage',
    terminee: 'brand',
    planifiee: 'warning',
};

export default function QvctIndex({ campagnes = [], stats = { score_moyen: null, taux_participation: null, alertes_actives: 0, derniere_campagne: null } }: Partial<Props>) {
    const canManage = useCan('qvct.manage');
    return (
        <DashboardLayout title="Baromètre QVCT" subtitle="Qualité de Vie et Conditions de Travail">
            <PageHeader
                title="Baromètre QVCT"
                subtitle="Campagnes de sondage, signaux faibles et alertes RPS"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'QVCT' }]}
                actions={
                    canManage ? (
                        <Link href="/qvct/questionnaire">
                            <Button leadingIcon={<PlusIcon />}>Nouvelle campagne</Button>
                        </Link>
                    ) : null
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard
                    label="Score moyen"
                    value={stats.score_moyen !== null ? `${stats.score_moyen}/10` : '—'}
                    tone={stats.score_moyen !== null && stats.score_moyen < 5 ? 'warning' : 'sage'}
                    progress={stats.score_moyen !== null ? stats.score_moyen * 10 : 0}
                />
                <KpiCard
                    label="Participation"
                    value={stats.taux_participation !== null ? `${stats.taux_participation} %` : '—'}
                    tone="brand"
                    progress={stats.taux_participation ?? 0}
                />
                <KpiCard label="Alertes actives" value={stats.alertes_actives} tone={stats.alertes_actives > 0 ? 'danger' : 'sage'} />
                <KpiCard label="Dernière campagne" value={stats.derniere_campagne ?? '—'} tone="neutral" />
            </div>

            {campagnes.length > 0 ? (
                <ul className="space-y-3">
                    {campagnes.map((c) => (
                        <Card key={c.id} className="hover:shadow-md">
                            <CardBody>
                                <div className="flex items-start justify-between gap-4">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h3 className="text-sm font-semibold text-ink-900 dark:text-white">{c.titre}</h3>
                                            <Badge tone={STATUT_TONE[c.statut] ?? 'neutral'} size="sm" dot>{c.statut_label}</Badge>
                                            {c.nb_alertes > 0 && <Badge tone="danger" size="xs">{c.nb_alertes} alerte(s)</Badge>}
                                        </div>
                                        <div className="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-ink-500 dark:text-ink-400">
                                            <span className="font-mono">{c.date_debut}{c.date_fin ? ` → ${c.date_fin}` : ''}</span>
                                            <span>{c.nb_reponses} réponse(s)</span>
                                            <span>Participation : {c.taux_participation}%</span>
                                        </div>
                                    </div>
                                    {c.score_moyen !== null && (
                                        <span className="font-mono text-lg font-bold text-ink-900 dark:text-white">{c.score_moyen}/10</span>
                                    )}
                                </div>
                            </CardBody>
                        </Card>
                    ))}
                </ul>
            ) : (
                <Card>
                    <EmptyState
                        icon={<HeartIcon />}
                        title="Aucune campagne QVCT"
                        description="Lancez votre première campagne de baromètre pour mesurer la qualité de vie au travail de vos équipes."
                        action={
                            canManage ? (
                                <Link href="/qvct/questionnaire">
                                    <Button>Lancer une campagne</Button>
                                </Link>
                            ) : undefined
                        }
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
function HeartIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
        </svg>
    );
}
