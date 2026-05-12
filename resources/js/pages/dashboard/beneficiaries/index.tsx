import { BeneficiaryPreviewSheet, type BeneficiaryPreview } from '@/components/preview-sheets';
import { QuickAddBeneficiaryModal } from '@/components/quick-add';
import { Badge, Button, Card, EmptyState, PageHeader, Pagination, TBody, THead, Table, Td, Th, Tr } from '@/components/ui';
import { useCan } from '@/lib/can';
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

    return (
        <DashboardLayout title="Bénéficiaires" subtitle="Personnes accompagnées par votre structure">
            <PageHeader
                title="Bénéficiaires"
                subtitle={`${meta?.total ?? list.length} bénéficiaire(s) — page ${meta?.current_page ?? 1} sur ${meta?.last_page ?? 1}`}
                breadcrumb={[{ label: 'Tableau de bord', href: '/dashboard' }, { label: 'Bénéficiaires' }]}
                actions={
                    canCreate ? (
                        <Button leadingIcon={<PlusIcon />} onClick={() => setShowQuickAdd(true)}>
                            Nouveau bénéficiaire
                        </Button>
                    ) : null
                }
            />

            <Card>
                {list.length > 0 ? (
                    <Table>
                        <THead>
                            <Tr>
                                <Th>Bénéficiaire</Th>
                                <Th>Âge</Th>
                                <Th>GIR</Th>
                                <Th>Ville</Th>
                                <Th>Statut</Th>
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
                    <EmptyState
                        title="Aucun bénéficiaire"
                        description="Créez votre premier bénéficiaire pour démarrer le suivi."
                        action={
                            canCreate ? (
                                <Button onClick={() => setShowQuickAdd(true)}>Nouveau bénéficiaire</Button>
                            ) : undefined
                        }
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

function GirBadge({ gir }: { gir: number }) {
    const tone = gir <= 2 ? 'danger' : gir <= 4 ? 'warning' : 'sage';
    return (
        <Badge tone={tone} size="sm">
            GIR {gir}
        </Badge>
    );
}

function PlusIcon() {
    return (
        <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14" />
        </svg>
    );
}
