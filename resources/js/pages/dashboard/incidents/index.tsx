import {
    Badge,
    Button,
    Card,
    EmptyState,
    IncidentGraviteBadge,
    IncidentStatusBadge,
    KpiCard,
    PageHeader,
} from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

type Gravite = 'mineur' | 'significatif' | 'grave' | 'critique';
type Statut = 'declare' | 'en_analyse' | 'plan_actions' | 'clos';

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
    stats: { declare: number; en_analyse: number; plan_actions: number; clos: number; graves: number };
}

export default function Incidents({
    incidents = [],
    total = 0,
    stats = { declare: 0, en_analyse: 0, plan_actions: 0, clos: 0, graves: 0 },
}: Partial<Props>) {
    return (
        <DashboardLayout title="Incidents & événements indésirables" subtitle="Déclaration, analyse et suivi">
            <PageHeader
                title="Incidents"
                subtitle="Toute déclaration est auditée et conservée 10 ans (CDC §6)"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Incidents' }]}
                actions={
                    <Link href="/incidents/create">
                        <Button variant="danger" leadingIcon={<PlusIcon />}>
                            Déclarer un incident
                        </Button>
                    </Link>
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-5">
                <KpiCard label="Déclarés" value={stats.declare} tone="danger" />
                <KpiCard label="En analyse" value={stats.en_analyse} tone="warning" />
                <KpiCard label="Plan d'actions" value={stats.plan_actions} tone="brand" />
                <KpiCard label="Clos" value={stats.clos} tone="neutral" />
                <KpiCard label="Graves / critiques" value={stats.graves} tone="danger" />
            </div>

            <h2 className="mb-3 text-sm font-semibold text-ink-900">{total} incident(s)</h2>

            {incidents.length > 0 ? (
                <ul className="space-y-3">
                    {incidents.map((inc) => (
                        <Card key={inc.id} className="hover:shadow-md">
                            <div className="flex items-start gap-4 p-5">
                                <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-semibold text-brand-700">
                                    {inc.initials || '?'}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-sm font-semibold text-ink-900">{inc.categorie}</span>
                                        <IncidentGraviteBadge gravite={inc.gravite} />
                                        <IncidentStatusBadge statut={inc.statut} />
                                        {inc.notifie_autorites && (
                                            <Badge tone="danger" size="xs">
                                                ARS notifiée
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="mt-1 line-clamp-2 text-sm text-ink-600">{inc.description}</p>
                                    <div className="mt-2 flex flex-wrap items-center gap-3 text-xs text-ink-500">
                                        <span>Par {inc.declarant}</span>
                                        <span>{inc.structure}</span>
                                        <span className="font-mono">{inc.date_heure}</span>
                                    </div>
                                </div>
                                <Link
                                    href={`/incidents/${inc.id}`}
                                    className="shrink-0 self-center text-sm font-medium text-brand-600 hover:text-brand-700"
                                >
                                    Traiter →
                                </Link>
                            </div>
                        </Card>
                    ))}
                </ul>
            ) : (
                <Card>
                    <EmptyState title="Aucun incident déclaré" description="Les déclarations apparaîtront ici dès qu'elles seront enregistrées." />
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
