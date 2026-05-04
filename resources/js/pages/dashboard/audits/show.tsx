import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Ecart {
    id: number;
    critere: string;
    constat: string;
    gravite: 'mineur' | 'majeur' | 'critique';
    action_corrective: string | null;
}

interface Audit {
    id: string;
    titre: string;
    referentiel: string;
    referentiel_label: string;
    description: string | null;
    date_audit: string | null;
    statut: string;
    statut_label: string;
    score: number | null;
    auditeur: string | null;
    ecarts: Ecart[];
    created_at: string | null;
}

interface Props {
    audit: Audit | null;
}

const GRAVITE_TONE: Record<string, 'neutral' | 'warning' | 'danger'> = {
    mineur: 'neutral',
    majeur: 'warning',
    critique: 'danger',
};

export default function AuditShow({ audit }: Props) {
    if (!audit) {
        return (
            <DashboardLayout title="Audit introuvable" subtitle="">
                <Card>
                    <EmptyState
                        title="Audit introuvable"
                        description="Cet audit n'existe pas ou n'est pas encore disponible."
                        action={<Link href="/audits"><Button variant="secondary">Retour aux audits</Button></Link>}
                    />
                </Card>
            </DashboardLayout>
        );
    }

    return (
        <DashboardLayout title={audit.titre} subtitle="">
            <PageHeader
                title={audit.titre}
                subtitle={`${audit.referentiel_label} · ${audit.date_audit ?? 'Non planifié'}`}
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Audits', href: '/audits' },
                    { label: audit.titre },
                ]}
                actions={
                    <>
                        <Badge tone="brand" size="sm">{audit.statut_label}</Badge>
                        {audit.score !== null && (
                            <span className="font-mono text-lg font-bold text-ink-900 dark:text-white">{audit.score}%</span>
                        )}
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Description" />
                    <CardBody>
                        {audit.description ? (
                            <p className="whitespace-pre-line text-sm leading-relaxed text-ink-700 dark:text-ink-300">{audit.description}</p>
                        ) : (
                            <p className="text-sm italic text-ink-500 dark:text-ink-400">Aucune description renseignée.</p>
                        )}
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Synthèse" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row label="Référentiel" value={audit.referentiel_label} />
                            <Row label="Date" value={audit.date_audit ?? '—'} />
                            <Row label="Auditeur" value={audit.auditeur ?? '—'} />
                            <Row label="Score" value={audit.score !== null ? `${audit.score} %` : '—'} />
                            <Row label="Écarts" value={`${audit.ecarts.length}`} />
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader title="Écarts identifiés" subtitle={`${audit.ecarts.length} écart(s)`} />
                    <CardBody>
                        {audit.ecarts.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {audit.ecarts.map((e) => (
                                    <li key={e.id} className="py-3">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium text-ink-900 dark:text-white">{e.critere}</p>
                                                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">{e.constat}</p>
                                                {e.action_corrective && (
                                                    <p className="mt-1 text-xs text-sage-700 dark:text-sage-300">→ {e.action_corrective}</p>
                                                )}
                                            </div>
                                            <Badge tone={GRAVITE_TONE[e.gravite] ?? 'neutral'} size="sm" dot>{e.gravite}</Badge>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Aucun écart identifié" description="Les écarts seront listés ici après évaluation des critères." />
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="flex items-center justify-between gap-3">
            <dt className="text-xs font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</dt>
            <dd className="text-sm font-medium text-ink-900 dark:text-white">{value}</dd>
        </div>
    );
}
