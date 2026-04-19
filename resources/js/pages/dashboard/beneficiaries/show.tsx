import { Form, Link, router } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    first_name: string;
    last_name: string;
    full_name: string;
    initials: string;
    date_of_birth: string | null;
    age: number | null;
    gender_label: string | null;
    address: string | null;
    postal_code: string | null;
    city: string | null;
    phone: string | null;
    email: string | null;
    marital_status: string | null;
    gir: number | null;
    primary_doctor: string | null;
    primary_doctor_phone: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    status_label: string;
    admitted_at: string | null;
    exited_at: string | null;
    is_erased: boolean;
}

interface Assignment {
    id: string;
    intervenant: { id: number; full_name: string; email: string };
    assigned_by: { id: number; full_name: string } | null;
    assigned_at: string | null;
    unassigned_at: string | null;
    is_active: boolean;
    notes: string | null;
}

interface EligibleIntervenant {
    id: number;
    full_name: string;
    email: string;
}

interface Props {
    beneficiary: { data: Beneficiary };
    assignments: { data: Assignment[] };
    eligible_intervenants: EligibleIntervenant[];
    can_assign: boolean;
}

const Field = ({ label, value }: { label: string; value: string | number | null }) => (
    <div style={{ marginBottom: 12 }}>
        <div style={{ fontSize: 11, color: '#64748B', marginBottom: 2 }}>{label}</div>
        <div style={{ fontSize: 13, color: 'var(--navy)' }}>{value ?? '—'}</div>
    </div>
);

const Section = ({ title, children }: { title: string; children: React.ReactNode }) => (
    <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
        <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14, textTransform: 'uppercase', letterSpacing: '0.05em' }}>{title}</h3>
        {children}
    </div>
);

const formatDateTime = (iso: string | null): string => {
    if (!iso) {
        return '—';
    }
    return new Date(iso).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' });
};

export default function BeneficiaryShow({ beneficiary: { data }, assignments, eligible_intervenants, can_assign }: Props) {
    const active = assignments.data.filter((a) => a.is_active);
    const historical = assignments.data.filter((a) => !a.is_active);

    const handleUnassign = (assignmentId: string, intervenantName: string) => {
        if (!confirm(`Mettre fin à l'assignation de ${intervenantName} ?`)) {
            return;
        }
        router.delete(`/assignments/${assignmentId}`, { preserveScroll: true });
    };

    return (
        <DashboardLayout title={data.full_name} subtitle={`Bénéficiaire · ${data.status_label}`}>

            <div style={{ display: 'flex', gap: 10, marginBottom: 20 }}>
                <Link href="/beneficiaries" style={{ fontSize: 12, color: '#64748B', textDecoration: 'none' }}>← Retour</Link>
                <span style={{ flex: 1 }} />
                <Link href={`/beneficiaries/${data.id}/dossier`} style={{
                    background: 'var(--navy)', color: 'white', fontSize: 12, fontWeight: 600,
                    padding: '8px 14px', borderRadius: 8, textDecoration: 'none',
                }}>📋 Dossier médical</Link>
                <Link href={`/beneficiaries/${data.id}/edit`} style={{
                    background: 'var(--gold)', color: 'var(--navy)', fontSize: 12, fontWeight: 700,
                    padding: '8px 14px', borderRadius: 8, textDecoration: 'none',
                }}>Modifier</Link>
            </div>

            <Section title="Identité">
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                    <Field label="Prénom" value={data.first_name} />
                    <Field label="Nom" value={data.last_name} />
                    <Field label="Date de naissance" value={data.date_of_birth} />
                    <Field label="Âge" value={data.age !== null ? `${data.age} ans` : null} />
                    <Field label="Sexe" value={data.gender_label} />
                    <Field label="Situation familiale" value={data.marital_status} />
                </div>
            </Section>

            <Section title="Coordonnées">
                <Field label="Adresse" value={data.address} />
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                    <Field label="Code postal" value={data.postal_code} />
                    <Field label="Ville" value={data.city} />
                    <Field label="Téléphone" value={data.phone} />
                    <Field label="Email" value={data.email} />
                </div>
            </Section>

            <Section title="Contexte médical (sans données sensibles)">
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                    <Field label="GIR" value={data.gir} />
                    <Field label="Médecin traitant" value={data.primary_doctor} />
                    <Field label="Téléphone médecin" value={data.primary_doctor_phone} />
                </div>
                <p style={{ fontSize: 11, color: '#64748B', marginTop: 10, fontStyle: 'italic' }}>
                    Les notes médicales, allergies, antécédents et traitements sont accessibles via le bouton « Dossier médical » ci-dessus. Chaque consultation est tracée dans le journal d'audit.
                </p>
            </Section>

            <Section title="Personne de référence">
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                    <Field label="Nom" value={data.emergency_contact_name} />
                    <Field label="Lien" value={data.emergency_contact_relationship} />
                    <Field label="Téléphone" value={data.emergency_contact_phone} />
                </div>
            </Section>

            <Section title="Suivi">
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                    <Field label="Date d'entrée" value={data.admitted_at} />
                    <Field label="Date de sortie" value={data.exited_at} />
                </div>
            </Section>

            <Section title={`Équipe d'intervention (${active.length} actif${active.length > 1 ? 's' : ''})`}>
                {active.length === 0 ? (
                    <p style={{ fontSize: 12, color: '#64748B', fontStyle: 'italic', marginBottom: can_assign ? 16 : 0 }}>
                        Aucun intervenant actuellement assigné à ce bénéficiaire.
                    </p>
                ) : (
                    <div style={{ marginBottom: can_assign ? 18 : 0 }}>
                        {active.map((a) => (
                            <div key={a.id} style={{
                                display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                                padding: '10px 12px', borderRadius: 8, background: '#F8FAFC', marginBottom: 8,
                            }}>
                                <div>
                                    <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)' }}>
                                        {a.intervenant.full_name}
                                    </div>
                                    <div style={{ fontSize: 11, color: '#64748B', marginTop: 2 }}>
                                        Depuis le {formatDateTime(a.assigned_at)}
                                        {a.assigned_by ? ` · par ${a.assigned_by.full_name}` : ''}
                                    </div>
                                    {a.notes ? (
                                        <div style={{ fontSize: 11, color: '#475569', marginTop: 4, fontStyle: 'italic' }}>
                                            « {a.notes} »
                                        </div>
                                    ) : null}
                                </div>
                                {can_assign ? (
                                    <button
                                        type="button"
                                        onClick={() => handleUnassign(a.id, a.intervenant.full_name)}
                                        style={{
                                            background: 'transparent', color: '#B91C1C', fontSize: 11, fontWeight: 600,
                                            padding: '6px 10px', borderRadius: 6, border: '1px solid #FECACA', cursor: 'pointer',
                                        }}
                                    >
                                        Mettre fin
                                    </button>
                                ) : null}
                            </div>
                        ))}
                    </div>
                )}

                {can_assign && eligible_intervenants.length > 0 ? (
                    <Form
                        action={`/beneficiaries/${data.id}/assignments`}
                        method="post"
                        resetOnSuccess
                        style={{
                            background: '#F1F5F9', borderRadius: 8, padding: 14,
                            display: 'flex', flexDirection: 'column', gap: 10,
                        }}
                    >
                        {({ errors, processing }) => (
                            <>
                                <div style={{ fontSize: 12, fontWeight: 600, color: 'var(--navy)' }}>
                                    Ajouter un intervenant à l'équipe
                                </div>
                                <select
                                    name="intervenant_id"
                                    required
                                    style={{
                                        padding: '8px 10px', fontSize: 13, borderRadius: 6,
                                        border: '1px solid #CBD5E1', background: 'white',
                                    }}
                                    defaultValue=""
                                >
                                    <option value="" disabled>— Choisir un intervenant —</option>
                                    {eligible_intervenants.map((i) => (
                                        <option key={i.id} value={i.id}>
                                            {i.full_name} ({i.email})
                                        </option>
                                    ))}
                                </select>
                                {errors.intervenant_id ? (
                                    <div style={{ fontSize: 11, color: '#B91C1C' }}>{errors.intervenant_id}</div>
                                ) : null}

                                <textarea
                                    name="notes"
                                    placeholder="Notes optionnelles (raison de l'assignation, instructions…)"
                                    rows={2}
                                    style={{
                                        padding: '8px 10px', fontSize: 12, borderRadius: 6,
                                        border: '1px solid #CBD5E1', background: 'white', resize: 'vertical',
                                    }}
                                />
                                {errors.notes ? (
                                    <div style={{ fontSize: 11, color: '#B91C1C' }}>{errors.notes}</div>
                                ) : null}

                                <button
                                    type="submit"
                                    disabled={processing}
                                    style={{
                                        background: 'var(--navy)', color: 'white', fontSize: 12, fontWeight: 600,
                                        padding: '9px 14px', borderRadius: 6, border: 'none', cursor: 'pointer',
                                        opacity: processing ? 0.6 : 1, alignSelf: 'flex-start',
                                    }}
                                >
                                    {processing ? 'Assignation…' : 'Assigner'}
                                </button>
                            </>
                        )}
                    </Form>
                ) : null}

                {can_assign && eligible_intervenants.length === 0 && active.length > 0 ? (
                    <p style={{ fontSize: 11, color: '#64748B', fontStyle: 'italic' }}>
                        Tous les intervenants de la structure sont déjà assignés à ce bénéficiaire.
                    </p>
                ) : null}

                {historical.length > 0 ? (
                    <details style={{ marginTop: 16 }}>
                        <summary style={{
                            fontSize: 11, color: '#64748B', cursor: 'pointer',
                            textTransform: 'uppercase', letterSpacing: '0.05em', fontWeight: 600,
                        }}>
                            Historique ({historical.length})
                        </summary>
                        <div style={{ marginTop: 10 }}>
                            {historical.map((a) => (
                                <div key={a.id} style={{
                                    padding: '8px 12px', borderRadius: 6, marginBottom: 6,
                                    fontSize: 12, color: '#475569', background: '#F8FAFC',
                                }}>
                                    <strong>{a.intervenant.full_name}</strong>
                                    <span style={{ color: '#64748B' }}>
                                        {' · '}du {formatDateTime(a.assigned_at)} au {formatDateTime(a.unassigned_at)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </details>
                ) : null}
            </Section>
        </DashboardLayout>
    );
}
