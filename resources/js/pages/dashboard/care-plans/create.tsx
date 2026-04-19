import { Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
}

interface Props {
    beneficiary: { data: Beneficiary };
}

export default function CarePlanCreate({ beneficiary: { data: b } }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        objectives: '',
        start_date: new Date().toISOString().slice(0, 10),
        end_date: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/beneficiaries/${b.id}/care-plans`);
    };

    const inputStyle: React.CSSProperties = {
        width: '100%', padding: '8px 12px', borderRadius: 8,
        border: '1px solid #E2E8F0', fontSize: 13, outline: 'none',
    };
    const labelStyle: React.CSSProperties = {
        fontSize: 12, color: '#64748B', marginBottom: 4, display: 'block', fontWeight: 600,
    };

    return (
        <DashboardLayout title="Nouveau plan d'accompagnement" subtitle={b.full_name}>
            <Link href={`/beneficiaries/${b.id}/care-plans`} style={{ fontSize: 12, color: '#64748B', textDecoration: 'none', display: 'inline-block', marginBottom: 14 }}>← Annuler</Link>

            <form onSubmit={submit}>
                <div style={{ background: 'white', borderRadius: 14, padding: 20, marginBottom: 14 }}>
                    <div style={{ marginBottom: 14 }}>
                        <label style={labelStyle}>Titre du plan *</label>
                        <input style={inputStyle} value={data.title} onChange={e => setData('title', e.target.value)} />
                        {errors.title && <div style={{ color: '#DC2626', fontSize: 11, marginTop: 4 }}>{errors.title}</div>}
                    </div>

                    <div style={{ marginBottom: 14 }}>
                        <label style={labelStyle}>Objectifs</label>
                        <textarea rows={6} style={{ ...inputStyle, resize: 'vertical' }} value={data.objectives} onChange={e => setData('objectives', e.target.value)} />
                        {errors.objectives && <div style={{ color: '#DC2626', fontSize: 11, marginTop: 4 }}>{errors.objectives}</div>}
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                        <div>
                            <label style={labelStyle}>Date de début *</label>
                            <input type="date" style={inputStyle} value={data.start_date} onChange={e => setData('start_date', e.target.value)} />
                            {errors.start_date && <div style={{ color: '#DC2626', fontSize: 11, marginTop: 4 }}>{errors.start_date}</div>}
                        </div>
                        <div>
                            <label style={labelStyle}>Date de fin (optionnel)</label>
                            <input type="date" style={inputStyle} value={data.end_date} onChange={e => setData('end_date', e.target.value)} />
                            {errors.end_date && <div style={{ color: '#DC2626', fontSize: 11, marginTop: 4 }}>{errors.end_date}</div>}
                        </div>
                    </div>
                </div>

                <button type="submit" disabled={processing} style={{
                    background: 'var(--gold)', color: 'var(--navy)', fontSize: 13, fontWeight: 700,
                    padding: '12px 28px', borderRadius: 10, border: 'none', cursor: processing ? 'wait' : 'pointer',
                    opacity: processing ? 0.6 : 1,
                }}>
                    {processing ? 'Enregistrement…' : 'Créer le plan en brouillon'}
                </button>
            </form>
        </DashboardLayout>
    );
}
