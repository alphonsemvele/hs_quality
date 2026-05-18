import { BeneficiaryHoverCard, UserHoverCard } from '@/components/hover-cards';
import { InterventionCalendar } from '@/components/InterventionCalendar';
import { InterventionPreviewSheet, type InterventionPreview } from '@/components/preview-sheets';
import { QuickAddInterventionModal } from '@/components/quick-add';
import {
    Badge,
    BulkActionsToolbar,
    BulkSelectCheckbox,
    Button,
    Card,
    ConfirmDialog,
    EmptyStateRich,
    FilterChipsBar,
    FilterDrawer,
    FormField,
    Input,
    InterventionStatusBadge,
    PageHeader,
    Pagination,
    Select,
    TBody,
    THead,
    Table,
    Td,
    Th,
    Tr,
} from '@/components/ui';
import { useCan } from '@/lib/can';
import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface QuickOption {
    id: number | string;
    name: string;
}

type Statut = 'planifiee' | 'en_cours' | 'realisee' | 'annulee' | 'non_realisee';

/**
 * Subtle left-edge marker so the row's statut is readable at a glance,
 * even before the badge column comes into view. Matches the tone used
 * by InterventionStatusBadge.
 */
const STATUT_BORDER: Record<Statut, string> = {
    planifiee: 'border-l-4 border-l-brand-500/70',
    en_cours: 'border-l-4 border-l-warning-500/80',
    realisee: 'border-l-4 border-l-sage-500/70',
    annulee: 'border-l-4 border-l-danger-500/70',
    non_realisee: 'border-l-4 border-l-ink-400/60',
};

function statutBorderClass(statut: Statut): string {
    return STATUT_BORDER[statut] ?? '';
}

interface Intervention {
    id: number | string;
    initials: string;
    intervenant: string;
    beneficiaire: string;
    structure: string;
    date_heure_debut: string;
    date_heure_fin: string | null;
    duree_minutes: number | null;
    statut: Statut;
    compte_rendu: string | null;
    sync_offline: boolean;
}

interface Props {
    interventions: Intervention[];
    total: number;
    pagination: { current_page: number; last_page: number; per_page: number };
    stats: { planifiees: number; en_cours: number; realisees: number; annulees: number };
    filters: { status: string | null; from: string | null; to: string | null; intervenant_id: string | null };
    quickAddOptions: { intervenants: QuickOption[]; beneficiaries: QuickOption[] } | null;
}

export default function Interventions({
    interventions = [],
    total = 0,
    pagination = { current_page: 1, last_page: 1, per_page: 20 },
    stats = { planifiees: 0, en_cours: 0, realisees: 0, annulees: 0 },
    filters = { status: null, from: null, to: null, intervenant_id: null },
    quickAddOptions = null,
}: Partial<Props>) {
    const activeFilter = filters.status;
    const canCreate = useCan('interventions.create');
    const [showQuickAdd, setShowQuickAdd] = useState(false);
    const [preview, setPreview] = useState<InterventionPreview | null>(null);
    const [showFilters, setShowFilters] = useState(false);
    const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
    const [bulkAction, setBulkAction] = useState<'cancel' | null>(null);
    const [view, setView] = useState<'list' | 'calendar'>('list');

    const toggleSelect = (id: string) => {
        setSelectedIds((prev) => {
            const next = new Set(prev);
            if (next.has(id)) next.delete(id);
            else next.add(id);
            return next;
        });
    };
    const toggleSelectAll = () => {
        if (selectedIds.size === interventions.length) {
            setSelectedIds(new Set());
        } else {
            setSelectedIds(new Set(interventions.map((i) => String(i.id))));
        }
    };
    const clearSelection = () => setSelectedIds(new Set());

    const bulkCancellable = interventions
        .filter((i) => selectedIds.has(String(i.id)))
        .filter((i) => i.statut === 'planifiee' || i.statut === 'en_cours');

    const performBulkCancel = () => {
        const ids = bulkCancellable.map((i) => String(i.id));
        if (ids.length === 0) {
            setBulkAction(null);
            return;
        }
        router.post(
            '/interventions/bulk/cancel',
            { ids, cancellation_reason: 'Annulation en masse depuis la liste' },
            {
                preserveScroll: true,
                onFinish: () => {
                    clearSelection();
                    setBulkAction(null);
                },
            },
        );
    };

    const applyFilters = (next: Record<string, string>) => {
        router.get('/interventions', next, { preserveScroll: true, preserveState: true });
    };
    const removeFilter = (key: string) => {
        const next: Record<string, string> = {};
        Object.entries(filters).forEach(([k, v]) => {
            if (v && k !== key) next[k] = v;
        });
        applyFilters(next);
    };
    const resetFilters = () => router.get('/interventions');

    const intervenantsById = new Map((quickAddOptions?.intervenants ?? []).map((u) => [String(u.id), u.name]));
    const chips: Array<{ key: string; label: string; value: string }> = [];
    if (filters.status) chips.push({ key: 'status', label: 'Statut', value: filters.status });
    if (filters.from) chips.push({ key: 'from', label: 'Du', value: filters.from });
    if (filters.to) chips.push({ key: 'to', label: 'Au', value: filters.to });
    if (filters.intervenant_id) chips.push({ key: 'intervenant_id', label: 'Intervenant', value: intervenantsById.get(filters.intervenant_id) ?? filters.intervenant_id });

    return (
        <DashboardLayout title="Interventions" subtitle="Suivi des interventions à domicile">
            <PageHeader
                title="Interventions"
                subtitle={`${total} intervention(s) au total`}
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Interventions' }]}
                actions={
                    <div className="flex items-center gap-2">
                        <div className="inline-flex rounded-full border border-ink-200 bg-white p-0.5 text-xs font-medium dark:border-ink-700 dark:bg-ink-800">
                            <button
                                type="button"
                                onClick={() => setView('list')}
                                aria-pressed={view === 'list'}
                                className={
                                    view === 'list'
                                        ? 'rounded-full bg-ink-900 px-3 py-1 text-white dark:bg-white dark:text-ink-900'
                                        : 'rounded-full px-3 py-1 text-ink-600 transition-colors hover:text-ink-900 dark:text-ink-300 dark:hover:text-white'
                                }
                            >
                                Liste
                            </button>
                            <button
                                type="button"
                                onClick={() => setView('calendar')}
                                aria-pressed={view === 'calendar'}
                                className={
                                    view === 'calendar'
                                        ? 'rounded-full bg-ink-900 px-3 py-1 text-white dark:bg-white dark:text-ink-900'
                                        : 'rounded-full px-3 py-1 text-ink-600 transition-colors hover:text-ink-900 dark:text-ink-300 dark:hover:text-white'
                                }
                            >
                                Calendrier
                            </button>
                        </div>
                        {canCreate && (
                            <Button leadingIcon={<PlusIcon />} onClick={() => setShowQuickAdd(true)}>
                                Nouvelle intervention
                            </Button>
                        )}
                    </div>
                }
            />

            {/* Filtres rapides + filtres avancés */}
            <div className="mb-5 space-y-3">
                <div className="flex flex-wrap items-center gap-2">
                    <FilterChip label="Toutes" count={total} active={!activeFilter} href="/interventions" />
                    <FilterChip label="En cours" count={stats.en_cours} active={activeFilter === 'en_cours'} href="/interventions?status=en_cours" />
                    <FilterChip label="Planifiées" count={stats.planifiees} active={activeFilter === 'planifiee'} href="/interventions?status=planifiee" />
                    <FilterChip label="Réalisées" count={stats.realisees} active={activeFilter === 'realisee'} href="/interventions?status=realisee" />
                    <FilterChip label="Annulées" count={stats.annulees} active={activeFilter === 'annulee'} href="/interventions?status=annulee" />
                </div>
                <FilterChipsBar
                    chips={chips}
                    onRemove={removeFilter}
                    onOpenDrawer={() => setShowFilters(true)}
                    onResetAll={resetFilters}
                />
            </div>

            <BulkActionsToolbar
                selectedCount={selectedIds.size}
                totalCount={interventions.length}
                onSelectAll={toggleSelectAll}
                onClear={clearSelection}
                actions={
                    <>
                        <Button
                            variant="secondary"
                            size="sm"
                            disabled={bulkCancellable.length === 0}
                            onClick={() => setBulkAction('cancel')}
                        >
                            Annuler {bulkCancellable.length > 0 && `(${bulkCancellable.length})`}
                        </Button>
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => {
                                const ids = Array.from(selectedIds).join(',');
                                window.open(`/interventions/export.csv?ids=${ids}`, '_blank');
                            }}
                        >
                            Exporter CSV
                        </Button>
                    </>
                }
                hint={bulkCancellable.length < selectedIds.size ? `${selectedIds.size - bulkCancellable.length} intervention(s) déjà clôturée(s) ne peuvent pas être annulée(s).` : undefined}
            />

            <Card>
                {view === 'calendar' ? (
                    <div className="p-4">
                        <InterventionCalendar
                            interventions={interventions}
                            onSelect={(i) => setPreview(i as InterventionPreview)}
                        />
                    </div>
                ) : interventions.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th>
                                    <BulkSelectCheckbox
                                        checked={selectedIds.size === interventions.length}
                                        indeterminate={selectedIds.size > 0 && selectedIds.size < interventions.length}
                                        onChange={toggleSelectAll}
                                        ariaLabel="Tout sélectionner"
                                    />
                                </Th>
                                <Th hint="Personnel terrain (aide à domicile, AVS, AES) qui réalise la visite. Affecté via l'écran Bénéficiaires → Affectations.">
                                    Intervenant
                                </Th>
                                <Th hint="Personne accompagnée à son domicile lors de cette intervention.">Bénéficiaire</Th>
                                <Th hint="Horaires planifiés de l'intervention. Le check-in réel (mobile) peut différer ; tout écart est tracé.">
                                    Date & heure
                                </Th>
                                <Th hint="Durée prévue de l'intervention en minutes. Comparée à la durée effective (check-out − check-in).">Durée</Th>
                                <Th hint="Cycle de vie : Planifié → En cours (check-in) → Terminé (check-out) ou Manqué / Annulé. Mis à jour en temps réel via WebSocket Reverb.">
                                    Statut
                                </Th>
                                <Th hint="Compte-Rendu d'intervention : indique si l'intervenant a complété son rapport (tâches cochées, observations, photos, signature) en sortie de visite.">
                                    CR
                                </Th>
                                <Th></Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {interventions.map((i) => (
                                <Tr key={i.id} className={statutBorderClass(i.statut)}>
                                    <Td>
                                        <BulkSelectCheckbox
                                            checked={selectedIds.has(String(i.id))}
                                            onChange={() => toggleSelect(String(i.id))}
                                            ariaLabel={`Sélectionner intervention ${i.beneficiaire}`}
                                        />
                                    </Td>
                                    <Td>
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                                {i.initials}
                                            </div>
                                            <UserHoverCard
                                                name={i.intervenant}
                                                initials={i.initials}
                                                role="Intervenant·e à domicile"
                                            />
                                        </div>
                                    </Td>
                                    <Td>
                                        <BeneficiaryHoverCard name={i.beneficiaire} />
                                    </Td>
                                    <Td className="font-mono text-xs text-ink-600 dark:text-ink-400">{i.date_heure_debut}</Td>
                                    <Td className="font-mono text-xs">
                                        {i.duree_minutes ? `${i.duree_minutes} min` : '—'}
                                    </Td>
                                    <Td>
                                        <InterventionStatusBadge statut={i.statut} />
                                    </Td>
                                    <Td>
                                        <div className="flex items-center gap-1.5">
                                            {i.compte_rendu ? (
                                                <span className="text-sage-600 dark:text-sage-400">✓</span>
                                            ) : (
                                                <span className="text-ink-300 dark:text-ink-600">—</span>
                                            )}
                                            {i.sync_offline && (
                                                <Badge tone="warning" size="xs">
                                                    offline
                                                </Badge>
                                            )}
                                        </div>
                                    </Td>
                                    <Td className="text-right">
                                        <div className="flex items-center justify-end gap-1">
                                            <button
                                                type="button"
                                                onClick={() => setPreview(i)}
                                                aria-label="Aperçu rapide"
                                                className="rounded-md p-1.5 text-ink-400 transition-colors hover:bg-ink-100 hover:text-brand-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40 dark:text-ink-500 dark:hover:bg-ink-700 dark:hover:text-brand-400"
                                            >
                                                <EyeIcon />
                                            </button>
                                            <Link
                                                href={`/interventions/${i.id}`}
                                                className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                            >
                                                Voir →
                                            </Link>
                                        </div>
                                    </Td>
                                </Tr>
                            ))}
                        </TBody>
                    </Table>
                ) : (
                    <EmptyStateRich
                        icon={
                            <svg className="size-6" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        }
                        title="Aucune intervention planifiée"
                        description="Planifiez vos premières visites à domicile pour démarrer le suivi terrain. Les intervenants saisiront leurs comptes-rendus depuis le mobile."
                        primaryAction={
                            canCreate ? (
                                <Button size="lg" onClick={() => setShowQuickAdd(true)}>
                                    Planifier la première intervention →
                                </Button>
                            ) : undefined
                        }
                        suggestions={[
                            {
                                icon: '👥',
                                title: "Affecter les intervenants",
                                description: 'Avant de planifier des visites, associez vos intervenants aux bénéficiaires.',
                                tone: 'sage',
                                cta: { label: 'Voir les bénéficiaires', href: '/beneficiaries' },
                            },
                            {
                                icon: '📋',
                                title: 'Créer un plan de soins',
                                description: 'Définissez les tâches récurrentes que les intervenants devront cocher à chaque visite.',
                                tone: 'brand',
                                cta: { label: 'Aller aux bénéficiaires', href: '/beneficiaries' },
                            },
                            {
                                icon: '📱',
                                title: 'Activer le mobile',
                                description: 'Les intervenants saisissent les visites depuis leur smartphone, même en zone blanche.',
                                tone: 'neutral',
                                cta: { label: "Voir l'aide mobile", href: '/dashboard/profile' },
                            },
                        ]}
                    />
                )}
                <div className="px-4 pb-4">
                    <Pagination
                        currentPage={pagination.current_page}
                        lastPage={pagination.last_page}
                        total={total}
                        perPage={pagination.per_page}
                    />
                </div>
            </Card>

            {canCreate && quickAddOptions && (
                <QuickAddInterventionModal
                    open={showQuickAdd}
                    onClose={() => setShowQuickAdd(false)}
                    intervenants={quickAddOptions.intervenants}
                    beneficiaries={quickAddOptions.beneficiaries}
                />
            )}

            <InterventionPreviewSheet intervention={preview} onClose={() => setPreview(null)} />

            <FilterDrawer
                open={showFilters}
                onClose={() => setShowFilters(false)}
                pageKey="interventions"
                basePath="/interventions"
                currentQuery={{
                    status: filters.status,
                    from: filters.from,
                    to: filters.to,
                    intervenant_id: filters.intervenant_id,
                }}
                appliedCount={chips.length}
                onApply={applyFilters}
                onReset={resetFilters}
            >
                <InterventionFilterFields
                    filters={filters}
                    intervenants={quickAddOptions?.intervenants ?? []}
                    onChange={applyFilters}
                />
            </FilterDrawer>

            <ConfirmDialog
                open={bulkAction === 'cancel'}
                onClose={() => setBulkAction(null)}
                onConfirm={performBulkCancel}
                title={`Annuler ${bulkCancellable.length} intervention${bulkCancellable.length > 1 ? 's' : ''} ?`}
                description={
                    <span>
                        Les interventions sélectionnées seront marquées comme annulées et apparaîtront dans l'historique
                        des bénéficiaires. Cette opération est tracée dans le registre d'audit.
                    </span>
                }
                confirmLabel="Confirmer l'annulation"
                tone="danger"
            />
        </DashboardLayout>
    );
}

function InterventionFilterFields({
    filters,
    intervenants,
    onChange,
}: {
    filters: { status: string | null; from: string | null; to: string | null; intervenant_id: string | null };
    intervenants: QuickOption[];
    onChange: (next: Record<string, string>) => void;
}) {
    const update = (key: string, value: string) => {
        const next: Record<string, string> = {};
        Object.entries(filters).forEach(([k, v]) => {
            if (v && k !== key) next[k] = v;
        });
        if (value) next[key] = value;
        onChange(next);
    };
    return (
        <>
            <FormField label="Statut" htmlFor="flt-status">
                <Select
                    id="flt-status"
                    value={filters.status ?? ''}
                    onChange={(e) => update('status', e.target.value)}
                >
                    <option value="">Tous</option>
                    <option value="planifiee">Planifiées</option>
                    <option value="en_cours">En cours</option>
                    <option value="realisee">Réalisées</option>
                    <option value="annulee">Annulées</option>
                </Select>
            </FormField>

            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <FormField label="Du" htmlFor="flt-from">
                    <Input
                        id="flt-from"
                        type="date"
                        value={filters.from ?? ''}
                        onChange={(e) => update('from', e.target.value)}
                    />
                </FormField>
                <FormField label="Au" htmlFor="flt-to">
                    <Input
                        id="flt-to"
                        type="date"
                        value={filters.to ?? ''}
                        min={filters.from ?? undefined}
                        onChange={(e) => update('to', e.target.value)}
                    />
                </FormField>
            </div>

            {intervenants.length > 0 && (
                <FormField label="Intervenant" htmlFor="flt-intervenant">
                    <Select
                        id="flt-intervenant"
                        value={filters.intervenant_id ?? ''}
                        onChange={(e) => update('intervenant_id', e.target.value)}
                    >
                        <option value="">Tous</option>
                        {intervenants.map((u) => (
                            <option key={u.id} value={u.id}>
                                {u.name}
                            </option>
                        ))}
                    </Select>
                </FormField>
            )}

            <p className="rounded-lg border border-brand-200 bg-brand-50/40 px-3 py-2 text-[11px] text-brand-900 dark:border-brand-700/40 dark:bg-brand-900/20 dark:text-brand-200">
                💡 Sauvegardez la combinaison de filtres ci-dessus pour un accès rapide depuis « Vues sauvegardées ».
            </p>
        </>
    );
}

function FilterChip({ label, count, active, href }: { label: string; count: number; active?: boolean; href: string }) {
    return (
        <Link
            href={href}
            preserveState
            className={
                active
                    ? 'inline-flex items-center gap-1.5 rounded-lg bg-ink-900 px-3.5 py-1.5 text-xs font-semibold text-white dark:bg-white dark:text-ink-900'
                    : 'inline-flex items-center gap-1.5 rounded-lg border border-ink-200 bg-white px-3.5 py-1.5 text-xs font-semibold text-ink-600 hover:border-ink-300 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-300 dark:hover:border-ink-500'
            }
        >
            {label}
            <span className={active ? 'text-white/70 dark:text-ink-900/60' : 'text-ink-400 dark:text-ink-500'}>({count})</span>
        </Link>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}

function EyeIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
            <circle cx="12" cy="12" r="3" />
        </svg>
    );
}
