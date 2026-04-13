import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Formation {
    id: number;
    intervenant: string;
    structure: string;
    intitule: string;
    type_formation: 'habilitation' | 'certification' | 'elearning' | 'presentiel' | 'tutore';
    date_debut: string;
    date_expiration: string | null;
    statut: 'planifiee' | 'en_cours' | 'validee' | 'echouee' | 'annulee';
    jours_avant_expiration: number | null;
}

const DEFAULT: Formation[] = [
    { id: 1, intervenant: 'Marie Essomba',   structure: 'SAAD Horizon Douala',  intitule: 'DEAS — Diplôme État Aide-Soignant',      type_formation: 'certification', date_debut: '01/09/2022', date_expiration: null,         statut: 'validee',   jours_avant_expiration: null },
    { id: 2, intervenant: 'Jean Koffi',      structure: 'SSIAD Centre Yaoundé', intitule: 'Gestes et soins d\'urgence',              type_formation: 'habilitation',  date_debut: '15/03/2024', date_expiration: '15/03/2026', statut: 'validee',   jours_avant_expiration: 7   },
    { id: 3, intervenant: 'Bruno Ngono',     structure: 'SSIAD Centre Yaoundé', intitule: 'Prévention des risques psychosociaux',    type_formation: 'presentiel',    date_debut: '10/04/2026', date_expiration: null,         statut: 'en_cours',  jours_avant_expiration: null },
    { id: 4, intervenant: 'Amina Fofana',    structure: 'SPASAD Nord',          intitule: 'Module e-learning : Bientraitance',       type_formation: 'elearning',     date_debut: '05/04/2026', date_expiration: null,         statut: 'validee',   jours_avant_expiration: null },
    { id: 5, intervenant: 'Sophie Ateba',    structure: 'SAAD Horizon Douala',  intitule: 'Transferts et manutention bénéficiaires', type_formation: 'habilitation',  date_debut: '20/05/2025', date_expiration: '20/05/2026', statut: 'validee',   jours_avant_expiration: 42  },
    { id: 6, intervenant: 'Clément Touré',   structure: 'SSIAD Centre Yaoundé', intitule: 'Certification HACCP alimentation',        type_formation: 'certification', date_debut: '01/04/2025', date_expiration: '01/04/2026', statut: 'echouee',   jours_avant_expiration: -7  },
    { id: 7, intervenant: 'Pascaline Eko',   structure: 'SPASAD Nord',          intitule: 'Accompagnement personnes Alzheimer',     type_formation: 'tutore',        date_debut: '15/04/2026', date_expiration: null,         statut: 'planifiee', jours_avant_expiration: null },
];

const TYPE_COLORS: Record<string, { bg: string; text: string }> = {
    habilitation: { bg: '#EFF6FF', text: '#1D4ED8' },
    certification:{ bg: '#F5F3FF', text: '#7C3AED' },
    elearning:    { bg: '#F0FDF4', text: '#166534' },
    presentiel:   { bg: '#FFFBEB', text: '#92400E' },
    tutore:       { bg: '#F0F9FF', text: '#0369A1' },
};

const STATUT_MAP = {
    planifiee: { label: 'Planifiée', bg: '#F8FAFC', text: '#64748B' },
    en_cours:  { label: 'En cours',  bg: '#FFFBEB', text: '#92400E' },
    validee:   { label: 'Validée',   bg: '#F0FDF4', text: '#166534' },
    echouee:   { label: 'Échouée',   bg: '#FEF2F2', text: '#DC2626' },
    annulee:   { label: 'Annulée',   bg: '#F1F5F9', text: '#94A3B8' },
};

export default function Formations({ formations = DEFAULT }: { formations?: Formation[] }) {
    const expirent = formations.filter(f => f.jours_avant_expiration !== null && f.jours_avant_expiration >= 0 && f.jours_avant_expiration <= 60).length;
    const expirees = formations.filter(f => f.jours_avant_expiration !== null && f.jours_avant_expiration < 0).length;

    return (
        <DashboardLayout title="Formations & Habilitations" subtitle="Suivi des compétences et certifications">

            {expirent > 0 || expirees > 0 ? (
                <div style={{ background: '#FFF7ED', border: '1px solid #FED7AA', borderRadius: 12, padding: '12px 18px', marginBottom: 20, display: 'flex', alignItems: 'center', gap: 10 }}>
                    <svg width={18} height={18} fill="none" stroke="#F97316" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span style={{ fontSize: 13, color: '#92400E', fontWeight: 600 }}>
                        {expirees > 0 && `${expirees} habilitation(s) expirée(s)`}{expirees > 0 && expirent > 0 && ' · '}{expirent > 0 && `${expirent} expirent dans moins de 60 jours`}
                    </span>
                </div>
            ) : null}

            <div className="flex items-center justify-between mb-4">
                <div style={{ display: 'flex', gap: 8 }}>
                    {['Toutes', 'Habilitations', 'E-learning', 'À renouveler'].map(f => (
                        <button key={f} style={{ fontSize: 12, fontWeight: 600, padding: '6px 12px', borderRadius: 8, border: '1px solid #E2E8F0', background: 'white', color: '#64748B', cursor: 'pointer' }}>{f}</button>
                    ))}
                </div>
                <button style={{ display: 'inline-flex', alignItems: 'center', gap: 6, background: 'var(--navy)', color: 'white', fontSize: 13, fontWeight: 700, padding: '9px 18px', borderRadius: 10, border: 'none', cursor: 'pointer' }}>
                    + Enregistrer une formation
                </button>
            </div>

            <div style={{ background: 'white', borderRadius: 16, border: '1px solid #F1F5F9', overflow: 'hidden' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                    <thead>
                        <tr style={{ borderBottom: '1px solid #F1F5F9', background: '#FAFAFA' }}>
                            {['Intervenant', 'Formation', 'Type', 'Début', 'Expiration', 'Statut', ''].map(h => (
                                <th key={h} style={{ padding: '11px 16px', fontSize: 11, fontWeight: 700, color: '#94A3B8', textAlign: 'left', letterSpacing: '.04em', textTransform: 'uppercase' }}>{h}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {formations.map((f, idx) => {
                            const t = TYPE_COLORS[f.type_formation];
                            const st = STATUT_MAP[f.statut];
                            const expWarn = f.jours_avant_expiration !== null && f.jours_avant_expiration <= 60;
                            const expAlert = f.jours_avant_expiration !== null && f.jours_avant_expiration < 0;
                            return (
                                <tr key={f.id} style={{ borderBottom: idx < formations.length - 1 ? '1px solid #F8FAFC' : 'none', transition: 'background .15s' }}
                                    onMouseEnter={e => (e.currentTarget as HTMLTableRowElement).style.background = '#FAFAFA'}
                                    onMouseLeave={e => (e.currentTarget as HTMLTableRowElement).style.background = 'transparent'}
                                >
                                    <td style={{ padding: '12px 16px' }}>
                                        <div style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)' }}>{f.intervenant}</div>
                                        <div style={{ fontSize: 11, color: '#94A3B8' }}>{f.structure}</div>
                                    </td>
                                    <td style={{ padding: '12px 16px', fontSize: 13, color: '#475569', maxWidth: 220 }}>
                                        <div style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{f.intitule}</div>
                                    </td>
                                    <td style={{ padding: '12px 16px' }}>
                                        <span style={{ fontSize: 11, fontWeight: 600, background: t.bg, color: t.text, padding: '3px 9px', borderRadius: 20, textTransform: 'capitalize' }}>{f.type_formation}</span>
                                    </td>
                                    <td style={{ padding: '12px 16px', fontSize: 12, color: '#475569', fontFamily: "'DM Mono', monospace" }}>{f.date_debut}</td>
                                    <td style={{ padding: '12px 16px' }}>
                                        {f.date_expiration ? (
                                            <span style={{ fontSize: 12, fontFamily: "'DM Mono', monospace", fontWeight: expAlert || expWarn ? 700 : 400, color: expAlert ? '#DC2626' : expWarn ? '#F59E0B' : '#475569' }}>
                                                {f.date_expiration}
                                                {expAlert && ' ⚠'}
                                                {expWarn && !expAlert && ` (J−${f.jours_avant_expiration})`}
                                            </span>
                                        ) : <span style={{ color: '#CBD5E1' }}>—</span>}
                                    </td>
                                    <td style={{ padding: '12px 16px' }}>
                                        <span style={{ fontSize: 11, fontWeight: 600, background: st.bg, color: st.text, padding: '3px 9px', borderRadius: 20 }}>{st.label}</span>
                                    </td>
                                    <td style={{ padding: '12px 16px' }}>
                                        <button style={{ fontSize: 12, color: 'var(--gold)', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 600 }}>Voir →</button>
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