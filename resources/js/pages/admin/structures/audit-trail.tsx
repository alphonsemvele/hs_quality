import { Badge, Card, CardBody, CardHeader, EmptyState, KpiCard, PageHeader, Pagination, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../../dashboard/layout';

interface StructureDetail {
    id: string;
    code: string;
    name: string;
    type_label: string;
    tier_label: string;
    status: string;
    status_label: string;
}

interface AuditRow {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: string;
    user_name: string;
    user_email: string | null;
    ip_address: string | null;
    changed_keys: string[];
    created_at: string;
    created_at_human: string;
}

interface Props {
    structure: { data: StructureDetail } | StructureDetail;
    rows: AuditRow[];
    pagination: { current_page: number; last_page: number; per_page: number; total: number };
    totals: { all_time: number; last_24h: number; last_7d: number };
}

const EVENT_META: Record<string, { tone: 'sage' | 'warning' | 'danger' | 'neutral' | 'brand'; label: string }> = {
    created: { tone: 'sage', label: 'Création' },
    updated: { tone: 'brand', label: 'Modification' },
    deleted: { tone: 'warning', label: 'Suppression' },
    restored: { tone: 'sage', label: 'Restauration' },
};

function unwrap<T>(maybe: { data: T } | T): T {
    if (maybe && typeof maybe === 'object' && 'data' in (maybe as Record<string, unknown>)) {
        return (maybe as { data: T }).data;
    }
    return maybe as T;
}

function formatDate(iso: string): string {
    try {
        return new Date(iso).toLocaleString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return iso;
    }
}

export default function StructureAuditTrail({ structure, rows, pagination, totals }: Props) {
    const s = unwrap(structure);

    return (
        <DashboardLayout
            title={`Audit-trail · ${s.name}`}
            subtitle="Historique complet des actions tenant — administration plateforme"
        >
            <PageHeader
                title={`Audit-trail · ${s.name}`}
                subtitle={`${pagination.total} événement${pagination.total > 1 ? 's' : ''} enregistré${pagination.total > 1 ? 's' : ''} pour ce tenant`}
                breadcrumb={[
                    { label: 'Plateforme', href: '/admin' },
                    { label: 'Structures', href: '/admin/structures' },
                    { label: s.name, href: `/admin/structures/${s.id}` },
                    { label: 'Audit-trail' },
                ]}
                actions={
                    <Link
                        href={`/admin/structures/${s.id}`}
                        className="inline-flex items-center gap-2 rounded-full border border-ink-200 bg-white px-4 py-2 text-sm font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                    >
                        ← Retour à la fiche
                    </Link>
                }
            />

            {/* Structure summary banner */}
            <Card>
                <div className="flex flex-wrap items-center justify-between gap-4 p-5">
                    <div>
                        <p className="text-sm font-semibold text-ink-900 dark:text-white">{s.name}</p>
                        <p className="mt-1 flex flex-wrap items-center gap-2 text-xs text-ink-500 dark:text-ink-400">
                            <span className="font-mono">{s.code}</span>
                            <span>·</span>
                            <span>{s.type_label}</span>
                            <Badge tone="brand" size="xs">
                                {s.tier_label}
                            </Badge>
                            <Badge tone={s.status === 'active' ? 'sage' : 'warning'} size="xs" dot>
                                {s.status_label}
                            </Badge>
                        </p>
                    </div>
                </div>
            </Card>

            {/* Stats */}
            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <KpiCard
                    label="Événements (total)"
                    value={totals.all_time}
                    sub="Depuis le provisionnement"
                    icon={<ListIcon />}
                    tone="brand"
                    hint="Nombre total d'entrées dans la table audits pour ce tenant — toutes auditables confondues (Structure, User, Beneficiary, CarePlan, Intervention, Incident, etc.)."
                />
                <KpiCard
                    label="Événements (7 jours)"
                    value={totals.last_7d}
                    sub="Activité hebdomadaire"
                    icon={<TrendIcon />}
                    tone="sage"
                    hint="Rythme normal d'un tenant actif. Une chute brutale peut signaler une indisponibilité opérationnelle ; une explosion peut signaler un import massif."
                />
                <KpiCard
                    label="Événements (24 h)"
                    value={totals.last_24h}
                    sub="Activité récente"
                    icon={<PulseIcon />}
                    tone={totals.last_24h > 0 ? 'warning' : 'neutral'}
                    hint="Activité sur le dernier jour. Au-delà de 200 événements, vérifier qu'il n'y a pas un boucle infinie ou un job qui s'emballe."
                />
            </div>

            {/* Audit rows */}
            <div className="mt-6">
                <Card>
                    <CardHeader>
                        <h3 className="text-sm font-semibold text-ink-900 dark:text-white">Événements récents</h3>
                        <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                            Lecture seule — la table {' '}
                            <span className="font-mono">audits</span> est immutable par conception (Article 30 RGPD).
                        </p>
                    </CardHeader>
                    <CardBody className="p-0">
                        {rows.length === 0 ? (
                            <EmptyState
                                title="Aucun événement"
                                description="Aucune activité auditée pour ce tenant. Probablement provisionné mais pas encore utilisé."
                            />
                        ) : (
                            <Table>
                                <THead>
                                    <Tr>
                                        <Th hint="Horodatage exact de l'action (fuseau Europe/Paris).">Quand</Th>
                                        <Th hint="Type d'action : created (création), updated (modification), deleted (suppression logique), restored (restauration). Chaque écriture sensible est tracée automatiquement.">
                                            Événement
                                        </Th>
                                        <Th hint="Type et identifiant de l'enregistrement touché : Structure, User, Beneficiary, CarePlan, etc.">
                                            Ressource
                                        </Th>
                                        <Th hint="Utilisateur authentifié à l'origine de l'action. « Système » indique un job automatique (cron, webhook, queue worker).">
                                            Utilisateur
                                        </Th>
                                        <Th hint="Adresse IP source de la requête.">IP</Th>
                                        <Th hint="Liste des champs modifiés (max 6 affichés).">Champs modifiés</Th>
                                    </Tr>
                                </THead>
                                <TBody>
                                    {rows.map((row) => {
                                        const meta = EVENT_META[row.event] ?? { tone: 'neutral' as const, label: row.event };
                                        return (
                                            <Tr key={row.id}>
                                                <Td>
                                                    <p className="font-mono text-xs text-ink-700 dark:text-ink-300">
                                                        {formatDate(row.created_at)}
                                                    </p>
                                                    <p className="text-[11px] text-ink-400 dark:text-ink-500">
                                                        {row.created_at_human}
                                                    </p>
                                                </Td>
                                                <Td>
                                                    <Badge tone={meta.tone} size="sm">
                                                        {meta.label}
                                                    </Badge>
                                                </Td>
                                                <Td>
                                                    <p className="font-medium text-ink-900 dark:text-white">{row.auditable_type}</p>
                                                    <p className="font-mono text-[11px] text-ink-500 dark:text-ink-400">
                                                        #{row.auditable_id}
                                                    </p>
                                                </Td>
                                                <Td>
                                                    <p className="text-sm text-ink-700 dark:text-ink-300">{row.user_name}</p>
                                                    {row.user_email && (
                                                        <p className="text-[11px] text-ink-500 dark:text-ink-400">{row.user_email}</p>
                                                    )}
                                                </Td>
                                                <Td className="font-mono text-[11px] text-ink-500 dark:text-ink-400">
                                                    {row.ip_address ?? '—'}
                                                </Td>
                                                <Td>
                                                    {row.changed_keys.length === 0 ? (
                                                        <span className="text-xs text-ink-400">—</span>
                                                    ) : (
                                                        <div className="flex flex-wrap gap-1">
                                                            {row.changed_keys.map((k) => (
                                                                <span
                                                                    key={k}
                                                                    className="inline-flex rounded-md bg-ink-100 px-2 py-0.5 font-mono text-[11px] text-ink-700 dark:bg-ink-700 dark:text-ink-300"
                                                                >
                                                                    {k}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    )}
                                                </Td>
                                            </Tr>
                                        );
                                    })}
                                </TBody>
                            </Table>
                        )}
                    </CardBody>
                    {pagination.last_page > 1 && (
                        <div className="px-4 pb-4">
                            <Pagination
                                currentPage={pagination.current_page}
                                lastPage={pagination.last_page}
                                total={pagination.total}
                                perPage={pagination.per_page}
                            />
                        </div>
                    )}
                </Card>
            </div>
        </DashboardLayout>
    );
}

function ListIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <line x1="8" y1="6" x2="21" y2="6" />
            <line x1="8" y1="12" x2="21" y2="12" />
            <line x1="8" y1="18" x2="21" y2="18" />
            <line x1="3" y1="6" x2="3.01" y2="6" />
            <line x1="3" y1="12" x2="3.01" y2="12" />
            <line x1="3" y1="18" x2="3.01" y2="18" />
        </svg>
    );
}
function TrendIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" strokeLinecap="round" strokeLinejoin="round" />
            <polyline points="17 6 23 6 23 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
function PulseIcon() {
    return (
        <svg className="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.75}>
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
