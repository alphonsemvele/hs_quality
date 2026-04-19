import { Link, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
}

interface CarePlan {
    id: string;
    title: string;
    objectives: string | null;
    start_date: string | null;
    end_date: string | null;
}

interface Props {
    plan: { data: CarePlan };
    beneficiary: { data: Beneficiary };
}

export default function CarePlanEdit({ plan: { data: plan }, beneficiary: { data: b } }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        title: plan.title,
        objectives: plan.objectives ?? '',
        start_date: plan.start_date ?? '',
        end_date: plan.end_date ?? '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(`/care-plans/${plan.id}`);
    };

    const inputStyle: React.CSSProperties = {
        width: '100%', padding: '8px 12px', borderRadius: 8,
        border: '1px solid #E2E8F0', fontSize: 13, outline: 'none',
    };
    const labelStyle: React.CSSProperties = {
        fontSize: 12, color: '#64748B', marginBottom: 4, display: 'block', fontWeight: 600,
    };

    return (
        <DashboardLayout title={`Modifier — ${plan.title}`} subtitle={b.full_name}>
            <Link href={`/care-plans/${plan.id}`} style={{ fontSize: 12, color: '#64748B', textDecoration: 'none', display: 'inline-block', marginBottom: 14 }}>← Annuler</Link>

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
                    </div>

                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 14 }}>
                        <div>
                            <label style={labelStyle}>Date de début *</label>
                            <input type="date" style={inputStyle} value={data.start_date} onChange={e => setData('start_date', e.target.value)} />
                        </div>
                        <div>
                            <label style={labelStyle}>Date de fin (optionnel)</label>
                            <input type="date" style={inputStyle} value={data.end_date} onChange={e => setData('end_date', e.target.value)} />
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
