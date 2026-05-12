import { Badge, Button, Sheet } from '@/components/ui';
import { Link } from '@inertiajs/react';

export interface BeneficiaryPreview {
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
    beneficiary: BeneficiaryPreview | null;
    onClose: () => void;
}

export function BeneficiaryPreviewSheet({ beneficiary, onClose }: Props) {
    return (
        <Sheet
            open={beneficiary !== null}
            onClose={onClose}
            size="md"
            title="Aperçu bénéficiaire"
            description={beneficiary?.full_name}
            iconTone="sage"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            }
            footer={
                beneficiary && !beneficiary.is_erased ? (
                    <>
                        <Button variant="ghost" onClick={onClose}>
                            Fermer
                        </Button>
                        <Link href={`/beneficiaries/${beneficiary.id}`}>
                            <Button>Voir le détail complet →</Button>
                        </Link>
                    </>
                ) : null
            }
        >
            {beneficiary && (
                <div className="space-y-5">
                    {beneficiary.is_erased ? (
                        <div className="rounded-xl border border-warning-200 bg-warning-50/40 p-4 text-center dark:border-warning-700/40 dark:bg-warning-900/15">
                            <p className="text-sm font-semibold text-warning-900 dark:text-warning-200">
                                Bénéficiaire anonymisé·e (RGPD)
                            </p>
                            <p className="mt-1 text-xs text-warning-800/80 dark:text-warning-200/80">
                                Les données personnelles ont été effacées conformément à la politique de conservation.
                                Seules les métadonnées d'audit sont conservées.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="flex items-center gap-3">
                                <span className="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-sage-500 to-sage-700 text-sm font-bold text-white">
                                    {beneficiary.initials}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">
                                        {beneficiary.full_name}
                                    </p>
                                    <p className="text-xs text-ink-500 dark:text-ink-400">
                                        {beneficiary.age !== null ? `${beneficiary.age} ans` : 'Âge non renseigné'}
                                        {beneficiary.city && ` · ${beneficiary.city}`}
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                {beneficiary.gir !== null && (
                                    <Badge tone="brand" size="sm">
                                        GIR {beneficiary.gir}
                                    </Badge>
                                )}
                                {beneficiary.status_label && (
                                    <Badge tone="neutral" size="sm" dot>
                                        {beneficiary.status_label}
                                    </Badge>
                                )}
                            </div>

                            <div className="rounded-xl border border-brand-200 bg-brand-50/40 px-3 py-3 text-xs dark:border-brand-700/40 dark:bg-brand-900/20">
                                <p className="font-semibold text-brand-900 dark:text-brand-200">
                                    🔒 Données sensibles
                                </p>
                                <p className="mt-1 text-brand-900/80 dark:text-brand-200/80">
                                    Le dossier médical (allergies, traitements, antécédents) reste accessible avec
                                    traçabilité depuis la fiche complète.
                                </p>
                            </div>

                            <div className="grid grid-cols-2 gap-2">
                                <Link
                                    href={`/beneficiaries/${beneficiary.id}/care-plans`}
                                    className="rounded-lg border border-ink-200 bg-white px-3 py-2 text-center text-xs font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200 dark:hover:bg-ink-700/40"
                                >
                                    Plans de soins →
                                </Link>
                                <Link
                                    href={`/beneficiaries/${beneficiary.id}/dossier`}
                                    className="rounded-lg border border-ink-200 bg-white px-3 py-2 text-center text-xs font-medium text-ink-700 transition-colors hover:border-brand-300 hover:bg-brand-50 dark:border-ink-700 dark:bg-ink-800 dark:text-ink-200 dark:hover:bg-ink-700/40"
                                >
                                    Dossier médical →
                                </Link>
                            </div>
                        </>
                    )}
                </div>
            )}
        </Sheet>
    );
}
