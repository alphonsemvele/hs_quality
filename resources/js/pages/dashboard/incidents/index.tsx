import { IncidentHoverCard } from '@/components/hover-cards';
import { IncidentHeatmap } from '@/components/IncidentHeatmap';
import { IncidentPreviewSheet, type IncidentPreview } from '@/components/preview-sheets';
import {
    Badge,
    Button,
    Card,
    EmptyState,
    IncidentGraviteBadge,
    IncidentStatusBadge,
    InlineHelp,
    KpiCard,
    PageHeader,
    Pagination,
} from '@/components/ui';
import { useCan } from '@/lib/can';
import { useUrlBoolFilter, useUrlFilter } from '@/lib/use-url-filter';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

type Gravite = 'mineur' | 'significatif' | 'grave' | 'critique';
type Statut = 'declare' | 'en_analyse' | 'plan_actions' | 'clos';

const GRAVITE_VALUES = ['all', 'mineur', 'significatif', 'grave', 'critique'] as const;
const STATUT_VALUES = ['all', 'declare', 'en_analyse', 'plan_actions', 'clos'] as const;
type GraviteFilter = (typeof GRAVITE_VALUES)[number];
type StatutFilter = (typeof STATUT_VALUES)[number];

interface Incident {
    id: number | string;
    initials: string;
    declarant: string;
    categorie: string;
    gravite: Gravite;
    statut: Statut;
    structure: string;
    date_heure: string;
    description: string;
    notifie_responsable: boolean;
    notifie_autorites: boolean;
}

interface Props {
    incidents: Incident[];
    total: number;
    pagination: { current_page: number; last_page: number; per_page: number };
    stats: { declare: number; en_analyse: number; plan_actions: number; clos: number; graves: number };
}

export default function Incidents({
    incidents = [],
    total = 0,
    pagination = { current_page: 1, last_page: 1, per_page: 20 },
    stats = { declare: 0, en_analyse: 0, plan_actions: 0, clos: 0, graves: 0 },
}: Partial<Props>) {
    const canDeclare = useCan('incidents.create');
    const [preview, setPreview] = useState<IncidentPreview | null>(null);
    const [graviteFilter, setGraviteFilter] = useUrlFilter<GraviteFilter>('gravite', 'all', GRAVITE_VALUES);
    const [statutFilter, setStatutFilter] = useUrlFilter<StatutFilter>('statut', 'all', STATUT_VALUES);
    const [arsOnly, setArsOnly] = useUrlBoolFilter('ars_only', false);

    const filteredIncidents = incidents.filter((inc) => {
        if (graviteFilter !== 'all' && inc.gravite !== graviteFilter) return false;
        if (statutFilter !== 'all' && inc.statut !== statutFilter) return false;
        if (arsOnly && !inc.notifie_autorites) return false;
        return true;
    });

    const resetFilters = () => {
        setGraviteFilter('all');
        setStatutFilter('all');
        setArsOnly(false);
    };

    const hasActiveFilter = graviteFilter !== 'all' || statutFilter !== 'all' || arsOnly;

    return (
        <DashboardLayout title="Incidents & événements indésirables" subtitle="Déclaration, analyse et suivi">
            <PageHeader
                title="Incidents"
                subtitle="Toute déclaration est auditée et conservée 10 ans (CDC §6)"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Incidents' }]}
                actions={
                    canDeclare ? (
                        <Link href="/incidents/create">
                            <Button variant="danger" leadingIcon={<PlusIcon />}>
                                Déclarer un incident
                            </Button>
                        </Link>
                    ) : null
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
                <KpiCard label="Déclarés" value={stats.declare} tone="danger" />
                <KpiCard label="En analyse" value={stats.en_analyse} tone="warning" />
                <KpiCard label="Plan d'actions" value={stats.plan_actions} tone="brand" />
                <KpiCard label="Clos" value={stats.clos} tone="neutral" />
                <KpiCard label="Graves / critiques" value={stats.graves} tone="danger" />
            </div>

            {incidents.length > 0 && (
                <Card className="mb-6">
                    <div className="space-y-3 p-5">
                        <div>
                            <h3 className="text-sm font-semibold text-ink-900 dark:text-white">Densité sur 12 semaines</h3>
                            <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                Survolez une cellule pour voir le détail journalier. La case d'aujourd'hui est cerclée.
                            </p>
                        </div>
                        <IncidentHeatmap incidents={incidents} />
                    </div>
                </Card>
            )}

            <div className="mb-4 flex flex-wrap items-center gap-2">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                    Filtres
                </span>
                <FilterChipGroup
                    label="Gravité"
                    value={graviteFilter}
                    onChange={(v) => setGraviteFilter(v as 'all' | Gravite)}
                    options={[
                        { value: 'all', label: 'Toutes' },
                        { value: 'mineur', label: 'Mineur' },
                        { value: 'significatif', label: 'Significatif' },
                        { value: 'grave', label: 'Grave' },
                        { value: 'critique', label: 'Critique' },
                    ]}
                />
                <FilterChipGroup
                    label="Statut"
                    value={statutFilter}
                    onChange={(v) => setStatutFilter(v as 'all' | Statut)}
                    options={[
                        { value: 'all', label: 'Tous' },
                        { value: 'declare', label: 'Déclaré' },
                        { value: 'en_analyse', label: 'En analyse' },
                        { value: 'plan_actions', label: "Plan d'actions" },
                        { value: 'clos', label: 'Clos' },
                    ]}
                />
                <div className="inline-flex items-center">
                    <button
                        type="button"
                        onClick={() => setArsOnly(!arsOnly)}
                        aria-pressed={arsOnly}
                        className={
                            arsOnly
                                ? 'inline-flex items-center rounded-full bg-danger-600 px-3 py-1 text-[11px] font-semibold text-white transition-colors'
                                : 'inline-flex items-center rounded-full border border-ink-200 bg-white px-3 py-1 text-[11px] font-medium text-ink-700 transition-colors hover:border-ink-400 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200'
                        }
                    >
                        ARS notifiée
                    </button>
                    <InlineHelp>L'ARS (Agence Régionale de Santé) doit être notifiée sous 24h pour tout événement indésirable grave ou critique impliquant un bénéficiaire (loi 2002-2, art. L1413-14).</InlineHelp>
                </div>
                {hasActiveFilter && (
                    <button
                        type="button"
                        onClick={resetFilters}
                        className="text-[11px] font-medium text-brand-600 underline-offset-2 hover:underline dark:text-brand-400"
                    >
                        Réinitialiser
                    </button>
                )}
            </div>

            <h2 className="mb-3 text-sm font-semibold text-ink-900 dark:text-white">
                {filteredIncidents.length === total
                    ? `${total} incident(s)`
                    : `${filteredIncidents.length} affiché(s) sur ${total}`}
            </h2>

            {filteredIncidents.length > 0 ? (
                <ul className="space-y-3">
                    {filteredIncidents.map((inc) => (
                        <Card key={inc.id} className="hover:shadow-md">
                            <div className="flex items-start gap-4 p-5">
                                <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                    {inc.initials || '?'}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <IncidentHoverCard
                                            title={inc.categorie}
                                            categorie={inc.categorie}
                                            gravite={inc.gravite}
                                            statut={inc.statut}
                                            occurredAt={inc.date_heure}
                                            declaredBy={inc.declarant}
                                            href={`/incidents/${inc.id}`}
                                        />
                                        <IncidentGraviteBadge gravite={inc.gravite} />
                                        <IncidentStatusBadge statut={inc.statut} />
                                        {inc.notifie_autorites && (
                                            <Badge tone="danger" size="xs">
                                                ARS notifiée
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="mt-1 line-clamp-2 text-sm text-ink-600 dark:text-ink-400">{inc.description}</p>
                                    <div className="mt-2 flex flex-wrap items-center gap-3 text-xs text-ink-500 dark:text-ink-400">
                                        <span>Par {inc.declarant}</span>
                                        <span>{inc.structure}</span>
                                        <span className="font-mono">{inc.date_heure}</span>
                                    </div>
                                </div>
                                <div className="flex shrink-0 items-center gap-1 self-center">
                                    <button
                                        type="button"
                                        onClick={() => setPreview(inc)}
                                        aria-label="Aperçu rapide"
                                        className="rounded-md p-1.5 text-ink-400 transition-colors hover:bg-ink-100 hover:text-danger-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger-500/40 dark:text-ink-500 dark:hover:bg-ink-700 dark:hover:text-danger-400"
                                    >
                                        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                    <Link
                                        href={`/incidents/${inc.id}`}
                                        className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                    >
                                        Traiter →
                                    </Link>
                                </div>
                            </div>
                        </Card>
                    ))}
                </ul>
            ) : (
                <Card>
                    <EmptyState title="Aucun incident déclaré" description="Les déclarations apparaîtront ici dès qu'elles seront enregistrées." />
                </Card>
            )}

            <Pagination
                currentPage={pagination.current_page}
                lastPage={pagination.last_page}
                total={total}
                perPage={pagination.per_page}
            />

            <IncidentPreviewSheet incident={preview} onClose={() => setPreview(null)} />
        </DashboardLayout>
    );
}

function FilterChipGroup({
    label,
    value,
    onChange,
    options,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    options: Array<{ value: string; label: string }>;
}) {
    return (
        <div className="flex items-center gap-1 rounded-full border border-ink-100 bg-white p-0.5 dark:border-ink-700 dark:bg-ink-800">
            <span className="px-2 text-[10px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                {label}
            </span>
            {options.map((opt) => (
                <button
                    key={opt.value}
                    type="button"
                    onClick={() => onChange(opt.value)}
                    aria-pressed={value === opt.value}
                    className={
                        value === opt.value
                            ? 'rounded-full bg-ink-900 px-2.5 py-0.5 text-[11px] font-semibold text-white dark:bg-white dark:text-ink-900'
                            : 'rounded-full px-2.5 py-0.5 text-[11px] font-medium text-ink-600 transition-colors hover:text-ink-900 dark:text-ink-300 dark:hover:text-white'
                    }
                >
                    {opt.label}
                </button>
            ))}
        </div>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}
