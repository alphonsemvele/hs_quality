import { Badge, Button, Card, EmptyState, KpiCard, PageHeader, Pagination, Sheet, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { downloadCsv } from '@/lib/csv';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface AuditRow {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: string;
    user_name: string | null;
    user_email: string | null;
    url: string | null;
    ip_address: string | null;
    changed_keys: string[];
    created_at: string | null;
    created_at_human: string | null;
}

interface Filters {
    event: string | null;
    type: string | null;
    from: string | null;
    to: string | null;
}

interface Props {
    rows: AuditRow[];
    pagination: { current_page: number; last_page: number; per_page: number; total: number };
    filters: Filters;
    eventCounts: Record<string, number>;
    auditableTypes: string[];
}

const EVENT_TONE: Record<string, 'sage' | 'brand' | 'warning' | 'danger' | 'neutral'> = {
    created: 'sage',
    updated: 'brand',
    deleted: 'danger',
    restored: 'warning',
};

export default function AuditLogIndex({
    rows = [],
    pagination = { current_page: 1, last_page: 1, per_page: 50, total: 0 },
    filters = { event: null, type: null, from: null, to: null },
    eventCounts = {},
    auditableTypes = [],
}: Partial<Props>) {
    const [selected, setSelected] = useState<AuditRow | null>(null);

    const applyFilter = (patch: Partial<Filters>) => {
        const next: Record<string, string> = {};
        const merged = { ...filters, ...patch };
        if (merged.event) next.event = merged.event;
        if (merged.type) next.type = merged.type;
        if (merged.from) next.from = merged.from;
        if (merged.to) next.to = merged.to;
        router.get('/audit-log', next, { preserveScroll: true, preserveState: true });
    };

    const clearFilters = () => {
        router.get('/audit-log', {}, { preserveScroll: true });
    };

    return (
        <DashboardLayout title="Registre d'audit" subtitle="Traçabilité RGPD Art. 30 — qui a fait quoi sur les données">
            <PageHeader
                title="Registre d'audit"
                subtitle="Journal immuable de toutes les actions sur les données — exigence RGPD & ANSSI"
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Audit log' }]}
                actions={
                    rows.length > 0 ? (
                        <Button
                            variant="secondary"
                            onClick={() =>
                                downloadCsv(
                                    `audit-log-${new Date().toISOString().slice(0, 10)}.csv`,
                                    rows,
                                    [
                                        { header: 'Horodatage', accessor: 'created_at' },
                                        { header: 'Événement', accessor: 'event' },
                                        { header: 'Ressource', accessor: 'auditable_type' },
                                        { header: 'ID ressource', accessor: 'auditable_id' },
                                        { header: 'Utilisateur', accessor: (r) => r.user_name ?? 'Système' },
                                        { header: 'Email', accessor: 'user_email' },
                                        { header: 'IP', accessor: 'ip_address' },
                                        { header: 'Champs modifiés', accessor: (r) => r.changed_keys.join(' | ') },
                                    ],
                                )
                            }
                        >
                            Exporter CSV
                        </Button>
                    ) : undefined
                }
            />

            <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                <KpiCard
                    label="Événements totaux"
                    value={pagination.total.toLocaleString('fr-FR')}
                    icon={<DatabaseIcon />}
                    tone="neutral"
                />
                <KpiCard
                    label="Créations"
                    value={eventCounts.created ?? 0}
                    icon={<PlusIcon />}
                    tone="sage"
                />
                <KpiCard
                    label="Modifications"
                    value={eventCounts.updated ?? 0}
                    icon={<EditIcon />}
                    tone="brand"
                />
                <KpiCard
                    label="Suppressions"
                    value={eventCounts.deleted ?? 0}
                    icon={<TrashIcon />}
                    tone={(eventCounts.deleted ?? 0) > 0 ? 'danger' : 'neutral'}
                />
            </div>

            <Card className="mb-5">
                <div className="flex flex-col gap-3 p-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="flex flex-wrap gap-2">
                        <FilterChip
                            label="Tous"
                            active={!filters.event}
                            onClick={() => applyFilter({ event: '' })}
                        />
                        <FilterChip label="Créés" tone="sage" active={filters.event === 'created'} onClick={() => applyFilter({ event: 'created' })} />
                        <FilterChip label="Modifiés" tone="brand" active={filters.event === 'updated'} onClick={() => applyFilter({ event: 'updated' })} />
                        <FilterChip label="Supprimés" tone="danger" active={filters.event === 'deleted'} onClick={() => applyFilter({ event: 'deleted' })} />
                        <FilterChip label="Restaurés" tone="warning" active={filters.event === 'restored'} onClick={() => applyFilter({ event: 'restored' })} />
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <select
                            value={filters.type ?? ''}
                            onChange={(e) => applyFilter({ type: e.target.value })}
                            className="h-9 rounded-md border border-ink-200 bg-white px-2.5 text-xs text-ink-700 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                        >
                            <option value="">Tous types</option>
                            {auditableTypes.map((t) => (
                                <option key={t} value={t}>
                                    {t}
                                </option>
                            ))}
                        </select>

                        <input
                            type="date"
                            value={filters.from ?? ''}
                            onChange={(e) => applyFilter({ from: e.target.value })}
                            aria-label="Du"
                            className="h-9 rounded-md border border-ink-200 bg-white px-2.5 text-xs text-ink-700 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                        />
                        <span className="text-xs text-ink-400">→</span>
                        <input
                            type="date"
                            value={filters.to ?? ''}
                            onChange={(e) => applyFilter({ to: e.target.value })}
                            aria-label="Au"
                            className="h-9 rounded-md border border-ink-200 bg-white px-2.5 text-xs text-ink-700 focus:border-brand-400 focus:outline-none dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200"
                        />

                        {(filters.event || filters.type || filters.from || filters.to) && (
                            <Button variant="ghost" size="sm" onClick={clearFilters}>
                                Effacer
                            </Button>
                        )}
                    </div>
                </div>
            </Card>

            <Card>
                {rows.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th hint="Date et heure exactes de l'action (fuseau Europe/Paris).">Quand</Th>
                                <Th hint="Type d'action effectuée : created (création), updated (modification), deleted (suppression logique), restored (restauration). Chaque écriture sensible est tracée automatiquement.">
                                    Événement
                                </Th>
                                <Th hint="Type et identifiant de l'enregistrement touché : Bénéficiaire, Intervention, Incident, Audit, PAC, Utilisateur, etc.">
                                    Ressource
                                </Th>
                                <Th hint="Utilisateur authentifié à l'origine de l'action. « Système » indique un job automatique (cron, webhook).">
                                    Utilisateur
                                </Th>
                                <Th hint="Liste des champs modifiés. Cliquer sur la ligne pour voir le diff complet avant/après — preuve exigible en évaluation HAS et en cas de contrôle CNIL.">
                                    Champs modifiés
                                </Th>
                                <Th />
                            </Tr>
                        </THead>
                        <TBody>
                            {rows.map((r) => (
                                <Tr key={r.id}>
                                    <Td>
                                        <span className="font-mono text-xs">{r.created_at_human ?? '—'}</span>
                                    </Td>
                                    <Td>
                                        <Badge tone={EVENT_TONE[r.event] ?? 'neutral'} size="sm">
                                            {r.event}
                                        </Badge>
                                    </Td>
                                    <Td>
                                        <p className="text-sm font-medium text-ink-900 dark:text-white">{r.auditable_type}</p>
                                        <p className="font-mono text-[10px] text-ink-400 dark:text-ink-500">{r.auditable_id.slice(0, 8)}…</p>
                                    </Td>
                                    <Td>
                                        {r.user_name ? (
                                            <div>
                                                <p className="text-sm text-ink-900 dark:text-white">{r.user_name}</p>
                                                <p className="font-mono text-[10px] text-ink-500 dark:text-ink-400">{r.user_email ?? r.ip_address}</p>
                                            </div>
                                        ) : (
                                            <span className="font-mono text-xs text-ink-400">système</span>
                                        )}
                                    </Td>
                                    <Td>
                                        <div className="flex flex-wrap gap-1">
                                            {r.changed_keys.length > 0 ? (
                                                r.changed_keys.map((k) => (
                                                    <code key={k} className="rounded bg-ink-100 px-1.5 py-0.5 font-mono text-[10px] text-ink-700 dark:bg-ink-700 dark:text-ink-200">
                                                        {k}
                                                    </code>
                                                ))
                                            ) : (
                                                <span className="text-xs text-ink-400">—</span>
                                            )}
                                        </div>
                                    </Td>
                                    <Td className="text-right">
                                        <button
                                            type="button"
                                            onClick={() => setSelected(r)}
                                            aria-label="Voir le diff"
                                            className="rounded-md p-1.5 text-ink-400 transition-colors hover:bg-ink-100 hover:text-brand-600 dark:text-ink-500 dark:hover:bg-ink-700 dark:hover:text-brand-400"
                                        >
                                            <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </button>
                                    </Td>
                                </Tr>
                            ))}
                        </TBody>
                    </Table>
                ) : (
                    <EmptyState
                        icon={<DatabaseIcon />}
                        title="Aucun événement"
                        description="Aucune action enregistrée pour cette période. Modifiez les filtres ou attendez les prochains événements."
                        action={
                            (filters.event || filters.type || filters.from || filters.to) && (
                                <Button variant="secondary" onClick={clearFilters}>
                                    Effacer les filtres
                                </Button>
                            )
                        }
                    />
                )}

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

            <Sheet
                open={selected !== null}
                onClose={() => setSelected(null)}
                size="lg"
                title="Détail de l'événement"
                description={selected ? `${selected.auditable_type} · ${selected.event}` : undefined}
                iconTone="brand"
                icon={<DatabaseIcon />}
            >
                {selected && <AuditDetail row={selected} />}
            </Sheet>
        </DashboardLayout>
    );
}

function AuditDetail({ row }: { row: AuditRow }) {
    return (
        <div className="space-y-5">
            <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <Field label="Événement" value={<Badge tone={EVENT_TONE[row.event] ?? 'neutral'} size="sm">{row.event}</Badge>} />
                <Field label="Ressource" value={`${row.auditable_type} · ${row.auditable_id}`} mono />
                <Field label="Utilisateur" value={row.user_name ?? 'système'} />
                <Field label="Email" value={row.user_email ?? '—'} mono />
                <Field label="IP" value={row.ip_address ?? '—'} mono />
                <Field label="Date" value={row.created_at ? new Date(row.created_at).toLocaleString('fr-FR') : '—'} />
                {row.url && <Field label="URL" value={row.url} mono fullWidth />}
            </dl>

            {row.changed_keys.length > 0 && (
                <div>
                    <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                        Champs modifiés
                    </p>
                    <div className="flex flex-wrap gap-1.5 rounded-xl border border-ink-100 bg-ink-50/40 p-3 dark:border-ink-700/60 dark:bg-ink-900/30">
                        {row.changed_keys.map((key) => (
                            <code key={key} className="rounded-md bg-white px-2 py-1 font-mono text-xs text-ink-800 shadow-sm dark:bg-ink-800 dark:text-ink-100">
                                {key}
                            </code>
                        ))}
                    </div>
                    <p className="mt-2 text-[11px] text-ink-500 dark:text-ink-400">
                        Le diff complet avant/après est disponible via l'API <code className="font-mono">/api/audit-log/{row.id}</code>{' '}
                        (à venir — exigerait un endpoint dédié et un contrôle de visibilité strict des données sensibles).
                    </p>
                </div>
            )}

            <p className="rounded-lg border border-brand-200 bg-brand-50/40 px-3 py-2 text-[11px] text-brand-900 dark:border-brand-700/40 dark:bg-brand-900/20 dark:text-brand-200">
                Cet événement est immuable et conservé conformément à la politique RGPD de votre structure.
            </p>
        </div>
    );
}

function Field({ label, value, mono, fullWidth }: { label: string; value: React.ReactNode; mono?: boolean; fullWidth?: boolean }) {
    return (
        <div className={cn('rounded-lg border border-ink-100 bg-ink-50/40 px-3 py-2 dark:border-ink-700/60 dark:bg-ink-900/30', fullWidth && 'sm:col-span-2')}>
            <dt className="text-[10px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</dt>
            <dd className={cn('mt-0.5 text-sm', mono ? 'font-mono text-xs' : '', 'text-ink-900 dark:text-white')}>
                {value}
            </dd>
        </div>
    );
}

function FilterChip({
    label,
    active,
    tone,
    onClick,
}: {
    label: string;
    active?: boolean;
    tone?: 'sage' | 'brand' | 'warning' | 'danger';
    onClick: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn(
                'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors',
                active
                    ? cn(
                          tone === 'sage' && 'border-sage-500 bg-sage-50 text-sage-700 dark:border-sage-400 dark:bg-sage-900/30 dark:text-sage-200',
                          tone === 'brand' && 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-400 dark:bg-brand-900/30 dark:text-brand-200',
                          tone === 'warning' && 'border-warning-500 bg-warning-50 text-warning-700 dark:border-warning-400 dark:bg-warning-900/30 dark:text-warning-200',
                          tone === 'danger' && 'border-danger-500 bg-danger-50 text-danger-700 dark:border-danger-400 dark:bg-danger-900/30 dark:text-danger-200',
                          !tone && 'border-brand-500 bg-brand-50 text-brand-700 dark:border-brand-400 dark:bg-brand-900/30 dark:text-brand-200',
                      )
                    : 'border-ink-200 bg-white text-ink-600 hover:border-ink-300 hover:bg-ink-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-300 dark:hover:bg-ink-700/40',
            )}
        >
            {label}
        </button>
    );
}

function DatabaseIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <ellipse cx="12" cy="5" rx="9" ry="3" />
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
        </svg>
    );
}
function PlusIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M12 5v14M5 12h14" strokeLinecap="round" />
        </svg>
    );
}
function EditIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
        </svg>
    );
}
function TrashIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6" />
            <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2" />
        </svg>
    );
}
