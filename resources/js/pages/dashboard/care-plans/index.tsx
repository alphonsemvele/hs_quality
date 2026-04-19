import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Beneficiary {
    id: string;
    full_name: string;
}

interface CarePlan {
    id: string;
    title: string;
    status: string;
    status_label: string;
    is_active: boolean;
    is_archived: boolean;
    start_date: string | null;
    end_date: string | null;
    tasks_count: number;
}

interface Props {
    beneficiary: { data: Beneficiary };
    plans: { data: CarePlan[] };
}

const STATUS_STYLES: Record<string, { bg: string; color: string }> = {
    draft: { bg: '#E2E8F0', color: '#475569' },
    active: { bg: '#DCFCE7', color: '#15803D' },
    archived: { bg: '#FEF3C7', color: '#92400E' },
};

export default function CarePlansIndex({ beneficiary: { data: b }, plans: { data } }: Props) {
    return (
        <DashboardLayout
            title={`Plans d'accompagnement — ${b.full_name}`}
            subtitle="Historique et plan actif"
        >
            <div style={{ display: 'flex', gap: 10, marginBottom: 20, alignItems: 'center' }}>
                <Link href={`/beneficiaries/${b.id}`} style={{ fontSize: 12, color: '#64748B', textDecoration: 'none' }}>
                    ← Retour au bénéficiaire
                </Link>
                <span style={{ flex: 1 }} />
                <Link href={`/beneficiaries/${b.id}/care-plans/create`} style={{
                    background: 'var(--gold)', color: 'var(--navy)',
                    fontSize: 13, fontWeight: 700, padding: '9px 18px', borderRadius: 10, textDecoration: 'none',
                }}>+ Nouveau plan</Link>
            </div>

            {data.length === 0 ? (
                <div style={{ background: 'white', borderRadius: 14, padding: '40px 20px', textAlign: 'center', color: '#64748B' }}>
                    Aucun plan d'accompagnement pour le moment.
                </div>
            ) : (
                <div style={{ display: 'grid', gap: 10 }}>
                    {data.map(plan => {
                        const s = STATUS_STYLES[plan.status] ?? STATUS_STYLES.draft;
                        return (
                            <Link key={plan.id} href={`/care-plans/${plan.id}`} style={{
                                background: 'white', borderRadius: 12, padding: '14px 18px',
                                textDecoration: 'none', color: 'inherit',
                                display: 'flex', alignItems: 'center', gap: 14,
                            }}>
                                <div style={{ flex: 1 }}>
                                    <div style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)' }}>{plan.title}</div>
                                    <div style={{ fontSize: 11, color: '#64748B', marginTop: 4 }}>
                                        Du {plan.start_date ?? '?'} {plan.end_date ? `au ${plan.end_date}` : '— en cours'}
                                        {' · '}
                                        {plan.tasks_count} tâche{plan.tasks_count > 1 ? 's' : ''}
                                    </div>
                                </div>
                                <span style={{
                                    background: s.bg, color: s.color,
                                    fontSize: 11, fontWeight: 700, padding: '4px 10px', borderRadius: 6,
                                }}>{plan.status_label}</span>
                            </Link>
                        );
                    })}
                </div>
            )}
        </DashboardLayout>
    );
}
