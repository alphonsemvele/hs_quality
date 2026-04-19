import { Link } from '@inertiajs/react';
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

interface Props {
    beneficiary: { data: Beneficiary };
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

export default function BeneficiaryShow({ beneficiary: { data } }: Props) {
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
        </DashboardLayout>
    );
}
