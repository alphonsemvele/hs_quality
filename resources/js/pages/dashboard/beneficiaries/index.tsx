import { BeneficiaryPreviewSheet, type BeneficiaryPreview } from '@/components/preview-sheets';
import { QuickAddBeneficiaryModal } from '@/components/quick-add';
import { Badge, Button, Card, EmptyStateRich, GirBadge, PageHeader, Pagination, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { useCan } from '@/lib/can';
import { downloadCsv } from '@/lib/csv';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
    initials: string;
    age: number | null;
    gir: number | null;
    city: string | null;
    status: string | null;
    status_label: string | null;
    is_erased: boolean;
}

interface Props {
    beneficiaries: { data: Beneficiary[] };
    meta: { total: number; current_page: number; last_page: number };
}

export default function BeneficiariesIndex({ beneficiaries, meta }: Props) {
    const list = beneficiaries?.data ?? [];
    const canCreate = useCan('beneficiaries.create');
    const [showQuickAdd, setShowQuickAdd] = useState(false);
    const [preview, setPreview] = useState<BeneficiaryPreview | null>(null);
    const [view, setView] = useState<'list' | 'cards'>('list');

    return (
        <DashboardLayout title="Bénéficiaires" subtitle="Personnes accompagnées par votre structure">
            <PageHeader
                title="Bénéficiaires"
                subtitle={`${meta?.total ?? list.length} bénéficiaire(s) — page ${meta?.current_page ?? 1} sur ${meta?.last_page ?? 1}`}
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Bénéficiaires' }]}
                actions={
                    <div className="flex items-center gap-2">
                        {list.length > 0 && (
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
                                    onClick={() => setView('cards')}
                                    aria-pressed={view === 'cards'}
                                    className={
                                        view === 'cards'
                                            ? 'rounded-full bg-ink-900 px-3 py-1 text-white dark:bg-white dark:text-ink-900'
                                            : 'rounded-full px-3 py-1 text-ink-600 transition-colors hover:text-ink-900 dark:text-ink-300 dark:hover:text-white'
                                    }
                                >
                                    Cartes
                                </button>
                            </div>
                        )}
                        {list.length > 0 && (
                            <Button
                                variant="secondary"
                                onClick={() =>
                                    downloadCsv(
                                        `beneficiaires-${new Date().toISOString().slice(0, 10)}.csv`,
                                        list,
                                        [
                                            { header: 'Nom complet', accessor: 'full_name' },
                                            { header: 'Âge', accessor: (b) => b.age ?? '' },
                                            { header: 'GIR', accessor: (b) => b.gir ?? '' },
                                            { header: 'Ville', accessor: (b) => b.city ?? '' },
                                            { header: 'Statut', accessor: (b) => b.status_label ?? b.status ?? '' },
                                        ],
                                    )
                                }
                            >
                                Exporter CSV
                            </Button>
                        )}
                        {canCreate && (
                            <Button leadingIcon={<PlusIcon />} onClick={() => setShowQuickAdd(true)}>
                                Nouveau bénéficiaire
                            </Button>
                        )}
                    </div>
                }
            />

            <Card>
                {list.length > 0 && view === 'cards' ? (
                    <div className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        {list.map((b) => (
                            <button
                                type="button"
                                key={b.id}
                                onClick={() => setPreview(b)}
                                className="group flex items-start gap-3 rounded-xl border border-ink-100 bg-white p-4 text-left transition-shadow hover:border-sage-200 hover:shadow-md dark:border-ink-700/60 dark:bg-ink-800 dark:hover:border-sage-700/60"
                            >
                                <div className="flex size-11 shrink-0 items-center justify-center rounded-lg bg-sage-50 text-sm font-semibold text-sage-700 dark:bg-sage-900/30 dark:text-sage-300">
                                    {b.initials || '?'}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <div className="flex flex-wrap items-center gap-1.5">
                                        <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">
                                            {b.full_name}
                                        </p>
                                        {b.is_erased && (
                                            <Badge tone="warning" size="xs">
                                                Anonymisé
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">
                                        {b.age !== null ? `${b.age} ans` : 'Âge ?'} · {b.city ?? '—'}
                                    </p>
                                    <div className="mt-2 flex flex-wrap items-center gap-1.5">
                                        {b.gir && <GirBadge gir={b.gir} />}
                                        <Badge tone={b.status === 'active' ? 'sage' : 'neutral'} size="xs" dot={b.status === 'active'}>
                                            {b.status_label ?? b.status ?? '—'}
                                        </Badge>
                                    </div>
                                </div>
                                <Link
                                    href={`/beneficiaries/${b.id}`}
                                    onClick={(e) => e.stopPropagation()}
                                    className="shrink-0 text-[11px] font-medium text-brand-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-brand-400"
                                >
                                    Détail →
                                </Link>
                            </button>
                        ))}
                    </div>
                ) : list.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th hint="Personne accompagnée par votre structure à son domicile. Le nom complet et les initiales sont affichés ; le badge « Anonymisé » apparaît si une demande d'effacement RGPD a été traitée.">
                                    Bénéficiaire
                                </Th>
                                <Th hint="Âge calculé à partir de la date de naissance enregistrée dans la fiche bénéficiaire.">Âge</Th>
                                <Th hint="Groupe Iso-Ressources : classification de 1 à 6 du niveau de dépendance. GIR 1 = très dépendant (assistance permanente), GIR 6 = autonome. Détermine l'éligibilité à l'APA et oriente le plan d'accompagnement.">
                                    GIR
                                </Th>
                                <Th hint="Ville de résidence du bénéficiaire — utile pour optimiser les tournées par secteur géographique.">Ville</Th>
                                <Th hint="Statut administratif du dossier : Actif (suivi en cours), Suspendu (pause temporaire), Archivé (sortie de la structure). Seuls les dossiers actifs apparaissent dans le planning.">
                                    Statut
                                </Th>
                                <Th></Th>
                            </Tr>
                        </THead>
                        <TBody>
                            {list.map((b) => (
                                <Tr key={b.id}>
                                    <Td>
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sage-50 text-xs font-semibold text-sage-700 dark:bg-sage-900/30 dark:text-sage-300">
                                                {b.initials || '?'}
                                            </div>
                                            <span className="font-medium text-ink-900 dark:text-white">{b.full_name}</span>
                                            {b.is_erased && (
                                                <Badge tone="warning" size="xs">
                                                    Anonymisé
                                                </Badge>
                                            )}
                                        </div>
                                    </Td>
                                    <Td className="font-mono">{b.age ?? '—'}</Td>
                                    <Td>{b.gir ? <GirBadge gir={b.gir} /> : <span className="text-ink-400 dark:text-ink-500">—</span>}</Td>
                                    <Td>{b.city ?? '—'}</Td>
                                    <Td>
                                        <Badge tone={b.status === 'active' ? 'sage' : 'neutral'} size="sm" dot={b.status === 'active'}>
                                            {b.status_label ?? b.status ?? '—'}
                                        </Badge>
                                    </Td>
                                    <Td className="text-right">
                                        <div className="flex items-center justify-end gap-1">
                                            <button
                                                type="button"
                                                onClick={() => setPreview(b)}
                                                aria-label="Aperçu rapide"
                                                className="rounded-md p-1.5 text-ink-400 transition-colors hover:bg-ink-100 hover:text-sage-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sage-500/40 dark:text-ink-500 dark:hover:bg-ink-700 dark:hover:text-sage-400"
                                            >
                                                <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </button>
                                            <Link
                                                href={`/beneficiaries/${b.id}`}
                                                className="text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                                            >
                                                Détail →
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
                                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        }
                        title="Démarrez votre suivi"
                        description="Ajoutez votre premier bénéficiaire pour structurer le suivi qualité. L'ajout prend 30 secondes — vous compléterez le dossier ensuite."
                        primaryAction={
                            canCreate ? (
                                <Button size="lg" onClick={() => setShowQuickAdd(true)}>
                                    Ajouter mon premier bénéficiaire →
                                </Button>
                            ) : undefined
                        }
                        suggestions={[
                            {
                                icon: '🔒',
                                title: 'Conformité RGPD',
                                description: 'Les données médicales sont chiffrées au repos. Tout accès est tracé dans le registre d\'audit.',
                                tone: 'brand',
                            },
                            {
                                icon: '👨‍⚕️',
                                title: 'Plan de soins individualisé',
                                description: 'Définissez des tâches récurrentes à cocher à chaque visite. Modifiable à tout moment.',
                                tone: 'sage',
                            },
                            {
                                icon: '📊',
                                title: 'Suivi automatique',
                                description: 'Les indicateurs se calculent automatiquement à partir des visites réalisées.',
                                tone: 'neutral',
                            },
                        ]}
                    />
                )}
                <div className="px-4 pb-4">
                    <Pagination
                        currentPage={meta?.current_page ?? 1}
                        lastPage={meta?.last_page ?? 1}
                        total={meta?.total ?? list.length}
                        perPage={20}
                    />
                </div>
            </Card>

            <QuickAddBeneficiaryModal open={showQuickAdd} onClose={() => setShowQuickAdd(false)} />
            <BeneficiaryPreviewSheet beneficiary={preview} onClose={() => setPreview(null)} />
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
