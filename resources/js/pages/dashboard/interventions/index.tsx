import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Intervention {
    id: number;
    initials: string;
    intervenant: string;
    beneficiaire: string;
    structure: string;
    date_heure_debut: string;
    date_heure_fin: string | null;
    duree_minutes: number | null;
    statut: 'planifiee' | 'en_cours' | 'realisee' | 'annulee' | 'non_realisee';
    compte_rendu: string | null;
    sync_offline: boolean;
}

interface Props {
    interventions: Intervention[];
    total: number;
    stats: { planifiees: number; en_cours: number; realisees: number; annulees: number };
}

const DEFAULT_INTERVENTIONS: Intervention[] = [
    { id: 1, initials: 'ME', intervenant: 'Marie Essomba',   beneficiaire: 'Pierre Mbarga',    structure: 'SAAD Horizon Douala',   date_heure_debut: '08/04/2026 08:00', date_heure_fin: '08/04/2026 10:00', duree_minutes: 120, statut: 'realisee',     compte_rendu: 'RAS. Bénéficiaire en forme.',   sync_offline: false },
    { id: 2, initials: 'JK', intervenant: 'Jean Koffi',      beneficiaire: 'Élise Ngo',         structure: 'SSIAD Centre Yaoundé',  date_heure_debut: '08/04/2026 09:30', date_heure_fin: null,               duree_minutes: null,  statut: 'en_cours',     compte_rendu: null,                            sync_offline: false },
    { id: 3, initials: 'AF', intervenant: 'Amina Fofana',    beneficiaire: 'Jules Atangana',    structure: 'SPASAD Nord',           date_heure_debut: '08/04/2026 11:00', date_heure_fin: null,               duree_minutes: null,  statut: 'planifiee',    compte_rendu: null,                            sync_offline: false },
    { id: 4, initials: 'PB', intervenant: 'Paul Biya Jr.',   beneficiaire: 'Cécile Fouda',      structure: 'SAAD Sud Littoral',     date_heure_debut: '07/04/2026 14:00', date_heure_fin: '07/04/2026 16:30', duree_minutes: 150, statut: 'realisee',     compte_rendu: 'Soins effectués. Famille présente.', sync_offline: false },
    { id: 5, initials: 'FN', intervenant: 'Fatima Ndiaye',   beneficiaire: 'Robert Owona',      structure: 'SAAD Horizon Douala',   date_heure_debut: '08/04/2026 07:00', date_heure_fin: '08/04/2026 09:00', duree_minutes: 120, statut: 'realisee',     compte_rendu: 'Ménage + repas préparé.',       sync_offline: true  },
    { id: 6, initials: 'CT', intervenant: 'Clément Touré',   beneficiaire: 'Agnès Belinga',     structure: 'SSIAD Centre Yaoundé',  date_heure_debut: '08/04/2026 13:00', date_heure_fin: null,               duree_minutes: null,  statut: 'annulee',      compte_rendu: null,                            sync_offline: false },
];

const STATUT_MAP = {
    planifiee:    { label: 'Planifiée',    bg: '#EFF6FF', text: '#1D4ED8', dot: '#3B82F6' },
    en_cours:     { label: 'En cours',     bg: '#FFFBEB', text: '#92400E', dot: '#F59E0B' },
    realisee:     { label: 'Réalisée',     bg: '#F0FDF4', text: '#166534', dot: '#16A34A' },
    annulee:      { label: 'Annulée',      bg: '#FEF2F2', text: '#991B1B', dot: '#EF4444' },
    non_realisee: { label: 'Non réalisée', bg: '#F8FAFC', text: '#475569', dot: '#94A3B8' },
};

export default function Interventions({ interventions = DEFAULT_INTERVENTIONS, total = DEFAULT_INTERVENTIONS.length, stats = { planifiees: 3, en_cours: 1, realisees: 3, annulees: 1 } }: Partial<Props>) {
    return (
        <DashboardLayout title="Interventions" subtitle="Suivi des interventions à domicile">

            {/* Header actions */}
            <div className="flex items-center justify-between mb-5">
                <div className="flex items-center gap-3 flex-wrap">
                    {[
                        { label: 'Toutes', count: total,          active: true  },
                        { label: 'En cours',  count: stats.en_cours,  active: false },
                        { label: 'Planifiées',count: stats.planifiees,active: false },
                        { label: 'Réalisées', count: stats.realisees, active: false },
                    ].map(f => (
                        <button key={f.label} style={{
                            fontSize: 13, fontWeight: 600, padding: '7px 14px', borderRadius: 10, border: 'none', cursor: 'pointer',
                            background: f.active ? 'var(--navy)' : 'white',
                            color: f.active ? 'white' : '#64748B',
                            // border: f.active ? 'none' : '1px solid #E2E8F0',
                        }}>
                            {f.label} <span style={{ fontSize: 11, opacity: .7 }}>({f.count})</span>
                        </button>
                    ))}
                </div>
                <Link href="/interventions/create" style={{
                    display: 'inline-flex', alignItems: 'center', gap: 6,
                    background: 'var(--gold)', color: 'var(--navy)',
                    fontSize: 13, fontWeight: 700, padding: '9px 18px', borderRadius: 10, textDecoration: 'none',
                }}>
                    <svg width={14} height={14} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14"/></svg>
                    Nouvelle intervention
                </Link>
            </div>

            {/* Table */}
            <div style={{ background: 'white', borderRadius: 16, border: '1px solid #F1F5F9', overflow: 'hidden' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                        <tr style={{ borderBottom: '1px solid #F1F5F9', background: '#FAFAFA' }}>
                            {['Intervenant', 'Bénéficiaire', 'Structure', 'Date & Heure', 'Durée', 'Statut', 'CR', ''].map(h => (
                                <th key={h} style={{ padding: '11px 16px', fontSize: 11, fontWeight: 700, color: '#94A3B8', textAlign: 'left', letterSpacing: '.04em', textTransform: 'uppercase' }}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {interventions.map((i, idx) => {
                            const st = STATUT_MAP[i.statut];
                            return (
                                <tr key={i.id} style={{ borderBottom: idx < interventions.length - 1 ? '1px solid #F8FAFC' : 'none', transition: 'background .15s', cursor: 'default' }}
                                    onMouseEnter={e => (e.currentTarget as HTMLTableRowElement).style.background = '#FAFAFA'}
                                    onMouseLeave={e => (e.currentTarget as HTMLTableRowElement).style.background = 'transparent'}
                                >
                                    <td style={{ padding: '12px 16px' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                            <div style={{ width: 32, height: 32, borderRadius: 9, background: '#F1F5F9', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, fontWeight: 800, color: 'var(--navy)', flexShrink: 0 }}>{i.initials}</div>
                                            <span style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)' }}>{i.intervenant}</span>
                                        </div>
                                    </td>
                                    <td style={{ padding: '12px 16px', fontSize: 13, color: '#475569' }}>{i.beneficiaire}</td>
                                    <td style={{ padding: '12px 16px', fontSize: 12, color: '#94A3B8' }}>{i.structure}</td>
                                    <td style={{ padding: '12px 16px', fontSize: 12, color: '#475569', fontFamily: "'DM Mono', monospace" }}>{i.date_heure_debut}</td>
                                    <td style={{ padding: '12px 16px', fontSize: 12, color: '#475569', fontFamily: "'DM Mono', monospace" }}>
                                        {i.duree_minutes ? `${i.duree_minutes} min` : '—'}
                                    </td>
                                    <td style={{ padding: '12px 16px' }}>
                                        <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, fontSize: 11, fontWeight: 600, background: st.bg, color: st.text, padding: '3px 9px', borderRadius: 20 }}>
                                            <span style={{ width: 5, height: 5, borderRadius: '50%', background: st.dot }} />
                                            {st.label}
                                        </span>
                                    </td>
                                    <td style={{ padding: '12px 16px' }}>
                                        {i.compte_rendu
                                            ? <span style={{ fontSize: 11, color: '#16A34A' }}>✓</span>
                                            : <span style={{ fontSize: 11, color: '#CBD5E1' }}>—</span>
                                        }
                                        {i.sync_offline && <span style={{ marginLeft: 6, fontSize: 10, background: '#FFFBEB', color: '#92400E', padding: '1px 6px', borderRadius: 6, fontWeight: 600 }}>offline</span>}
                                    </td>
                                    <td style={{ padding: '12px 16px' }}>
                                        <Link href={`/interventions/${i.id}`} style={{ fontSize: 12, color: 'var(--gold)', textDecoration: 'none', fontWeight: 600 }}>Voir →</Link>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </DashboardLayout>
    );
}