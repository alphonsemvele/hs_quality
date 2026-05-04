import { Badge, Button, Card, CardBody, CardHeader, EmptyState, PageHeader } from '@/components/ui';
import { Form, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
    initials: string;
    age: number | null;
    gir: number | null;
    gender_label: string | null;
    address: string | null;
    postal_code: string | null;
    city: string | null;
    phone: string | null;
    email: string | null;
    primary_doctor: string | null;
    primary_doctor_phone: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    status: string | null;
    status_label: string | null;
    admitted_at: string | null;
    is_erased: boolean;
}

interface Assignment {
    id: number | string;
    intervenant: { id: number; full_name: string; email: string } | null;
    assigned_at: string | null;
    unassigned_at: string | null;
    is_active: boolean;
    notes: string | null;
}

interface Intervenant {
    id: number;
    full_name: string;
    email: string;
}

interface Props {
    beneficiary: { data: Beneficiary } | Beneficiary;
    assignments: { data: Assignment[] } | Assignment[];
    eligible_intervenants: Intervenant[];
    can_assign: boolean;
}

function unwrap<T>(value: { data: T } | T): T {
    if (value && typeof value === 'object' && 'data' in (value as object)) {
        return (value as { data: T }).data;
    }
    return value as T;
}

export default function BeneficiaryShow({ beneficiary, assignments, eligible_intervenants, can_assign }: Props) {
    const b = unwrap<Beneficiary>(beneficiary);
    const assignmentsList = unwrap<Assignment[]>(assignments) ?? [];

    const [showAttach, setShowAttach] = useState(false);

    const detach = (id: number | string) => {
        if (confirm('Désaffecter cet intervenant ?')) {
            router.delete(`/assignments/${id}`);
        }
    };

    return (
        <DashboardLayout title={b.full_name} subtitle="">
            <PageHeader
                title={b.full_name}
                subtitle={
                    [
                        b.gender_label,
                        b.age ? `${b.age} ans` : null,
                        b.gir ? `GIR ${b.gir}` : null,
                        b.city,
                    ]
                        .filter(Boolean)
                        .join(' · ') || ' '
                }
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Bénéficiaires', href: '/beneficiaries' },
                    { label: b.full_name },
                ]}
                actions={
                    <>
                        <Link href={`/beneficiaries/${b.id}/dossier`}>
                            <Button variant="secondary">Dossier médical</Button>
                        </Link>
                        <Link href={`/beneficiaries/${b.id}/care-plans`}>
                            <Button variant="secondary">Plans</Button>
                        </Link>
                        <Link href={`/beneficiaries/${b.id}/edit`}>
                            <Button>Modifier</Button>
                        </Link>
                    </>
                }
            />

            <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Informations" />
                    <CardBody>
                        <dl className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Field label="Adresse" value={b.address ?? '—'} />
                            <Field
                                label="Localité"
                                value={[b.postal_code, b.city].filter(Boolean).join(' ') || '—'}
                            />
                            <Field label="Téléphone" value={b.phone ?? '—'} mono />
                            <Field label="Email" value={b.email ?? '—'} mono />
                            <Field label="Médecin traitant" value={b.primary_doctor ?? '—'} />
                            <Field label="Téléphone médecin" value={b.primary_doctor_phone ?? '—'} mono />
                        </dl>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader title="Statut & contact d'urgence" />
                    <CardBody>
                        <dl className="space-y-3.5">
                            <Row
                                label="Statut"
                                value={
                                    <Badge tone={b.status === 'active' ? 'sage' : 'neutral'} size="sm" dot={b.status === 'active'}>
                                        {b.status_label ?? b.status ?? '—'}
                                    </Badge>
                                }
                            />
                            {b.admitted_at && (
                                <Row
                                    label="Admis le"
                                    value={<span className="font-mono text-xs">{b.admitted_at}</span>}
                                />
                            )}
                            <Row label="Contact d'urgence" value={b.emergency_contact_name ?? '—'} />
                            {b.emergency_contact_relationship && (
                                <Row label="Lien" value={b.emergency_contact_relationship} />
                            )}
                            {b.emergency_contact_phone && (
                                <Row
                                    label="Téléphone"
                                    value={<span className="font-mono">{b.emergency_contact_phone}</span>}
                                />
                            )}
                        </dl>
                    </CardBody>
                </Card>

                <Card className="lg:col-span-3">
                    <CardHeader
                        title="Intervenants assignés"
                        subtitle={`${assignmentsList.filter((a) => a.is_active).length} actif(s) · ${assignmentsList.length} historiquement`}
                        action={
                            can_assign && (
                                <Button variant="secondary" size="sm" onClick={() => setShowAttach(!showAttach)}>
                                    {showAttach ? 'Annuler' : '+ Affecter'}
                                </Button>
                            )
                        }
                    />
                    <CardBody>
                        {showAttach && eligible_intervenants.length > 0 && (
                            <div className="mb-5 rounded-xl border border-brand-200 bg-brand-50 p-4 dark:border-brand-700/50 dark:bg-brand-900/20">
                                <Form action={`/beneficiaries/${b.id}/assignments`} method="post" resetOnSuccess>
                                    {({ processing }) => (
                                        <div className="flex flex-wrap items-end gap-3">
                                            <div className="min-w-64 flex-1">
                                                <label className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">
                                                    Intervenant à affecter
                                                </label>
                                                <select
                                                    name="user_id"
                                                    required
                                                    defaultValue=""
                                                    className="h-10 w-full rounded-lg border border-brand-200 bg-white px-3 text-sm text-ink-900 dark:border-brand-700/50 dark:bg-ink-800 dark:text-ink-100"
                                                >
                                                    <option value="" disabled>
                                                        Sélectionner
                                                    </option>
                                                    {eligible_intervenants.map((u) => (
                                                        <option key={u.id} value={u.id}>
                                                            {u.full_name} ({u.email})
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <Button type="submit" loading={processing}>
                                                Affecter
                                            </Button>
                                        </div>
                                    )}
                                </Form>
                            </div>
                        )}

                        {assignmentsList.length > 0 ? (
                            <ul className="divide-y divide-ink-100 dark:divide-ink-700/60">
                                {assignmentsList.map((a) => (
                                    <li key={a.id} className="flex items-center gap-3 py-3">
                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                                            {a.intervenant?.full_name
                                                ? a.intervenant.full_name
                                                      .split(' ')
                                                      .map((n) => n[0])
                                                      .join('')
                                                      .toUpperCase()
                                                      .slice(0, 2)
                                                : '?'}
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-medium text-ink-900 dark:text-white">
                                                {a.intervenant?.full_name ?? 'Intervenant'}
                                            </p>
                                            <p className="font-mono text-xs text-ink-500 dark:text-ink-400">{a.intervenant?.email}</p>
                                        </div>
                                        {a.is_active ? (
                                            <Badge tone="sage" size="sm" dot>
                                                Actif
                                            </Badge>
                                        ) : (
                                            <Badge tone="neutral" size="sm">
                                                Historique
                                            </Badge>
                                        )}
                                        {a.is_active && can_assign && (
                                            <Button variant="ghost" size="sm" onClick={() => detach(a.id)}>
                                                Désaffecter
                                            </Button>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState
                                title="Aucun intervenant assigné"
                                description="Affectez un intervenant pour qu'il puisse réaliser des interventions chez ce bénéficiaire."
                            />
                        )}
                    </CardBody>
                </Card>
            </div>
        </DashboardLayout>
    );
}

function Field({ label, value, mono }: { label: string; value: string; mono?: boolean }) {
    return (
        <div>
            <dt className="text-[11px] font-semibold uppercase tracking-wider text-ink-500 dark:text-ink-400">{label}</dt>
            <dd className={mono ? 'mt-1 font-mono text-sm text-ink-900 dark:text-white' : 'mt-1 text-sm font-medium text-ink-900 dark:text-white'}>{value}</dd>
        </div>
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
