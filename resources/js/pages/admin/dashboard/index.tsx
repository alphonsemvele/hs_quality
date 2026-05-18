import {
    Badge,
    Button,
    Card,
    CardBody,
    CardHeader,
    EmptyState,
    IncidentGraviteBadge,
    IncidentStatusBadge,
    KpiCard,
    PageHeader,
    TBody,
    THead,
    Table,
    Td,
    Th,
    Tr,
} from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../dashboard/layout';

interface Kpis {
    total_structures: number;
    active_structures: number;
    suspended_structures: number;
    structures_premium: number;
    structures_pro: number;
    structures_essential: number;
    total_users: number;
    total_intervenants: number;
    total_beneficiaries: number;
    interventions_this_month: number;
    interventions_in_progress: number;
    incidents_open: number;
    incidents_critical_open: number;
    mrr_estimate_eur: number;
}

interface RecentStructure {
    id: number | string;
    code: string;
    name: string;
    type_label: string;
    tier_label: string;
    status: 'active' | 'suspended';
    status_label: string;
    created_at: string | null;
}

interface StructureActivity {
    id: number | string;
    code: string;
    name: string;
    type_label: string;
    tier: string;
    tier_label: string;
    status: 'active' | 'suspended';
    status_label: string;
    users_count: number;
    beneficiaries_count: number;
    interventions_this_month: number;
    critical_incidents_open: number;
}

interface CriticalIncident {
    id: number | string;
    structure_name: string;
    structure_code: string;
    categorie: string;
    gravite: 'mineur' | 'significatif' | 'grave' | 'critique';
    statut: 'declare' | 'en_analyse' | 'plan_actions' | 'clos';
    occurred_at: string | null;
    occurred_at_human: string | null;
}

interface Props {
    kpis: Kpis;
    recent_structures: RecentStructure[];
    structures_activity: StructureActivity[];
    critical_incidents: CriticalIncident[];
}

const formatEuro = (value: number): string =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(value);

const formatDate = (iso: string | null): string =>
    iso ? new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

export default function AdminDashboard({ kpis, recent_structures, structures_activity, critical_incidents }: Props) {
    const activeRatio = kpis.total_structures > 0
        ? Math.round((kpis.active_structures / kpis.total_structures) * 100)
        : 0;

    return (
        <DashboardLayout title="Console plateforme" subtitle="Vue cross-tenants — administration QualitéDomicile">
            <PageHeader
                title="Console plateforme"
                subtitle={`${kpis.total_structures} structures provisionnées · ${kpis.active_structures} actives · ${kpis.suspended_structures} suspendues`}
                breadcrumb={[{ label: 'Plateforme' }]}
                actions={
                    <Link href="/admin/structures/create">
                        <Button leadingIcon={<PlusIcon />}>Nouvelle structure</Button>
                    </Link>
                }
            />

            {/* KPI principaux */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Structures actives"
                    value={kpis.active_structures}
                    sub={`${activeRatio}% du portefeuille`}
                    icon={<BuildingIcon />}
                    tone="brand"
                    progress={activeRatio}
                    hint="Tenants en production sur la plateforme. La barre de progression montre la part active sur l'ensemble du portefeuille (actives + suspendues)."
                />
                <KpiCard
                    label="Utilisateurs plateforme"
                    value={kpis.total_users}
                    sub={`${kpis.total_intervenants} intervenants terrain`}
                    icon={<UsersIcon />}
                    tone="sage"
                    hint="Comptes utilisateurs actifs (tous rôles, tous tenants confondus). Le sous-total cible les intervenants à domicile — leur volumétrie pilote la facturation par siège."
                />
                <KpiCard
                    label="Bénéficiaires suivis"
                    value={kpis.total_beneficiaries}
                    sub="Tous tenants confondus"
                    icon={<HeartIcon />}
                    tone="neutral"
                    hint="Personnes accompagnées par les structures de la plateforme. Données strictement agrégées — aucun accès aux dossiers individuels depuis cette console."
                />
                <KpiCard
                    label="MRR estimé"
                    value={formatEuro(kpis.mrr_estimate_eur)}
                    sub="Active × tier × users"
                    icon={<EuroIcon />}
                    tone="sage"
                    hint="Monthly Recurring Revenue : revenu mensuel récurrent estimé. Calculé à partir du tier d'abonnement × nombre d'utilisateurs facturables, uniquement pour les structures actives."
                />
            </div>

            {/* KPI activité */}
            <div className="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Interventions ce mois"
                    value={kpis.interventions_this_month}
                    sub={`${kpis.interventions_in_progress} en cours`}
                    icon={<ClipboardIcon />}
                    tone="brand"
                    hint="Visites à domicile planifiées ou réalisées sur le mois en cours (toutes structures). Le sous-total « en cours » correspond aux interventions ayant un check-in mais pas encore de check-out."
                />
                <KpiCard
                    label="Incidents ouverts"
                    value={kpis.incidents_open}
                    sub={`${kpis.incidents_critical_open} graves / critiques`}
                    icon={<AlertIcon />}
                    tone={kpis.incidents_critical_open > 0 ? 'danger' : 'warning'}
                    hint="Incidents non clôturés (statuts : déclaré, en analyse, plan d'actions). Les niveaux « grave » et « critique » déclenchent une notification ARS automatique sous 48 h."
                />
                <KpiCard
                    label="Tier Premium"
                    value={kpis.structures_premium}
                    sub={`${kpis.structures_pro} Pro · ${kpis.structures_essential} Essentiel`}
                    icon={<StarIcon />}
                    tone="brand"
                    hint="Répartition par niveau d'abonnement. Premium inclut tous les modules (QVCT, audits HAS, API mobile). Pro exclut la QVCT avancée. Essentiel se limite à la planification + dossiers."
                />
                <KpiCard
                    label="Suspendues"
                    value={kpis.suspended_structures}
                    sub="À surveiller"
                    icon={<PauseIcon />}
                    tone={kpis.suspended_structures > 0 ? 'warning' : 'neutral'}
                    hint="Tenants avec accès bloqué (impayé, non-conformité ou pause volontaire). Leurs utilisateurs ne peuvent plus se connecter mais les données restent conservées."
                />
            </div>

            <div className="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
                {/* Activité par tenant */}
                <div className="xl:col-span-2">
                    <Card>
                        <CardHeader>
                            <h3 className="text-sm font-semibold text-ink-900 dark:text-white">Activité par structure</h3>
                            <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                Vue agrégée — aucun accès aux dossiers individuels
                            </p>
                        </CardHeader>
                        <CardBody className="p-0">
                            {structures_activity.length > 0 ? (
                                <Table>
                                    <THead>
                                        <Tr>
                                            <Th hint="Tenant client de la plateforme : nom + code court + type d'établissement (SAAD, SSIAD, SPASAD, ESAD, CCAS).">
                                                Structure
                                            </Th>
                                            <Th hint="Niveau d'abonnement : Starter (essentiel), Pro, Premium. Détermine les quotas et les modules disponibles.">
                                                Tier
                                            </Th>
                                            <Th hint="État opérationnel du tenant : Actif ou Suspendu (impayé / non-conformité).">
                                                Statut
                                            </Th>
                                            <Th
                                                hint="Nombre total d'utilisateurs avec un compte actif sur cette structure (tous rôles : dirigeants, coordinateurs, qualité, RH, intervenants)."
                                                className="text-right"
                                            >
                                                Users
                                            </Th>
                                            <Th
                                                hint="Bénéficiaires actifs accompagnés par cette structure (dossiers non archivés)."
                                                className="text-right"
                                            >
                                                Bénéf.
                                            </Th>
                                            <Th
                                                hint="Nombre d'interventions à domicile réalisées ou planifiées sur le mois en cours."
                                                className="text-right"
                                            >
                                                Interv./mois
                                            </Th>
                                            <Th
                                                hint="Incidents classés « grave » ou « critique » non clôturés. Un incident critique déclenche automatiquement une notification ARS (Agence Régionale de Santé)."
                                                className="text-right"
                                            >
                                                Incid. critiques
                                            </Th>
                                        </Tr>
                                    </THead>
                                    <TBody>
                                        {structures_activity.map((s) => (
                                            <Tr key={s.id}>
                                                <Td>
                                                    <Link
                                                        href={`/admin/structures/${s.id}`}
                                                        className="font-medium text-ink-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400"
                                                    >
                                                        {s.name}
                                                    </Link>
                                                    <div className="font-mono text-xs text-ink-500 dark:text-ink-400">
                                                        {s.code} · {s.type_label}
                                                    </div>
                                                </Td>
                                                <Td>
                                                    <Badge tone={tierTone(s.tier)}>{s.tier_label}</Badge>
                                                </Td>
                                                <Td>
                                                    <Badge tone={s.status === 'active' ? 'sage' : 'warning'}>
                                                        {s.status_label}
                                                    </Badge>
                                                </Td>
                                                <Td className="text-right font-mono text-sm text-ink-700 dark:text-ink-300">
                                                    {s.users_count}
                                                </Td>
                                                <Td className="text-right font-mono text-sm text-ink-700 dark:text-ink-300">
                                                    {s.beneficiaries_count}
                                                </Td>
                                                <Td className="text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <span className="font-mono text-sm text-ink-700 dark:text-ink-300">
                                                            {s.interventions_this_month}
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            className="block h-1.5 w-16 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700"
                                                        >
                                                            <span
                                                                className="block h-full rounded-full bg-brand-500 dark:bg-brand-400"
                                                                style={{
                                                                    width:
                                                                        relativeWidth(
                                                                            s.interventions_this_month,
                                                                            structures_activity.map((x) => x.interventions_this_month),
                                                                        ) + '%',
                                                                }}
                                                            />
                                                        </span>
                                                    </div>
                                                </Td>
                                                <Td className="text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        <span
                                                            className={
                                                                s.critical_incidents_open > 0
                                                                    ? 'font-mono text-sm font-semibold text-danger-600 dark:text-danger-400'
                                                                    : 'font-mono text-sm text-ink-500 dark:text-ink-400'
                                                            }
                                                        >
                                                            {s.critical_incidents_open}
                                                        </span>
                                                        {s.critical_incidents_open > 0 && (
                                                            <span
                                                                aria-hidden="true"
                                                                className="block h-1.5 w-16 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700"
                                                            >
                                                                <span
                                                                    className="block h-full rounded-full bg-danger-500 dark:bg-danger-400"
                                                                    style={{
                                                                        width:
                                                                            relativeWidth(
                                                                                s.critical_incidents_open,
                                                                                structures_activity.map((x) => x.critical_incidents_open),
                                                                            ) + '%',
                                                                    }}
                                                                />
                                                            </span>
                                                        )}
                                                    </div>
                                                </Td>
                                            </Tr>
                                        ))}
                                    </TBody>
                                </Table>
                            ) : (
                                <EmptyState
                                    title="Aucune structure"
                                    message="Provisionnez votre première structure pour démarrer."
                                    action={
                                        <Link href="/admin/structures/create">
                                            <Button>Nouvelle structure</Button>
                                        </Link>
                                    }
                                />
                            )}
                        </CardBody>
                    </Card>
                </div>

                {/* Incidents critiques cross-tenants */}
                <div>
                    <Card>
                        <CardHeader>
                            <h3 className="text-sm font-semibold text-ink-900 dark:text-white">Incidents critiques ouverts</h3>
                            <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                Structures en alerte — tous tenants
                            </p>
                        </CardHeader>
                        <CardBody className="p-0">
                            {critical_incidents.length > 0 ? (
                                <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                    {critical_incidents.map((i) => (
                                        <li key={i.id} className="flex items-start gap-3 px-5 py-3">
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-center gap-2">
                                                    <IncidentGraviteBadge gravite={i.gravite} />
                                                    <IncidentStatusBadge statut={i.statut} />
                                                </div>
                                                <p className="mt-1.5 truncate text-sm font-medium text-ink-900 dark:text-white">
                                                    {i.structure_name}
                                                </p>
                                                <p className="text-xs text-ink-500 dark:text-ink-400">
                                                    {i.categorie} · {i.occurred_at_human ?? '—'}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <EmptyState title="Tout va bien" message="Aucun incident critique ouvert sur la plateforme." />
                            )}
                        </CardBody>
                    </Card>

                    {/* Dernières structures provisionnées */}
                    <div className="mt-6">
                        <Card>
                            <CardHeader>
                                <h3 className="text-sm font-semibold text-ink-900 dark:text-white">Provisionnements récents</h3>
                            </CardHeader>
                            <CardBody className="p-0">
                                {recent_structures.length > 0 ? (
                                    <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                        {recent_structures.map((s) => (
                                            <li key={s.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                                <div className="min-w-0">
                                                    <Link
                                                        href={`/admin/structures/${s.id}`}
                                                        className="block truncate text-sm font-medium text-ink-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400"
                                                    >
                                                        {s.name}
                                                    </Link>
                                                    <p className="text-xs text-ink-500 dark:text-ink-400">
                                                        {s.tier_label} · {formatDate(s.created_at)}
                                                    </p>
                                                </div>
                                                <Badge tone={s.status === 'active' ? 'sage' : 'warning'}>{s.status_label}</Badge>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <EmptyState title="Pas encore de structure" />
                                )}
                            </CardBody>
                        </Card>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}

/**
 * Bar width (0-100) relative to the maximum value in the column.
 * Returns 0 when all values are zero so the column stays empty visually.
 */
function relativeWidth(value: number, values: number[]): number {
    if (value === 0) return 0;
    const max = Math.max(...values, 0);
    if (max === 0) return 0;
    return Math.max(8, Math.round((value / max) * 100));
}

function tierTone(tier: string): 'brand' | 'sage' | 'neutral' {
    if (tier === 'premium') return 'brand';
    if (tier === 'pro') return 'sage';
    return 'neutral';
}

function PlusIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
            <path d="M12 5v14M5 12h14" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function BuildingIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16M3 21h18M9 7h1M9 11h1M9 15h1M14 7h1M14 11h1M14 15h1" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function UsersIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function HeartIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function EuroIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M18 7a8 8 0 100 10M3 10h11M3 14h9" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function ClipboardIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function AlertIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" strokeLinecap="round" strokeLinejoin="round" />
            <line x1="12" y1="9" x2="12" y2="13" strokeLinecap="round" />
            <line x1="12" y1="17" x2="12.01" y2="17" strokeLinecap="round" />
        </svg>
    );
}

function StarIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <polygon points="12 2 15 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 9 8.5 12 2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function PauseIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <rect x="6" y="4" width="4" height="16" rx="1" />
            <rect x="14" y="4" width="4" height="16" rx="1" />
        </svg>
    );
}
