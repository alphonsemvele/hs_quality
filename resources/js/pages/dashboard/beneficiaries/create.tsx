import { Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import DashboardLayout from '../layout';

export default function BeneficiaryCreate() {
    const { data, setData, post, processing, errors } = useForm({
        first_name: '',
        last_name: '',
        date_of_birth: '',
        gender: '',
        address: '',
        postal_code: '',
        city: '',
        phone: '',
        email: '',
        gir: '',
        primary_doctor: '',
        primary_doctor_phone: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        emergency_contact_relationship: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/beneficiaries');
    };

    const inputStyle: React.CSSProperties = {
        width: '100%', padding: '8px 12px', borderRadius: 8,
        border: '1px solid #E2E8F0', fontSize: 13, outline: 'none',
    };
    const labelStyle: React.CSSProperties = {
        fontSize: 12, color: '#64748B', marginBottom: 4, display: 'block', fontWeight: 600,
    };
    const errorStyle: React.CSSProperties = {
        color: '#DC2626', fontSize: 11, marginTop: 4,
    };

    const Field = ({ name, label, type = 'text', children }: { name: keyof typeof data; label: string; type?: string; children?: React.ReactNode }) => (
        <div style={{ marginBottom: 14 }}>
            <label style={labelStyle}>{label}</label>
            {children ?? (
                <input
                    type={type}
                    style={inputStyle}
                    value={data[name]}
                    onChange={e => setData(name, e.target.value)}
                />
            )}
            {errors[name] && <div style={errorStyle}>{errors[name]}</div>}
        </div>
    );

    return (
        <DashboardLayout title="Nouveau bénéficiaire" subtitle="Création d'un dossier de bénéficiaire">

            <Link href="/beneficiaries" style={{ fontSize: 12, color: '#64748B', textDecoration: 'none', display: 'inline-block', marginBottom: 14 }}>← Annuler</Link>

            <form onSubmit={submit}>
                <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                    <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14 }}>Identité</h3>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                        <Field name="first_name" label="Prénom *" />
                        <Field name="last_name" label="Nom *" />
                        <Field name="date_of_birth" label="Date de naissance" type="date" />
                        <Field name="gender" label="Sexe">
                            <select style={inputStyle} value={data.gender} onChange={e => setData('gender', e.target.value)}>
                                <option value="">—</option>
                                <option value="m">Homme</option>
                                <option value="f">Femme</option>
                                <option value="u">Non spécifié</option>
                            </select>
                        </Field>
                    </div>
                </div>

                <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                    <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14 }}>Coordonnées</h3>
                    <Field name="address" label="Adresse" />
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                        <Field name="postal_code" label="Code postal" />
                        <Field name="city" label="Ville" />
                        <Field name="phone" label="Téléphone" />
                        <Field name="email" label="Email" type="email" />
                    </div>
                </div>

                <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                    <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14 }}>Contexte médical</h3>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                        <Field name="gir" label="GIR (1-6)" type="number" />
                        <Field name="primary_doctor" label="Médecin traitant" />
                        <Field name="primary_doctor_phone" label="Téléphone médecin" />
                    </div>
                </div>

                <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                    <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14 }}>Personne de référence</h3>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                        <Field name="emergency_contact_name" label="Nom" />
                        <Field name="emergency_contact_relationship" label="Lien" />
                        <Field name="emergency_contact_phone" label="Téléphone" />
                    </div>
                </div>

                <button type="submit" disabled={processing} style={{
                    background: 'var(--gold)', color: 'var(--navy)', fontSize: 13, fontWeight: 700,
                    padding: '12px 28px', borderRadius: 10, border: 'none', cursor: processing ? 'wait' : 'pointer',
                    opacity: processing ? 0.6 : 1,
                }}>
                    {processing ? 'Enregistrement…' : 'Créer le bénéficiaire'}
                </button>
            </form>
        </DashboardLayout>
    );
}
