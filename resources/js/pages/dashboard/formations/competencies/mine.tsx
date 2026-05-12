import { Badge, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface Competency {
    id: string;
    intitule: string;
    organisme: string | null;
    date_obtention: string | null;
    date_expiration: string | null;
    days_to_expiry: number;
    statut: 'valide' | 'expire_bientot' | 'expiree';
}

interface Enrollment {
    id: string;
    session_id: string;
    title: string;
    date: string;
    location: string;
    status: 'registered' | 'attended' | 'cancelled';
    status_label: string;
}

interface Props {
    me: { name: string; role: string };
    habilitations: Competency[];
    certifications: Competency[];
    enrollments: Enrollment[];
}

const STATUT_TONE: Record<string, 'sage' | 'warning' | 'danger'> = {
    valide: 'sage',
    expire_bientot: 'warning',
    expiree: 'danger',
};

export default function MyCompetencies({ me, habilitations = [], certifications = [], enrollments = [] }: Partial<Props>) {
    const all = [...habilitations, ...certifications];
    const expiring = all.filter((c) => c.statut === 'expire_bientot');
    const expired = all.filter((c) => c.statut === 'expiree');
    const valid = all.filter((c) => c.statut === 'valide');

    return (
        <DashboardLayout title="Mes compétences" subtitle="Habilitations, certifications et inscriptions">
            <PageHeader
                title="Mes compétences"
                subtitle={me ? `${me.name} · ${me.role}` : 'Vue personnelle'}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Formations', href: '/formations' },
                    { label: 'Mes compétences' },
                ]}
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard label="Total" value={all.length} icon={<AcademicIcon />} tone="brand" />
                <KpiCard label="À jour" value={valid.length} icon={<CheckIcon />} tone="sage" />
                <KpiCard
                    label="Expire bientôt"
                    value={expiring.length}
                    icon={<ClockIcon />}
                    tone={expiring.length > 0 ? 'warning' : 'sage'}
                    sub={expiring.length > 0 ? 'Renouvellement à planifier' : undefined}
                />
                <KpiCard
                    label="Expirées"
                    value={expired.length}
                    icon={<AlertIcon />}
                    tone={expired.length > 0 ? 'danger' : 'sage'}
                />
            </div>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Inscriptions actives */}
                <Card className="lg:col-span-1">
                    <CardHeader title="Mes prochaines sessions" subtitle={`${enrollments.length} inscription${enrollments.length > 1 ? 's' : ''}`} />
                    <CardBody className="px-2 py-2">
                        {enrollments.length > 0 ? (
                            <ul className="flex flex-col gap-1">
                                {enrollments.map((e) => (
                                    <li key={e.id}>
                                        <Link
                                            href={`/formations/sessions/${e.session_id}`}
                                            className="block rounded-lg border border-ink-100 px-3 py-2 transition-colors hover:border-brand-300 hover:bg-brand-50/40 dark:border-ink-700/60 dark:hover:border-brand-500 dark:hover:bg-brand-900/20"
                                        >
                                            <div className="flex items-center justify-between gap-2">
                                                <p className="truncate text-sm font-medium text-ink-900 dark:text-white">{e.title}</p>
                                                <Badge tone="brand" size="xs">{e.status_label}</Badge>
                                            </div>
                                            <p className="font-mono text-[10px] text-ink-500 dark:text-ink-400">
                                                {new Date(e.date).toLocaleDateString('fr-FR', { dateStyle: 'medium' })} · {e.location}
                                            </p>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucune inscription" description="Vous n'êtes inscrit·e à aucune session à venir." />
                        )}
                    </CardBody>
                </Card>

                {/* Habilitations + certifications */}
                <div className="lg:col-span-2 space-y-5">
                    <CompetencyCard
                        title="Habilitations"
                        subtitle="Diplômes et habilitations (long terme)"
                        items={habilitations}
                    />
                    <CompetencyCard
                        title="Certifications"
                        subtitle="Renouvellement périodique requis"
                        items={certifications}
                    />
                </div>
            </div>
        </DashboardLayout>
    );
}

function CompetencyCard({ title, subtitle, items }: { title: string; subtitle: string; items: Competency[] }) {
    return (
        <Card>
            <CardHeader title={title} subtitle={`${items.length} · ${subtitle}`} />
            <CardBody className="px-0">
                {items.length > 0 ? (
                    <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                        {items.map((c) => (
                            <li key={c.id} className="flex items-start gap-3 px-5 py-3">
                                <span className={cn('mt-1 size-2 shrink-0 rounded-full', c.statut === 'valide' ? 'bg-sage-500' : c.statut === 'expire_bientot' ? 'bg-warning-500' : 'bg-danger-500')} />
                                <div className="min-w-0 flex-1">
                                    <p className="text-sm font-medium text-ink-900 dark:text-white">{c.intitule}</p>
                                    <p className="text-[11px] text-ink-500 dark:text-ink-400">
                                        {c.organisme ?? '—'}
                                        {c.date_obtention && ` · obtenu le ${c.date_obtention}`}
                                    </p>
                                </div>
                                <div className="shrink-0 text-right">
                                    <Badge tone={STATUT_TONE[c.statut]} size="sm" dot>
                                        {c.statut === 'valide' ? 'Valide' : c.statut === 'expire_bientot' ? 'Expire bientôt' : 'Expirée'}
                                    </Badge>
                                    {c.date_expiration && (
                                        <p className="mt-0.5 font-mono text-[10px] text-ink-500 dark:text-ink-400">
                                            {c.statut === 'expiree'
                                                ? `Expirée depuis ${Math.abs(c.days_to_expiry)} j`
                                                : c.statut === 'expire_bientot'
                                                    ? `Expire dans ${c.days_to_expiry} j`
                                                    : `Jusqu'au ${c.date_expiration}`}
                                        </p>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <EmptyState title={`Aucune ${title.toLowerCase()}`} description="Renseignez vos justificatifs auprès de votre RH." />
                )}
            </CardBody>
        </Card>
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
