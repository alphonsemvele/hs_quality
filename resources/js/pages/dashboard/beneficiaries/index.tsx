import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
    initials: string;
    age: number | null;
    gir: number | null;
    city: string | null;
    status_label: string;
    is_erased: boolean;
}

interface Props {
    beneficiaries: { data: Beneficiary[] };
    meta: { total: number; current_page: number; last_page: number };
}

const GIR_COLORS: Record<number, string> = {
    1: '#DC2626',
    2: '#EA580C',
    3: '#D97706',
    4: '#65A30D',
    5: '#0891B2',
    6: '#0D9488',
};

export default function BeneficiariesIndex({ beneficiaries, meta }: Props) {
    return (
        <DashboardLayout title="Bénéficiaires" subtitle="Suivi des personnes accompagnées">

            <div className="flex items-center justify-between mb-5">
                <p style={{ fontSize: 13, color: '#64748B' }}>
                    <span style={{ fontWeight: 700, color: 'var(--navy)' }}>{meta.total}</span> bénéficiaire{meta.total > 1 ? 's' : ''} suivi{meta.total > 1 ? 's' : ''}
                </p>
                <Link href="/beneficiaries/create" style={{
                    display: 'inline-flex', alignItems: 'center', gap: 6,
                    background: 'var(--gold)', color: 'var(--navy)',
                    fontSize: 13, fontWeight: 700, padding: '9px 18px', borderRadius: 10, textDecoration: 'none',
                }}>+ Ajouter</Link>
            </div>

            {beneficiaries.data.length === 0 ? (
                <div style={{ background: 'white', borderRadius: 14, padding: '40px 20px', textAlign: 'center', color: '#64748B' }}>
                    Aucun bénéficiaire enregistré pour le moment.
                </div>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 14 }}>
                    {beneficiaries.data.map(b => (
                        <Link key={b.id} href={`/beneficiaries/${b.id}`} style={{
                            background: 'white', borderRadius: 14, border: '1px solid #F1F5F9',
                            padding: '18px 20px', opacity: b.is_erased ? .4 : 1,
                            textDecoration: 'none', color: 'inherit', display: 'block',
                            transition: 'box-shadow .2s',
                        }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginBottom: 12 }}>
                                <div style={{
                                    width: 40, height: 40, borderRadius: 10, background: '#F1F5F9',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    fontSize: 12, fontWeight: 800, color: 'var(--navy)',
                                }}>{b.initials}</div>
                                <div>
                                    <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)' }}>{b.full_name}</div>
                                    <div style={{ fontSize: 11, color: '#64748B' }}>
                                        {b.age !== null ? `${b.age} ans` : '—'}
                                        {b.city ? ` · ${b.city}` : ''}
                                    </div>
                                </div>
                            </div>

                            <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 11, color: '#64748B' }}>
                                <span>
                                    {b.gir !== null && (
                                        <span style={{
                                            background: GIR_COLORS[b.gir] ?? '#64748B', color: 'white',
                                            padding: '2px 8px', borderRadius: 6, fontSize: 10, fontWeight: 700,
                                        }}>GIR {b.gir}</span>
                                    )}
                                </span>
                                <span style={{ fontWeight: 600 }}>{b.status_label}</span>
                            </div>
                        </Link>
                    ))}
                </div>
            )}

            {meta.last_page > 1 && (
                <div style={{ marginTop: 20, textAlign: 'center', fontSize: 12, color: '#64748B' }}>
                    Page {meta.current_page} sur {meta.last_page}
                </div>
            )}
        </DashboardLayout>
    );
}
