import { Button, InterventionStatusBadge, Sheet } from '@/components/ui';
import { Link } from '@inertiajs/react';

type Statut = 'planifiee' | 'en_cours' | 'realisee' | 'annulee' | 'non_realisee';

export interface InterventionPreview {
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
    intervention: InterventionPreview | null;
    onClose: () => void;
}

export function InterventionPreviewSheet({ intervention, onClose }: Props) {
    return (
        <Sheet
            open={intervention !== null}
            onClose={onClose}
            size="md"
            title="Aperçu intervention"
            description={intervention?.date_heure_debut}
            iconTone="brand"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            }
            footer={
                intervention ? (
                    <>
                        <Button variant="ghost" onClick={onClose}>
                            Fermer
                        </Button>
                        <Link href={`/interventions/${intervention.id}`}>
                            <Button>Voir le détail complet →</Button>
                        </Link>
                    </>
                ) : null
            }
        >
            {intervention && (
                <div className="space-y-5">
                    <div className="flex items-center gap-3">
                        <span className="flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white">
                            {intervention.initials}
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="truncate text-sm font-semibold text-ink-900 dark:text-white">
                                {intervention.beneficiaire}
                            </p>
                            <p className="truncate text-xs text-ink-500 dark:text-ink-400">
                                Intervenant : {intervention.intervenant}
                            </p>
                        </div>
                        <InterventionStatusBadge statut={intervention.statut} />
                    </div>

                    <dl className="space-y-3 rounded-xl border border-ink-100 bg-ink-50/40 p-4 dark:border-ink-700/60 dark:bg-ink-900/30">
                        <Row label="Date & heure" value={<span className="font-mono">{intervention.date_heure_debut}</span>} />
                        {intervention.date_heure_fin && (
                            <Row
                                label="Terminée à"
                                value={<span className="font-mono">{new Date(intervention.date_heure_fin).toLocaleString('fr-FR')}</span>}
                            />
                        )}
                        {intervention.duree_minutes !== null && (
                            <Row label="Durée" value={`${intervention.duree_minutes} min`} />
                        )}
                        <Row label="Structure" value={intervention.structure} />
                        <Row
                            label="Mode"
                            value={
                                <span className={intervention.sync_offline ? 'inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2 py-0.5 text-[11px] font-medium text-brand-700 dark:bg-brand-900/30 dark:text-brand-300' : 'text-xs text-ink-500 dark:text-ink-400'}>
                                    {intervention.sync_offline ? '📱 Mobile (sync offline)' : '🖥 Web'}
                                </span>
                            }
                        />
                    </dl>

                    {intervention.compte_rendu && (
                        <div className="rounded-xl border border-sage-200 bg-sage-50/40 px-3 py-2.5 dark:border-sage-700/40 dark:bg-sage-900/15">
                            <p className="text-[11px] font-semibold uppercase tracking-wider text-sage-700 dark:text-sage-300">
                                Compte-rendu disponible
                            </p>
                            <p className="mt-1 text-xs text-sage-900/80 dark:text-sage-200/80">
                                Le rapport saisi par l'intervenant·e est consultable sur la fiche complète.
                            </p>
                        </div>
                    )}
                </div>
            )}
        </Sheet>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</dt>
            <dd className="text-sm text-ink-900 dark:text-white">{value}</dd>
        </div>
    );
}
