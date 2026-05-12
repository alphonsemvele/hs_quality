import { Button, IncidentGraviteBadge, IncidentStatusBadge, Sheet } from '@/components/ui';
import { Link } from '@inertiajs/react';

type Gravite = 'mineur' | 'significatif' | 'grave' | 'critique';
type Statut = 'declare' | 'en_analyse' | 'plan_actions' | 'clos';

export interface IncidentPreview {
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
    incident: IncidentPreview | null;
    onClose: () => void;
}

export function IncidentPreviewSheet({ incident, onClose }: Props) {
    return (
        <Sheet
            open={incident !== null}
            onClose={onClose}
            size="md"
            title="Aperçu incident"
            description={incident?.date_heure}
            iconTone="danger"
            icon={
                <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            }
            footer={
                incident ? (
                    <>
                        <Button variant="ghost" onClick={onClose}>
                            Fermer
                        </Button>
                        <Link href={`/incidents/${incident.id}`}>
                            <Button>Voir le détail complet →</Button>
                        </Link>
                    </>
                ) : null
            }
        >
            {incident && (
                <div className="space-y-5">
                    <div className="flex flex-wrap items-center gap-2">
                        <IncidentGraviteBadge gravite={incident.gravite} />
                        <IncidentStatusBadge statut={incident.statut} />
                        <span className="rounded-full bg-ink-100 px-2.5 py-0.5 text-[11px] font-medium text-ink-700 dark:bg-ink-700 dark:text-ink-200">
                            {incident.categorie}
                        </span>
                    </div>

                    <div className="rounded-xl border border-ink-100 bg-ink-50/40 p-4 dark:border-ink-700/60 dark:bg-ink-900/30">
                        <p className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">
                            Description (chiffrée au repos)
                        </p>
                        <p className="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-200">
                            {incident.description}
                        </p>
                    </div>

                    <dl className="space-y-3">
                        <Row label="Déclarant" value={incident.declarant} />
                        <Row label="Structure" value={incident.structure} />
                        <Row label="Date & heure" value={<span className="font-mono">{incident.date_heure}</span>} />
                    </dl>

                    <div className="space-y-2">
                        {incident.notifie_responsable && (
                            <NotifyBadge label="Responsable de secteur notifié·e" tone="brand" />
                        )}
                        {incident.notifie_autorites && (
                            <NotifyBadge label="ARS notifiée (sous 24h)" tone="warning" />
                        )}
                    </div>
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

function NotifyBadge({ label, tone }: { label: string; tone: 'brand' | 'warning' }) {
    const cls = tone === 'brand'
        ? 'border-brand-200 bg-brand-50/60 text-brand-900 dark:border-brand-700/40 dark:bg-brand-900/20 dark:text-brand-200'
        : 'border-warning-200 bg-warning-50/60 text-warning-900 dark:border-warning-700/40 dark:bg-warning-900/20 dark:text-warning-200';
    return (
        <div className={`flex items-center gap-2 rounded-lg border px-3 py-2 text-xs ${cls}`}>
            <svg className="size-3.5 shrink-0" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            <span className="font-medium">{label}</span>
        </div>
    );
}
