import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Audit {
    id: number;
    structure: string;
    type_grille: string;
    score_global: number;
    ecarts_critiques: number;
    ecarts_majeurs: number;
    date_audit: string;
    statut: 'planifie' | 'en_cours' | 'finalise' | 'clos';
    referent: string;
    date_prochain_audit: string | null;
}

const DEFAULT: Audit[] = [
    { id: 1, structure: 'SAAD Horizon Douala',   type_grille: 'HAS Évaluation externe', score_global: 84, ecarts_critiques: 1, ecarts_majeurs: 3, date_audit: '05/04/2026', statut: 'finalise', referent: 'Sophie Ateba',    date_prochain_audit: '05/04/2027' },
    { id: 2, structure: 'SSIAD Centre Yaoundé',  type_grille: 'AFNOR NF X50-056',       score_global: 71, ecarts_critiques: 2, ecarts_majeurs: 5, date_audit: '08/04/2026', statut: 'en_cours', referent: 'Bruno Ngono',     date_prochain_audit: null         },
    { id: 3, structure: 'SPASAD Nord',           type_grille: 'ISO 9001',               score_global: 0,  ecarts_critiques: 0, ecarts_majeurs: 0, date_audit: '15/04/2026', statut: 'planifie', referent: 'Pascaline Eko',   date_prochain_audit: null         },
    { id: 4, structure: 'SAAD Sud Littoral',     type_grille: 'Caphandeo',              score_global: 91, ecarts_critiques: 0, ecarts_majeurs: 1, date_audit: '02/04/2026', statut: 'finalise', referent: 'Sophie Ateba',    date_prochain_audit: '02/04/2027' },
    { id: 5, structure: 'ESAD Centre',           type_grille: 'Interne',                score_global: 78, ecarts_critiques: 1, ecarts_majeurs: 2, date_audit: '01/04/2026', statut: 'clos',     referent: 'Bruno Ngono',     date_prochain_audit: '01/10/2026' },
];

const STATUT_MAP = {
    planifie: { label: 'Planifié',  bg: '#F8FAFC', text: '#64748B' },
    en_cours: { label: 'En cours',  bg: '#FFFBEB', text: '#92400E' },
    finalise: { label: 'Finalisé',  bg: '#F0FDF4', text: '#166534' },
    clos:     { label: 'Clos',      bg: '#F1F5F9', text: '#475569' },
};

function ScoreBar({ score }: { score: number }) {
    const color = score >= 85 ? '#16A34A' : score >= 70 ? '#F59E0B' : score > 0 ? '#EF4444' : '#E2E8F0';
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <div style={{ flex: 1, height: 6, background: '#F1F5F9', borderRadius: 3, overflow: 'hidden' }}>
                <div style={{ width: `${score}%`, height: '100%', background: color, borderRadius: 3, transition: 'width 1s' }} />
            </div>
            <span style={{ fontSize: 13, fontWeight: 800, color, fontFamily: "'DM Mono', monospace", minWidth: 36 }}>
                {score > 0 ? `${score}%` : '—'}
            </span>
        </div>
    );
}

export default function Audits({ audits = DEFAULT }: { audits?: Audit[] }) {
    return (
        <DashboardLayout title="Audits & Conformité" subtitle="Grilles HAS · AFNOR · ISO · Caphandeo">

            <div className="flex items-center justify-between mb-5">
                <div style={{ display: 'flex', gap: 8 }}>
                    {['HAS', 'AFNOR', 'ISO 9001', 'Caphandeo', 'Interne'].map(g => (
                        <button key={g} style={{ fontSize: 12, fontWeight: 600, padding: '6px 12px', borderRadius: 8, border: '1px solid #E2E8F0', background: 'white', color: '#64748B', cursor: 'pointer' }}>{g}</button>
                    ))}
                </div>
                <Link href="/audits/create" style={{
                    display: 'inline-flex', alignItems: 'center', gap: 6,
                    background: 'var(--navy)', color: 'white',
                    fontSize: 13, fontWeight: 700, padding: '9px 18px', borderRadius: 10, textDecoration: 'none',
                }}>+ Lancer un audit</Link>
            </div>

            <div style={{ background: 'white', borderRadius: 16, border: '1px solid #F1F5F9', overflow: 'hidden' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                        <tr style={{ borderBottom: '1px solid #F1F5F9', background: '#FAFAFA' }}>
                            {['Structure', 'Grille', 'Score global', 'Écarts critiques', 'Écarts majeurs', 'Référent', 'Date', 'Statut', ''].map(h => (
                                <th key={h} style={{ padding: '11px 16px', fontSize: 11, fontWeight: 700, color: '#94A3B8', textAlign: 'left', letterSpacing: '.04em', textTransform: 'uppercase' }}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {audits.map((a, idx) => {
                            const st = STATUT_MAP[a.statut];
                            return (
                                <tr key={a.id} style={{ borderBottom: idx < audits.length - 1 ? '1px solid #F8FAFC' : 'none', transition: 'background .15s' }}
                                    onMouseEnter={e => (e.currentTarget as HTMLTableRowElement).style.background = '#FAFAFA'}
                                    onMouseLeave={e => (e.currentTarget as HTMLTableRowElement).style.background = 'transparent'}
                                >
                                    <td style={{ padding: '12px 16px', fontSize: 13, fontWeight: 600, color: 'var(--navy)' }}>{a.structure}</td>
                                    <td style={{ padding: '12px 16px', fontSize: 12, color: '#475569' }}>{a.type_grille}</td>
                                    <td style={{ padding: '12px 16px', minWidth: 140 }}><ScoreBar score={a.score_global} /></td>
                                    <td style={{ padding: '12px 16px', textAlign: 'center' }}>
                                        <span style={{ fontSize: 13, fontWeight: 700, color: a.ecarts_critiques > 0 ? '#EF4444' : '#94A3B8' }}>{a.ecarts_critiques > 0 ? a.ecarts_critiques : '—'}</span>
                                    </td>
                                    <td style={{ padding: '12px 16px', textAlign: 'center' }}>
                                        <span style={{ fontSize: 13, fontWeight: 700, color: a.ecarts_majeurs > 0 ? '#F59E0B' : '#94A3B8' }}>{a.ecarts_majeurs > 0 ? a.ecarts_majeurs : '—'}</span>
                                    </td>
                                    <td style={{ padding: '12px 16px', fontSize: 12, color: '#475569' }}>{a.referent}</td>
                                    <td style={{ padding: '12px 16px', fontSize: 12, color: '#475569', fontFamily: "'DM Mono', monospace" }}>{a.date_audit}</td>
                                    <td style={{ padding: '12px 16px' }}>
                                        <span style={{ fontSize: 11, fontWeight: 600, background: st.bg, color: st.text, padding: '3px 9px', borderRadius: 20 }}>{st.label}</span>
                                    </td>
                                    <td style={{ padding: '12px 16px' }}>
                                        <Link href={`/audits/${a.id}`} style={{ fontSize: 12, color: 'var(--gold)', textDecoration: 'none', fontWeight: 600 }}>Voir →</Link>
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