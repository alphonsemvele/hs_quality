import {
    Badge,
    Button,
    Card,
    CardBody,
    CardHeader,
    EmptyState,
    KpiCard,
    PageHeader,
    TBody,
    THead,
    Table,
    Td,
    Th,
    Tr,
} from '@/components/ui';
import { useUrlTab } from '@/lib/use-url-tab';
import { cn } from '@/lib/utils';
import DashboardLayout from '../layout';

interface Formation {
    id: string;
    intervenant: string;
    initials: string;
    intitule: string;
    organisme: string | null;
    date_obtention: string | null;
    date_expiration: string | null;
    days_to_expiry: number;
    statut: 'valide' | 'expire_bientot' | 'expiree';
    statut_label: string;
    type: string;
}

interface TrainingPlan {
    id: string;
    year: number;
    theme: string;
    target_audience: string;
    status: 'draft' | 'published' | 'archived';
    status_label: string;
    sessions_total: number;
    sessions_done: number;
    participants_total: number;
    participants_done: number;
}

interface Session {
    id: string;
    date: string;
    time: string;
    title: string;
    location: string;
    capacity: number;
    registered: number;
    status: 'scheduled' | 'full' | 'cancelled';
}

interface ExpiringAlert {
    id: string;
    intervenant: string;
    intitule: string;
    date_expiration: string;
    days_to_expiry: number;
    severity: 'urgent' | 'warning' | 'expired';
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
    plans: TrainingPlan[];
    sessions: Session[];
    expiringAlerts: ExpiringAlert[];
}

type Tab = 'overview' | 'plans' | 'sessions' | 'competencies';

const FORMATIONS_TABS: readonly Tab[] = ['overview', 'plans', 'sessions', 'competencies'];

const STATUT_TONE: Record<string, 'sage' | 'warning' | 'danger'> = {
    valide: 'sage',
    expire_bientot: 'warning',
    expiree: 'danger',
};

const PLAN_TONE: Record<TrainingPlan['status'], 'sage' | 'warning' | 'brand'> = {
    published: 'sage',
    draft: 'warning',
    archived: 'brand',
};

export default function FormationsIndex({
    formations = [],
    stats = { total: 0, a_jour: 0, expirant_bientot: 0, expirees: 0 },
    plans = [],
    sessions = [],
    expiringAlerts = [],
}: Partial<Props>) {
    const [tab, setTab] = useUrlTab<Tab>('overview', FORMATIONS_TABS);

    return (
        <DashboardLayout title="Formations" subtitle="Plans, sessions, habilitations & certifications">
            <PageHeader
                title="Formations & Compétences"
                subtitle="Plans de formation annuels, sessions, alertes d'expiration et compétences acquises"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Formations' }]}
            />

            <div className="mb-5 flex flex-wrap gap-1 rounded-lg border border-ink-200 bg-white p-1 dark:border-ink-700 dark:bg-ink-800">
                <TabButton active={tab === 'overview'} onClick={() => setTab('overview')}>
                    Vue d'ensemble
                </TabButton>
                <TabButton active={tab === 'plans'} onClick={() => setTab('plans')}>
                    Plans annuels
                </TabButton>
                <TabButton active={tab === 'sessions'} onClick={() => setTab('sessions')}>
                    Sessions
                </TabButton>
                <TabButton active={tab === 'competencies'} onClick={() => setTab('competencies')}>
                    Compétences
                </TabButton>
            </div>

            {tab === 'overview' && (
                <OverviewTab stats={stats} expiringAlerts={expiringAlerts} sessions={sessions.slice(0, 3)} plans={plans.filter((p) => p.status === 'published').slice(0, 2)} />
            )}
            {tab === 'plans' && <PlansTab plans={plans} />}
            {tab === 'sessions' && <SessionsTab sessions={sessions} />}
            {tab === 'competencies' && <CompetenciesTab formations={formations} />}
        </DashboardLayout>
    );
}

function TabButton({ active, onClick, children }: { active: boolean; onClick: () => void; children: React.ReactNode }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-md px-3 py-2 text-xs font-medium transition-colors sm:flex-initial sm:px-4',
                active
                    ? 'bg-brand-600 text-white shadow-sm'
                    : 'text-ink-600 hover:bg-ink-100 dark:text-ink-300 dark:hover:bg-ink-700/60',
            )}
        >
            {children}
        </button>
    );
}

function OverviewTab({
    stats,
    expiringAlerts,
    sessions,
    plans,
}: {
    stats: Stats;
    expiringAlerts: ExpiringAlert[];
    sessions: Session[];
    plans: TrainingPlan[];
}) {
    return (
        <>
            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Total compétences" value={stats.total} icon={<AcademicIcon />} tone="brand" />
                <KpiCard label="À jour" value={stats.a_jour} icon={<CheckIcon />} tone="sage" />
                <KpiCard
                    label="Expire bientôt"
                    value={stats.expirant_bientot}
                    icon={<ClockIcon />}
                    tone={stats.expirant_bientot > 0 ? 'warning' : 'sage'}
                />
                <KpiCard
                    label="Expirées"
                    value={stats.expirees}
                    icon={<AlertIcon />}
                    tone={stats.expirees > 0 ? 'danger' : 'sage'}
                />
            </div>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader
                        title="Alertes d'expiration"
                        subtitle="Priorité aux certifications expirées et urgentes"
                    />
                    <CardBody>
                        {expiringAlerts.length > 0 ? (
                            <ul className="space-y-2">
                                {expiringAlerts.map((a) => (
                                    <AlertRow key={a.id} alert={a} />
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                icon={<CheckIcon />}
                                title="Tout est à jour"
                                description="Aucune certification ne nécessite d'attention immédiate."
                            />
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Prochaines sessions" subtitle={`${sessions.length} à venir`} />
                    <CardBody className="space-y-2">
                        {sessions.length > 0 ? (
                            sessions.map((s) => <SessionMini key={s.id} session={s} />)
                        ) : (
                            <EmptyState title="Aucune session" description="Aucune session planifiée." />
                        )}
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader title="Plans en cours" subtitle={`${plans.length} plan(s) publié(s)`} />
                    <CardBody>
                        {plans.length > 0 ? (
                            <ul className="grid grid-cols-1 gap-3 md:grid-cols-2">
                                {plans.map((p) => (
                                    <PlanCard key={p.id} plan={p} />
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucun plan publié" description="Créez un plan annuel pour structurer votre offre de formation." />
                        )}
                    </CardBody>
                </Card>
            </div>
        </>
    );
}

function AlertRow({ alert }: { alert: ExpiringAlert }) {
    const sev = alert.severity;
    const map: Record<ExpiringAlert['severity'], { bg: string; accent: string; label: string; icon: React.ReactNode }> = {
        urgent: {
            bg: 'border-warning-200 bg-warning-50/60 dark:border-warning-700/40 dark:bg-warning-900/15',
            accent: 'text-warning-700 dark:text-warning-300',
            label: `Expire dans ${alert.days_to_expiry} j`,
            icon: <ClockIcon />,
        },
        warning: {
            bg: 'border-brand-200 bg-brand-50/40 dark:border-brand-700/40 dark:bg-brand-900/15',
            accent: 'text-brand-700 dark:text-brand-300',
            label: `Expire dans ${alert.days_to_expiry} j`,
            icon: <ClockIcon />,
        },
        expired: {
            bg: 'border-danger-200 bg-danger-50/60 dark:border-danger-700/40 dark:bg-danger-900/15',
            accent: 'text-danger-700 dark:text-danger-300',
            label: `Expirée depuis ${Math.abs(alert.days_to_expiry)} j`,
            icon: <AlertIcon />,
        },
    };
    const c = map[sev];

    return (
        <li className={cn('flex items-start gap-3 rounded-xl border p-3', c.bg)}>
            <span className={cn('flex size-9 shrink-0 items-center justify-center rounded-lg bg-white dark:bg-ink-800', c.accent)}>
                {c.icon}
            </span>
            <div className="min-w-0 flex-1">
                <p className="text-sm font-medium text-ink-900 dark:text-white">{alert.intitule}</p>
                <p className="text-xs text-ink-500 dark:text-ink-400">
                    <span className="font-medium text-ink-700 dark:text-ink-300">{alert.intervenant}</span> · {alert.date_expiration}
                </p>
                <p className={cn('mt-1 text-[11px] font-semibold uppercase tracking-wider', c.accent)}>{c.label}</p>
            </div>
            <Button size="sm" variant="secondary">
                Programmer
            </Button>
        </li>
    );
}

function SessionMini({ session }: { session: Session }) {
    const full = session.registered >= session.capacity;
    return (
        <div className="flex items-start gap-3 rounded-lg border border-ink-100 p-2.5 dark:border-ink-700/60">
            <div className="flex size-11 shrink-0 flex-col items-center justify-center rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                <span className="font-mono text-[10px] font-semibold uppercase tracking-wider">{monthShort(session.date)}</span>
                <span className="font-mono text-base font-bold leading-none tabular-nums">{dayOf(session.date)}</span>
            </div>
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-ink-900 dark:text-white">{session.title}</p>
                <p className="text-[11px] text-ink-500 dark:text-ink-400">
                    {session.time} · {session.location}
                </p>
                <p className={cn('mt-0.5 text-[11px] font-medium', full ? 'text-warning-700 dark:text-warning-400' : 'text-sage-700 dark:text-sage-400')}>
                    {session.registered}/{session.capacity} inscrits {full && '· Complet'}
                </p>
            </div>
        </div>
    );
}

function PlanCard({ plan }: { plan: TrainingPlan }) {
    const progress = plan.participants_total > 0 ? (plan.participants_done / plan.participants_total) * 100 : 0;
    return (
        <li className="rounded-xl border border-ink-100 p-4 dark:border-ink-700/60">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <Badge tone={PLAN_TONE[plan.status]} size="xs" dot>
                            {plan.status_label}
                        </Badge>
                        <span className="font-mono text-[11px] text-ink-500 dark:text-ink-400">{plan.year}</span>
                    </div>
                    <h3 className="mt-1 text-sm font-semibold text-ink-900 dark:text-white">{plan.theme}</h3>
                    <p className="mt-0.5 text-[11px] text-ink-500 dark:text-ink-400">{plan.target_audience}</p>
                </div>
            </div>
            <div className="mt-3">
                <div className="flex items-center justify-between text-[11px] text-ink-500 dark:text-ink-400">
                    <span>
                        {plan.participants_done}/{plan.participants_total} formés · {plan.sessions_done}/{plan.sessions_total} sessions
                    </span>
                    <span className="font-mono tabular-nums">{progress.toFixed(0)}%</span>
                </div>
                <div className="mt-1.5 h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                    <div
                        className={cn('h-full rounded-full transition-all duration-700', progress === 100 ? 'bg-sage-500' : 'bg-brand-500')}
                        style={{ width: `${progress}%` }}
                    />
                </div>
            </div>
        </li>
    );
}

function PlansTab({ plans }: { plans: TrainingPlan[] }) {
    if (plans.length === 0) {
        return (
            <Card>
                <EmptyState icon={<AcademicIcon />} title="Aucun plan de formation" description="Créez un plan annuel pour structurer votre offre." />
            </Card>
        );
    }
    const byYear = plans.reduce<Record<number, TrainingPlan[]>>((acc, p) => {
        if (!acc[p.year]) acc[p.year] = [];
        acc[p.year].push(p);
        return acc;
    }, {});
    const years = Object.keys(byYear).map(Number).sort((a, b) => b - a);
    return (
        <div className="space-y-5">
            {years.map((y) => (
                <section key={y}>
                    <h3 className="mb-3 flex items-center gap-2 text-sm font-semibold text-ink-700 dark:text-ink-200">
                        Année {y}
                        <span className="rounded-full bg-ink-100 px-2 py-0.5 font-mono text-[11px] text-ink-600 dark:bg-ink-700 dark:text-ink-300">
                            {byYear[y].length} plan{byYear[y].length > 1 ? 's' : ''}
                        </span>
                    </h3>
                    <ul className="grid grid-cols-1 gap-3 md:grid-cols-2">
                        {byYear[y].map((p) => (
                            <PlanCard key={p.id} plan={p} />
                        ))}
                    </ul>
                </section>
            ))}
        </div>
    );
}

function SessionsTab({ sessions }: { sessions: Session[] }) {
    if (sessions.length === 0) {
        return (
            <Card>
                <EmptyState icon={<CalendarIcon />} title="Aucune session" description="Planifiez votre première session de formation." />
            </Card>
        );
    }

    // Group by month
    const byMonth = sessions.reduce<Record<string, Session[]>>((acc, s) => {
        const key = s.date.slice(0, 7);
        if (!acc[key]) acc[key] = [];
        acc[key].push(s);
        return acc;
    }, {});
    const months = Object.keys(byMonth).sort();

    return (
        <div className="space-y-5">
            {months.map((m) => (
                <Card key={m}>
                    <CardHeader title={monthLong(`${m}-01`)} subtitle={`${byMonth[m].length} session${byMonth[m].length > 1 ? 's' : ''}`} />
                    <CardBody>
                        <ul className="space-y-2">
                            {byMonth[m].map((s) => (
                                <SessionFullRow key={s.id} session={s} />
                            ))}
                        </ul>
                    </CardBody>
                </Card>
            ))}
        </div>
    );
}

function SessionFullRow({ session }: { session: Session }) {
    const full = session.registered >= session.capacity;
    const fillPct = session.capacity > 0 ? (session.registered / session.capacity) * 100 : 0;
    return (
        <li className="flex flex-col items-stretch gap-3 rounded-xl border border-ink-100 p-3.5 hover:bg-ink-50 sm:flex-row sm:items-center dark:border-ink-700/60 dark:hover:bg-ink-700/30">
            <div className="flex size-14 shrink-0 flex-col items-center justify-center rounded-lg bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                <span className="font-mono text-[11px] font-semibold uppercase tracking-wider">{monthShort(session.date)}</span>
                <span className="font-mono text-lg font-bold leading-none tabular-nums">{dayOf(session.date)}</span>
            </div>
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <h4 className="text-sm font-semibold text-ink-900 dark:text-white">{session.title}</h4>
                    {full && (
                        <Badge tone="warning" size="xs">
                            Complet
                        </Badge>
                    )}
                </div>
                <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                    {session.time} · {session.location}
                </p>
                <div className="mt-2 flex items-center gap-3">
                    <div className="flex-1">
                        <div className="h-1.5 overflow-hidden rounded-full bg-ink-100 dark:bg-ink-700">
                            <div
                                className={cn('h-full rounded-full transition-all duration-500', full ? 'bg-warning-500' : 'bg-brand-500')}
                                style={{ width: `${fillPct}%` }}
                            />
                        </div>
                    </div>
                    <span className="font-mono text-[11px] tabular-nums text-ink-500 dark:text-ink-400">
                        {session.registered}/{session.capacity}
                    </span>
                </div>
            </div>
            <Button size="sm" variant={full ? 'secondary' : 'primary'} disabled={full}>
                {full ? 'Complet' : "S'inscrire"}
            </Button>
        </li>
    );
}

function CompetenciesTab({ formations }: { formations: Formation[] }) {
    if (formations.length === 0) {
        return (
            <Card>
                <EmptyState icon={<AcademicIcon />} title="Aucune formation enregistrée" description="Les habilitations et certifications de vos intervenants apparaîtront ici." />
            </Card>
        );
    }
    return (
        <Card>
            <Table>
                <THead>
                    <Tr>
                        <Th hint="Salarié(e) terrain titulaire de la certification : DEAVS, AES, ADVF, CQP, etc.">Intervenant</Th>
                        <Th hint="Nom du diplôme ou de la certification professionnelle (DEAVS = Diplôme d'État d'Auxiliaire de Vie Sociale, AES = Accompagnant Éducatif et Social, ADVF = Assistant De Vie aux Familles, CQP = Certificat de Qualification Professionnelle).">
                            Formation
                        </Th>
                        <Th hint="Organisme certificateur ou centre de formation ayant délivré la qualification.">Organisme</Th>
                        <Th hint="Date d'obtention initiale de la certification.">Obtenue</Th>
                        <Th hint="Date d'expiration. Des alertes automatiques sont envoyées au RH et au titulaire à J-60 et J-30 avant cette date.">
                            Expire
                        </Th>
                        <Th hint="Validité actuelle : Valide, À renouveler (< 60 j), Expirée. Une certification expirée empêche d'assigner l'intervenant à un bénéficiaire requérant cette compétence.">
                            Statut
                        </Th>
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
                            <Td>
                                {f.date_expiration ? (
                                    <span className="font-mono text-xs">
                                        {f.date_expiration}
                                        <ExpiryHint days={f.days_to_expiry} />
                                    </span>
                                ) : (
                                    '—'
                                )}
                            </Td>
                            <Td>
                                <Badge tone={STATUT_TONE[f.statut] ?? 'neutral'} size="sm" dot>
                                    {f.statut_label}
                                </Badge>
                            </Td>
                        </Tr>
                    ))}
                </TBody>
            </Table>
        </Card>
    );
}

function ExpiryHint({ days }: { days: number }) {
    if (days < 0) {
        return <span className="ml-2 inline-block rounded-md bg-danger-50 px-1.5 py-0.5 text-[10px] font-semibold text-danger-700 dark:bg-danger-900/30 dark:text-danger-300">+{Math.abs(days)}j</span>;
    }
    if (days <= 90) {
        return <span className="ml-2 inline-block rounded-md bg-warning-50 px-1.5 py-0.5 text-[10px] font-semibold text-warning-700 dark:bg-warning-900/30 dark:text-warning-300">{days}j</span>;
    }
    return null;
}

const MONTHS_SHORT = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
const MONTHS_LONG = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

function monthShort(iso: string): string {
    const m = parseInt(iso.slice(5, 7), 10) - 1;
    return MONTHS_SHORT[m] ?? '';
}
function monthLong(iso: string): string {
    const m = parseInt(iso.slice(5, 7), 10) - 1;
    const y = iso.slice(0, 4);
    return `${MONTHS_LONG[m] ?? ''} ${y}`;
}
function dayOf(iso: string): string {
    return iso.slice(8, 10);
}

function AcademicIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
            <path d="M6 12v5c3 3 9 3 12 0v-5" />
        </svg>
    );
}
function CheckIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function ClockIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function AlertIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
            <line x1="12" y1="9" x2="12" y2="13" />
            <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
    );
}
function CalendarIcon() {
    return (
        <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
    );
}
