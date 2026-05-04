import { Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import DashboardLayout from '../layout';

interface Indicateurs {
    conformite: number | null;
    pac_completion: number | null;
    qvct_moyen: number | null;
    formations_a_jour: number | null;
    incidents_ce_mois: number | null;
    interventions_ce_mois: number | null;
}

interface SeriesPoint {
    date: string;
    value: number;
}

interface Props {
    indicateurs: Indicateurs;
    series: { label: string; data: SeriesPoint[] }[];
}

export default function IndicateursIndex({
    indicateurs = {
        conformite: null,
        pac_completion: null,
        qvct_moyen: null,
        formations_a_jour: null,
        incidents_ce_mois: null,
        interventions_ce_mois: null,
    },
    series = [],
}: Partial<Props>) {
    const hasData = Object.values(indicateurs).some((v) => v !== null);

    return (
        <DashboardLayout title="Indicateurs" subtitle="Tableau de bord qualité & QVCT">
            <PageHeader
                title="Indicateurs & KPIs"
                subtitle="Vue consolidée des métriques qualité, QVCT et activité"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Indicateurs' }]}
            />

            <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <KpiCard
                    label="Score de conformité"
                    value={indicateurs.conformite !== null ? `${indicateurs.conformite} %` : '—'}
                    sub="Cible : 85 %"
                    icon={<ShieldIcon />}
                    tone="sage"
                    progress={indicateurs.conformite ?? 0}
                />
                <KpiCard
                    label="Complétion PAC"
                    value={indicateurs.pac_completion !== null ? `${indicateurs.pac_completion} %` : '—'}
                    sub="Plans d'amélioration"
                    icon={<CheckListIcon />}
                    tone="brand"
                    progress={indicateurs.pac_completion ?? 0}
                />
                <KpiCard
                    label="Score QVCT moyen"
                    value={indicateurs.qvct_moyen !== null ? `${indicateurs.qvct_moyen}/10` : '—'}
                    sub="Baromètre équipe"
                    icon={<HeartIcon />}
                    tone={indicateurs.qvct_moyen !== null && indicateurs.qvct_moyen < 5 ? 'warning' : 'sage'}
                    progress={indicateurs.qvct_moyen !== null ? indicateurs.qvct_moyen * 10 : 0}
                />
                <KpiCard
                    label="Formations à jour"
                    value={indicateurs.formations_a_jour !== null ? `${indicateurs.formations_a_jour} %` : '—'}
                    sub="Certifications valides"
                    icon={<AcademicIcon />}
                    tone="brand"
                    progress={indicateurs.formations_a_jour ?? 0}
                />
                <KpiCard
                    label="Incidents ce mois"
                    value={indicateurs.incidents_ce_mois ?? '—'}
                    sub="Déclarations"
                    icon={<AlertIcon />}
                    tone="danger"
                />
                <KpiCard
                    label="Interventions ce mois"
                    value={indicateurs.interventions_ce_mois ?? '—'}
                    sub="Visites réalisées"
                    icon={<ClipboardIcon />}
                    tone="neutral"
                />
            </div>

            <Card>
                <CardHeader title="Évolution temporelle" subtitle="Séries de données mensuelles" />
                <CardBody>
                    {hasData && series.length > 0 ? (
                        <div className="flex items-center justify-center py-12 text-sm text-ink-500 dark:text-ink-400">
                            Les graphiques seront disponibles avec les données des modules M3, M5 et M6.
                        </div>
                    ) : (
                        <EmptyState
                            icon={<ChartIcon />}
                            title="Pas encore de données"
                            description="Les indicateurs seront alimentés par les modules Audits, QVCT et Formations une fois activés."
                        />
                    )}
                </CardBody>
            </Card>
        </DashboardLayout>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4" />
        </svg>
    );
}
function CheckListIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 11l3 3L22 4" />
            <path strokeLinecap="round" strokeLinejoin="round" d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" />
        </svg>
    );
}
function HeartIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
        </svg>
    );
}
function AcademicIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
            <path d="M6 12v5c3 3 9 3 12 0v-5" />
        </svg>
    );
}
function AlertIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            <line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
    );
}
function ClipboardIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
    );
}
function ChartIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M18 20V10M12 20V4M6 20v-6" />
        </svg>
    );
}
