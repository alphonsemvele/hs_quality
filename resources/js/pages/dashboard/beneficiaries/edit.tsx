import { Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    first_name: string;
    last_name: string;
    full_name: string;
    date_of_birth: string | null;
    gender: string | null;
    address: string | null;
    postal_code: string | null;
    city: string | null;
    phone: string | null;
    email: string | null;
    gir: number | null;
    primary_doctor: string | null;
    primary_doctor_phone: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    status: string | null;
    admitted_at: string | null;
    exited_at: string | null;
    exit_reason: string | null;
}

interface Props {
    beneficiary: { data: Beneficiary };
}

export default function BeneficiaryEdit({ beneficiary: { data: b } }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        first_name: b.first_name ?? '',
        last_name: b.last_name ?? '',
        date_of_birth: b.date_of_birth ?? '',
        gender: b.gender ?? '',
        address: b.address ?? '',
        postal_code: b.postal_code ?? '',
        city: b.city ?? '',
        phone: b.phone ?? '',
        email: b.email ?? '',
        gir: b.gir?.toString() ?? '',
        primary_doctor: b.primary_doctor ?? '',
        primary_doctor_phone: b.primary_doctor_phone ?? '',
        emergency_contact_name: b.emergency_contact_name ?? '',
        emergency_contact_phone: b.emergency_contact_phone ?? '',
        emergency_contact_relationship: b.emergency_contact_relationship ?? '',
        status: b.status ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(`/beneficiaries/${b.id}`);
    };

    const inputStyle: React.CSSProperties = {
        width: '100%', padding: '8px 12px', borderRadius: 8,
        border: '1px solid #E2E8F0', fontSize: 13, outline: 'none',
    };
    const labelStyle: React.CSSProperties = {
        fontSize: 12, color: '#64748B', marginBottom: 4, display: 'block', fontWeight: 600,
    };

    return (
        <DashboardLayout title={`Modifier — ${b.full_name}`} subtitle="Modification du dossier bénéficiaire">

            <Link href={`/beneficiaries/${b.id}`} style={{ fontSize: 12, color: '#64748B', textDecoration: 'none', display: 'inline-block', marginBottom: 14 }}>← Annuler</Link>

            <form onSubmit={submit}>
                <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                    <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14 }}>Identité</h3>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                        <div>
                            <label style={labelStyle}>Prénom *</label>
                            <input style={inputStyle} value={data.first_name} onChange={e => setData('first_name', e.target.value)} />
                            {errors.first_name && <div style={{ color: '#DC2626', fontSize: 11, marginTop: 4 }}>{errors.first_name}</div>}
                        </div>
                        <div>
                            <label style={labelStyle}>Nom *</label>
                            <input style={inputStyle} value={data.last_name} onChange={e => setData('last_name', e.target.value)} />
                            {errors.last_name && <div style={{ color: '#DC2626', fontSize: 11, marginTop: 4 }}>{errors.last_name}</div>}
                        </div>
                        <div>
                            <label style={labelStyle}>Date de naissance</label>
                            <input type="date" style={inputStyle} value={data.date_of_birth} onChange={e => setData('date_of_birth', e.target.value)} />
                        </div>
                        <div>
                            <label style={labelStyle}>Statut</label>
                            <select style={inputStyle} value={data.status} onChange={e => setData('status', e.target.value)}>
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                                <option value="discharged">Sorti</option>
                                <option value="deceased">Décédé</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                    <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14 }}>Coordonnées</h3>
                    <div>
                        <label style={labelStyle}>Adresse</label>
                        <input style={inputStyle} value={data.address} onChange={e => setData('address', e.target.value)} />
                    </div>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14, marginTop: 14 }}>
                        <div>
                            <label style={labelStyle}>Téléphone</label>
                            <input style={inputStyle} value={data.phone} onChange={e => setData('phone', e.target.value)} />
                        </div>
                        <div>
                            <label style={labelStyle}>Email</label>
                            <input type="email" style={inputStyle} value={data.email} onChange={e => setData('email', e.target.value)} />
                        </div>
                    </div>
                </div>

                <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                    <h3 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)', marginBottom: 14 }}>Contexte médical</h3>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                        <div>
                            <label style={labelStyle}>GIR (1-6)</label>
                            <input type="number" min={1} max={6} style={inputStyle} value={data.gir} onChange={e => setData('gir', e.target.value)} />
                        </div>
                        <div>
                            <label style={labelStyle}>Médecin traitant</label>
                            <input style={inputStyle} value={data.primary_doctor} onChange={e => setData('primary_doctor', e.target.value)} />
                        </div>
                    </div>
                </div>

                <button type="submit" disabled={processing} style={{
                    background: 'var(--gold)', color: 'var(--navy)', fontSize: 13, fontWeight: 700,
                    padding: '12px 28px', borderRadius: 10, border: 'none', cursor: processing ? 'wait' : 'pointer',
                    opacity: processing ? 0.6 : 1,
                }}>
                    {processing ? 'Enregistrement…' : 'Enregistrer'}
                </button>
            </form>
        </DashboardLayout>
    );
}
