import { Badge, Card, EmptyState, KpiCard, PageHeader } from '@/components/ui';
import { Link, router } from '@inertiajs/react';
import { useMemo, useState, type ReactNode } from 'react';
import DashboardLayout from '../layout';

type EventKind = 'intervention' | 'incident' | 'care_plan' | 'assignment_on' | 'assignment_off';

interface TimelineEvent {
    id: string;
    kind: EventKind;
    occurred_at: string;
    title: string;
    description: string;
    status: string;
    gravite?: string;
    link: string;
}

interface Beneficiary {
    id: string;
    full_name: string;
    initials: string;
    age: number | null;
    gir: number | null;
    city: string | null;
    status: string | null;
    status_label: string | null;
}

interface Props {
    beneficiary: { data: Beneficiary } | Beneficiary;
    events: TimelineEvent[];
    totals: {
        interventions: number;
        incidents: number;
        care_plans: number;
        assignments: number;
    };
    range: {
        months: number;
        unbounded: boolean;
        since: string | null;
    };
}

const KIND_META: Record<
    EventKind,
    {
        label: string;
        tone: 'brand' | 'sage' | 'warning' | 'danger' | 'neutral';
        icon: () => ReactNode;
        dot: string;
    }
> = {
    intervention: { label: 'Intervention', tone: 'brand', icon: ClipboardIcon, dot: 'bg-brand-500' },
    incident: { label: 'Incident', tone: 'danger', icon: AlertIcon, dot: 'bg-danger-500' },
    care_plan: { label: 'Plan de soins', tone: 'sage', icon: HeartIcon, dot: 'bg-sage-500' },
    assignment_on: { label: 'Affectation', tone: 'warning', icon: UserPlusIcon, dot: 'bg-warning-500' },
    assignment_off: { label: 'Désaffectation', tone: 'neutral', icon: UserMinusIcon, dot: 'bg-ink-400' },
};

const FILTERS: Array<{ key: EventKind | 'all'; label: string }> = [
    { key: 'all', label: 'Tous les événements' },
    { key: 'intervention', label: 'Interventions' },
    { key: 'incident', label: 'Incidents' },
    { key: 'care_plan', label: 'Plans de soins' },
    { key: 'assignment_on', label: 'Affectations' },
];

function formatDate(iso: string): string {
    try {
        return new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });
    } catch {
        return iso;
    }
}

function formatTime(iso: string): string {
    try {
        return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    } catch {
        return '';
    }
}

function unwrap<T>(maybeWrapped: { data: T } | T): T {
    if (maybeWrapped && typeof maybeWrapped === 'object' && 'data' in (maybeWrapped as Record<string, unknown>)) {
        return (maybeWrapped as { data: T }).data;
    }
    return maybeWrapped as T;
}

export default function BeneficiaryTimeline({ beneficiary, events, totals, range }: Props) {
    const b = unwrap(beneficiary);
    const [filter, setFilter] = useState<EventKind | 'all'>('all');
    const [search, setSearch] = useState('');

    const RANGE_OPTIONS: Array<{ months?: number; all?: boolean; label: string }> = [
        { months: 3, label: '3 mois' },
        { months: 6, label: '6 mois' },
        { months: 12, label: '12 mois' },
        { months: 24, label: '24 mois' },
        { all: true, label: 'Tout l\'historique' },
    ];

    const setRange = (opt: { months?: number; all?: boolean }) => {
        const url = `/beneficiaries/${b.id}/timeline?` + (opt.all ? 'all=1' : `months=${opt.months}`);
        router.visit(url, { preserveScroll: true });
    };

    const isActiveRange = (opt: { months?: number; all?: boolean }): boolean =>
        opt.all ? range.unbounded : !range.unbounded && opt.months === range.months;

    const filtered = useMemo(() => {
        const needle = search.trim().toLowerCase();
        return events.filter((e) => {
            if (filter !== 'all' && e.kind !== filter) return false;
            if (!needle) return true;
            return (
                e.title.toLowerCase().includes(needle) ||
                e.description.toLowerCase().includes(needle)
            );
        });
    }, [filter, events, search]);

    const grouped = useMemo(() => {
        const map = new Map<string, TimelineEvent[]>();
        for (const event of filtered) {
            const day = event.occurred_at.slice(0, 10);
            if (!map.has(day)) {
                map.set(day, []);
            }
            map.get(day)!.push(event);
        }
        return Array.from(map.entries()).sort((a, b) => b[0].localeCompare(a[0]));
    }, [filtered]);

    return (
        <DashboardLayout title={`Parcours · ${b.full_name}`} subtitle="Frise chronologique unifiée">
            <PageHeader
                title={`Parcours de ${b.full_name}`}
                subtitle={`${events.length} événement${events.length > 1 ? 's' : ''} agrégé${events.length > 1 ? 's' : ''} — interventions, incidents, plans de soins et affectations.`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: b.full_name, href: `/beneficiaries/${b.id}` },
                    { label: 'Parcours' },
                ]}
                actions={
                    <Link
                        href={`/beneficiaries/${b.id}`}
                        className="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                    >
                        ← Retour à la fiche
                    </Link>
                }
            />

            {/* KPI row */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <KpiCard
                    label="Interventions"
                    value={totals.interventions}
                    sub="Visites réalisées + planifiées"
                    icon={<ClipboardIcon />}
                    tone="brand"
                    hint="Toutes les visites à domicile inscrites au planning de ce bénéficiaire, tous statuts confondus. La frise affiche les 120 plus récentes."
                />
                <KpiCard
                    label="Incidents"
                    value={totals.incidents}
                    sub="Toutes gravités confondues"
                    icon={<AlertIcon />}
                    tone={totals.incidents > 0 ? 'danger' : 'neutral'}
                    hint="Événements indésirables déclarés pour ce bénéficiaire. Les niveaux « grave » ou « critique » ont déclenché une notification ARS automatique sous 48 h."
                />
                <KpiCard
                    label="Plans de soins"
                    value={totals.care_plans}
                    sub="Brouillons, actifs, archivés"
                    icon={<HeartIcon />}
                    tone="sage"
                    hint="Historique des plans de soins individualisés. Le plan actif détermine les tâches récurrentes à cocher à chaque visite."
                />
                <KpiCard
                    label="Affectations"
                    value={totals.assignments}
                    sub="Intervenants assignés"
                    icon={<UserPlusIcon />}
                    tone="warning"
                    hint="Toutes les affectations d'intervenants pour ce bénéficiaire (en cours + clôturées). Garantit la continuité du suivi à domicile."
                />
            </div>

            {/* Range picker */}
            <Card className="mt-6">
                <div className="flex flex-wrap items-center gap-2 border-b border-ink-100 p-4 dark:border-ink-700/60">
                    <span className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                        Période
                    </span>
                    {RANGE_OPTIONS.map((opt) => {
                        const active = isActiveRange(opt);
                        return (
                            <button
                                key={opt.label}
                                type="button"
                                onClick={() => setRange(opt)}
                                className={
                                    active
                                        ? 'inline-flex items-center rounded-full bg-ink-900 px-3 py-1 text-xs font-semibold text-white transition-colors dark:bg-white dark:text-ink-900'
                                        : 'inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1 text-xs font-medium text-ink-700 transition-colors hover:border-ink-400 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200'
                                }
                            >
                                {opt.label}
                            </button>
                        );
                    })}
                </div>

                {/* Search */}
                <div className="border-b border-ink-100 p-4 dark:border-ink-700/60">
                    <div className="relative">
                        <input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher dans les événements (titre, description)…"
                            className="block w-full rounded-lg border border-ink-200 bg-white pl-9 pr-3 py-2 text-sm text-ink-900 placeholder:text-ink-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-700 dark:bg-ink-800 dark:text-white"
                        />
                        <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-400">
                            <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2}>
                                <circle cx="11" cy="11" r="8" />
                                <path d="M21 21l-4.35-4.35" strokeLinecap="round" />
                            </svg>
                        </span>
                    </div>
                </div>

                {/* Filter chips */}
                <div className="flex flex-wrap items-center gap-2 p-4">
                    {FILTERS.map((opt) => {
                        const count = opt.key === 'all' ? events.length : events.filter((e) => e.kind === opt.key).length;
                        const active = filter === opt.key;
                        return (
                            <button
                                key={opt.key}
                                type="button"
                                onClick={() => setFilter(opt.key)}
                                className={
                                    active
                                        ? 'inline-flex items-center gap-2 rounded-full bg-brand-600 px-4 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-brand-700'
                                        : 'inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-1.5 text-xs font-semibold text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200'
                                }
                            >
                                {opt.label}
                                <span
                                    className={
                                        'rounded-full px-1.5 py-0.5 text-[10px] font-mono ' +
                                        (active ? 'bg-white/20 text-white' : 'bg-ink-100 text-ink-500 dark:bg-ink-700 dark:text-ink-300')
                                    }
                                >
                                    {count}
                                </span>
                            </button>
                        );
                    })}
                </div>
            </Card>

            {/* Timeline */}
            <div className="mt-6">
                {filtered.length === 0 ? (
                    <Card>
                        <EmptyState
                            title="Aucun événement"
                            description={
                                events.length === 0
                                    ? 'Ce bénéficiaire n\'a encore aucun événement enregistré.'
                                    : 'Aucun événement ne correspond à ce filtre.'
                            }
                        />
                    </Card>
                ) : (
                    <ol className="space-y-10">
                        {grouped.map(([day, dayEvents]) => (
                            <li key={day}>
                                <div className="sticky top-0 z-10 mb-3 inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white/95 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-ink-700 shadow-sm backdrop-blur dark:border-ink-700 dark:bg-ink-800/95 dark:text-ink-200">
                                    <CalendarIcon />
                                    {formatDate(day)}
                                    <span className="text-ink-400 dark:text-ink-500">·</span>
                                    <span className="font-mono text-ink-500 dark:text-ink-400">
                                        {dayEvents.length} événement{dayEvents.length > 1 ? 's' : ''}
                                    </span>
                                </div>
                                <ul className="relative space-y-3 border-l-2 border-ink-100 pl-5 dark:border-ink-700/60">
                                    {dayEvents.map((event) => {
                                        const meta = KIND_META[event.kind];
                                        const Icon = meta.icon;
                                        return (
                                            <li key={event.id} className="relative">
                                                <span
                                                    className={
                                                        'absolute -left-[1.65rem] mt-3 flex size-5 items-center justify-center rounded-full border-2 border-white dark:border-ink-900 ' +
                                                        meta.dot
                                                    }
                                                />
                                                <Link
                                                    href={event.link}
                                                    className="block rounded-2xl border border-ink-100 bg-white p-4 transition-all hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[0_4px_24px_rgba(15,23,42,0.06)] dark:border-ink-700/60 dark:bg-ink-800 dark:hover:border-brand-500"
                                                >
                                                    <div className="flex items-start gap-3">
                                                        <div className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-ink-50 text-ink-600 dark:bg-ink-700 dark:text-ink-300">
                                                            <Icon />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <Badge tone={meta.tone} size="xs">
                                                                    {meta.label}
                                                                </Badge>
                                                                {event.gravite && (
                                                                    <Badge
                                                                        tone={
                                                                            event.gravite === 'critique'
                                                                                ? 'danger'
                                                                                : event.gravite === 'grave'
                                                                                  ? 'warning'
                                                                                  : 'neutral'
                                                                        }
                                                                        size="xs"
                                                                    >
                                                                        {event.gravite}
                                                                    </Badge>
                                                                )}
                                                                <span className="font-mono text-[11px] text-ink-400 dark:text-ink-500">
                                                                    {formatTime(event.occurred_at)}
                                                                </span>
                                                            </div>
                                                            <p className="mt-1 text-sm font-semibold text-ink-900 dark:text-white">
                                                                {event.title}
                                                            </p>
                                                            <p className="mt-1 text-sm leading-relaxed text-ink-600 dark:text-ink-400">
                                                                {event.description}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </li>
                        ))}
                    </ol>
                )}
            </div>
        </DashboardLayout>
    );
}

// ─── Icons ─────────────────────────────────────────────────────────────────
function ClipboardIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function AlertIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" strokeLinecap="round" strokeLinejoin="round" />
            <line x1="12" y1="9" x2="12" y2="13" strokeLinecap="round" />
            <line x1="12" y1="17" x2="12.01" y2="17" strokeLinecap="round" />
        </svg>
    );
}
function HeartIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function UserPlusIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM20 8v6M23 11h-6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function UserMinusIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 11h-6" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function CalendarIcon() {
    return (
        <svg className="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
    );
}
