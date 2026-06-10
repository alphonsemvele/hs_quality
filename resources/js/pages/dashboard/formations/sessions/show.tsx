import { useTrackRecent } from '@/components/RecentlyViewed';
import { Badge, Button, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import DashboardLayout from '../../layout';

interface SessionDetail {
    id: string;
    title: string;
    plan_title: string;
    date: string;
    time: string;
    duration_minutes: number;
    location: string;
    capacity: number;
    registered: number;
    attended: number;
    status: 'scheduled' | 'full' | 'completed' | 'cancelled';
    organisme: string;
    description: string;
}

interface Attendee {
    id: string;
    user_id: string;
    name: string;
    initials: string;
    role: string;
    status: 'registered' | 'attended' | 'cancelled' | 'absent';
    status_label: string;
    last_psc1: string | null;
}

interface EligibleUser {
    id: number;
    full_name: string;
}

interface Props {
    session: SessionDetail | null;
    attendees: Attendee[];
    eligible_users: EligibleUser[];
}

const STATUS_TONE: Record<Attendee['status'], 'sage' | 'brand' | 'warning' | 'danger'> = {
    attended: 'sage',
    registered: 'brand',
    cancelled: 'warning',
    absent: 'danger',
};

export default function SessionShow({ session, attendees = [], eligible_users = [] }: Partial<Props>) {
    useTrackRecent(
        session
            ? {
                kind: 'formation',
                id: session.id,
                label: `${session.title} · ${session.date}`,
                href: `/formations/sessions/${session.id}`,
            }
            : null,
    );

    if (!session) {
        return (
            <DashboardLayout title="Session de formation" subtitle="">
                <PageHeader
                    title="Session introuvable"
                    breadcrumb={[
                        { label: 'Tableau de bord', href: '/dashboard' },
                        { label: 'Formations', href: '/formations' },
                    ]}
                />
                <Card>
                    <EmptyState title="Session non disponible" description="Cette session n'existe pas ou a été supprimée." />
                </Card>
            </DashboardLayout>
        );
    }

    const markAttended = (attendanceId: string) => {
        router.post(`/formations/attendances/${attendanceId}/mark-attended`, undefined, { preserveScroll: true });
    };

    const cancelAttendance = (attendanceId: string) => {
        router.post(`/formations/attendances/${attendanceId}/cancel`, undefined, { preserveScroll: true });
    };

    const activeAttendees = attendees.filter((a) => a.status !== 'cancelled');
    const present = attendees.filter((a) => a.status === 'attended').length;
    const fillPct = (session.registered / Math.max(session.capacity, 1)) * 100;

    return (
        <DashboardLayout title={session.title} subtitle="Détail de session">
            <PageHeader
                title={session.title}
                subtitle={`${session.plan_title} · ${session.organisme}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Formations', href: '/formations' },
                    { label: 'Sessions' },
                    { label: session.title },
                ]}
                actions={
                    <Button variant="secondary" onClick={() => router.visit('/formations')}>
                        ← Retour aux formations
                    </Button>
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                {/* Session metadata */}
                <Card className="lg:col-span-1">
                    <CardHeader title="Informations" subtitle={`${session.status}`} />
                    <CardBody className="space-y-3">
                        <Row label="Date" value={new Date(session.date).toLocaleDateString('fr-FR', { dateStyle: 'long' })} />
                        <Row label="Horaire" value={`${session.time} · ${session.duration_minutes} min`} />
                        <Row label="Lieu" value={session.location} />
                        <Row label="Organisme" value={session.organisme} />
                        <div>
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                                Description
                            </p>
                            <p className="mt-1 text-xs leading-relaxed text-ink-700 dark:text-ink-200">{session.description}</p>
                        </div>
                    </CardBody>
                </Card>

                {/* KPIs */}
                <div className="lg:col-span-2 space-y-5">
                    <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                        <KpiCard label="Inscrits" value={session.registered} tone="brand" progress={fillPct} sub={`/ ${session.capacity} max`} />
                        <KpiCard label="Présents" value={present} tone="sage" sub={`${Math.round((present / Math.max(session.registered, 1)) * 100)}% du roster`} />
                        <KpiCard label="Annulations" value={attendees.filter((a) => a.status === 'cancelled').length} tone="warning" />
                        <KpiCard label="Capacité" value={`${session.capacity} pl.`} tone="neutral" />
                    </div>

                    {/* Roster */}
                    <Card>
                        <CardHeader
                            title="Émargement"
                            subtitle={`${activeAttendees.length} participant${activeAttendees.length > 1 ? 's' : ''} actif${activeAttendees.length > 1 ? 's' : ''}`}
                            action={
                                eligible_users.length > 0 ? (
                                    <form
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            const fd = new FormData(e.currentTarget);
                                            const userId = fd.get('user_id');
                                            if (!userId) return;
                                            router.post(
                                                `/formations/sessions/${session.id}/attendances`,
                                                { user_id: Number(userId) },
                                                { preserveScroll: true, onSuccess: () => e.currentTarget?.reset() },
                                            );
                                        }}
                                        className="flex items-end gap-2"
                                    >
                                        <select
                                            name="user_id"
                                            defaultValue=""
                                            required
                                            className="h-9 rounded-lg border border-ink-200 bg-white px-2 text-xs text-ink-900 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                                        >
                                            <option value="" disabled>
                                                Sélectionner
                                            </option>
                                            {eligible_users.map((u) => (
                                                <option key={u.id} value={u.id}>
                                                    {u.full_name}
                                                </option>
                                            ))}
                                        </select>
                                        <Button type="submit" size="sm">
                                            Inscrire
                                        </Button>
                                    </form>
                                ) : undefined
                            }
                        />
                        <CardBody className="px-0">
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {attendees.map((a) => (
                                    <li key={a.id} className={cn('flex items-center gap-3 px-5 py-3', a.status === 'cancelled' && 'opacity-50')}>
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white">
                                            {a.initials}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium text-ink-900 dark:text-white">{a.name}</p>
                                            <p className="text-[11px] text-ink-500 dark:text-ink-400">
                                                {a.role}
                                                {a.last_psc1 && ` · PSC1 obtenu le ${a.last_psc1}`}
                                            </p>
                                        </div>
                                        <Badge tone={STATUS_TONE[a.status]} size="sm" dot>
                                            {a.status_label}
                                        </Badge>
                                        {a.status === 'registered' && (
                                            <div className="flex gap-1">
                                                <Button variant="secondary" size="sm" onClick={() => markAttended(a.id)}>
                                                    ✓ Présent
                                                </Button>
                                                <Button variant="ghost" size="sm" onClick={() => cancelAttendance(a.id)}>
                                                    Annuler
                                                </Button>
                                            </div>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </CardBody>
                    </Card>
                </div>
            </div>
        </DashboardLayout>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div>
            <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</p>
            <p className="mt-0.5 text-sm text-ink-900 dark:text-white">{value}</p>
        </div>
    );
}
