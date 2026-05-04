import { Badge, Card, CardBody, CardHeader, PageHeader } from '@/components/ui';
import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
    initials: string;
    age: number | null;
    gir: number | null;
    primary_doctor: string | null;
    medical_notes: string | null;
    allergies: string | null;
    medical_history: string | null;
    current_treatments: string | null;
}

function unwrap<T>(value: { data: T } | T): T {
    if (value && typeof value === 'object' && 'data' in (value as object)) {
        return (value as { data: T }).data;
    }
    return value as T;
}

export default function BeneficiaryDossier({ beneficiary }: { beneficiary: { data: Beneficiary } | Beneficiary }) {
    const b = unwrap<Beneficiary>(beneficiary);

    return (
        <DashboardLayout title={`Dossier — ${b.full_name}`} subtitle="">
            <PageHeader
                title={`Dossier médical · ${b.full_name}`}
                subtitle="Cet accès est journalisé et conservé 10 ans (CDC §5.2 « journalisation exhaustive »)."
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: b.full_name, href: `/beneficiaries/${b.id}` },
                    { label: 'Dossier médical' },
                ]}
                actions={
                    <Badge tone="warning" size="sm" dot>
                        Données de santé chiffrées
                    </Badge>
                }
            />

            <div className="mb-5 rounded-2xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-700/50 dark:bg-warning-900/20">
                <div className="flex items-start gap-3">
                    <svg className="size-5 shrink-0 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                    <div className="text-xs text-warning-700 dark:text-warning-300">
                        <p className="font-semibold">Accès loggé</p>
                        <p className="mt-0.5">
                            Cette consultation a été enregistrée avec votre identifiant, l'horodatage et l'adresse IP. Elle apparaît dans le journal d'audit du bénéficiaire.
                        </p>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card>
                    <CardHeader title="Synthèse" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row label="Bénéficiaire" value={b.full_name} />
                            <Row label="Âge" value={b.age ? `${b.age} ans` : '—'} />
                            <Row label="GIR" value={b.gir ? `${b.gir}` : '—'} />
                            <Row label="Médecin traitant" value={b.primary_doctor ?? '—'} />
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-2">
                    <CardHeader title="Notes médicales" />
                    <CardBody>
                        <Section label="Antécédents médicaux" content={b.medical_history} />
                        <Section label="Allergies" content={b.allergies} />
                        <Section label="Traitements en cours" content={b.current_treatments} />
                        <Section label="Notes" content={b.medical_notes} />
                    </CardBody>
                </Card>
            </div>

            <div className="mt-5">
                <Link href={`/beneficiaries/${b.id}`} className="text-sm text-ink-500 hover:text-brand-600 dark:text-ink-400 dark:hover:text-brand-400">
                    ← Retour au bénéficiaire
                </Link>
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

function Section({ label, content }: { label: string; content: string | null }) {
    return (
        <div className="border-b border-ink-100 py-3 last:border-b-0 dark:border-ink-700/60">
            <h4 className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</h4>
            <p className={`mt-1.5 text-sm leading-relaxed ${content ? 'text-ink-700 dark:text-ink-300' : 'italic text-ink-400 dark:text-ink-500'}`}>
                {content || 'Non renseigné.'}
            </p>
        </div>
    );
}
