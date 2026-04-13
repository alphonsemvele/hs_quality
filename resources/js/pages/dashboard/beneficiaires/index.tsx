import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Beneficiaire {
    id: number;
    initials: string;
    nom: string;
    prenom: string;
    structure: string;
    gir: number | null;
    type_dependance: 'PA' | 'PH' | 'enfant' | 'autre';
    interventions_ce_mois: number;
    dernier_incident: string | null;
    actif: boolean;
    consentement_rgpd: boolean;
}

interface Props {
    beneficiaires: Beneficiaire[];
    total: number;
}

const DEFAULT: Beneficiaire[] = [
    { id: 1, initials: 'PM', nom: 'Mbarga',    prenom: 'Pierre',    structure: 'SAAD Horizon Douala',  gir: 3, type_dependance: 'PA', interventions_ce_mois: 12, dernier_incident: null,         actif: true,  consentement_rgpd: true  },
    { id: 2, initials: 'EN', nom: 'Ngo',       prenom: 'Élise',     structure: 'SSIAD Centre Yaoundé', gir: 2, type_dependance: 'PA', interventions_ce_mois: 8,  dernier_incident: '05/04/2026', actif: true,  consentement_rgpd: true  },
    { id: 3, initials: 'JA', nom: 'Atangana',  prenom: 'Jules',     structure: 'SPASAD Nord',          gir: 4, type_dependance: 'PH', interventions_ce_mois: 6,  dernier_incident: null,         actif: true,  consentement_rgpd: true  },
    { id: 4, initials: 'CF', nom: 'Fouda',     prenom: 'Cécile',    structure: 'SAAD Sud Littoral',    gir: 1, type_dependance: 'PA', interventions_ce_mois: 20, dernier_incident: '07/04/2026', actif: true,  consentement_rgpd: true  },
    { id: 5, initials: 'RO', nom: 'Owona',     prenom: 'Robert',    structure: 'SAAD Horizon Douala',  gir: 5, type_dependance: 'PA', interventions_ce_mois: 4,  dernier_incident: null,         actif: true,  consentement_rgpd: false },
    { id: 6, initials: 'AB', nom: 'Belinga',   prenom: 'Agnès',     structure: 'SSIAD Centre Yaoundé', gir: 3, type_dependance: 'PH', interventions_ce_mois: 10, dernier_incident: null,         actif: false, consentement_rgpd: true  },
];

const TYPE_MAP = { PA: { label: 'Personne âgée', color: '#3B82F6' }, PH: { label: 'Handicap', color: '#7C3AED' }, enfant: { label: 'Enfant', color: '#10B981' }, autre: { label: 'Autre', color: '#64748B' } };

export default function Beneficiaires({ beneficiaires = DEFAULT, total = DEFAULT.length }: Partial<Props>) {
    return (
        <DashboardLayout title="Bénéficiaires" subtitle="Suivi des personnes accompagnées">

            <div className="flex items-center justify-between mb-5">
                <p style={{ fontSize: 13, color: '#64748B' }}><span style={{ fontWeight: 700, color: 'var(--navy)' }}>{total}</span> bénéficiaires suivis</p>
                <div style={{ display: 'flex', gap: 10 }}>
                    <input placeholder="Rechercher un bénéficiaire…" style={{ fontSize: 13, padding: '8px 14px', borderRadius: 10, border: '1px solid #E2E8F0', outline: 'none', width: 240 }} />
                    <Link href="/beneficiaires/create" style={{
                        display: 'inline-flex', alignItems: 'center', gap: 6,
                        background: 'var(--gold)', color: 'var(--navy)',
                        fontSize: 13, fontWeight: 700, padding: '9px 18px', borderRadius: 10, textDecoration: 'none',
                    }}>+ Ajouter</Link>
                </div>
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))', gap: 14 }}>
                {beneficiaires.map(b => {
                    const t = TYPE_MAP[b.type_dependance];
                    return (
                        <div key={b.id} style={{ background: 'white', borderRadius: 14, border: `1px solid ${b.actif ? '#F1F5F9' : '#F8FAFC'}`, padding: '18px 20px', opacity: b.actif ? 1 : .55, transition: 'box-shadow .2s' }}
                            onMouseEnter={e => (e.currentTarget as HTMLDivElement).style.boxShadow = '0 4px 20px rgba(11,22,40,.07)'}
                            onMouseLeave={e => (e.currentTarget as HTMLDivElement).style.boxShadow = 'none'}
                        >
                            <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 12 }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                    <div style={{ width: 40, height: 40, borderRadius: 10, background: '#F1F5F9', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 12, fontWeight: 800, color: 'var(--navy)' }}>{b.initials}</div>
                                    <div>
                                        <p style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)' }}>{b.prenom} {b.nom}</p>
                                        <p style={{ fontSize: 11, color: '#94A3B8' }}>{b.structure}</p>
                                    </div>
                                </div>
                                {!b.actif && <span style={{ fontSize: 10, background: '#F1F5F9', color: '#94A3B8', padding: '3px 8px', borderRadius: 6, fontWeight: 600 }}>Inactif</span>}
                            </div>

                            <div style={{ display: 'flex', gap: 8, marginBottom: 12, flexWrap: 'wrap' }}>
                                <span style={{ fontSize: 11, fontWeight: 600, background: t.color + '15', color: t.color, padding: '3px 9px', borderRadius: 20 }}>{t.label}</span>
                                {b.gir && <span style={{ fontSize: 11, fontWeight: 600, background: '#FFFBEB', color: '#92400E', padding: '3px 9px', borderRadius: 20 }}>GIR {b.gir}</span>}
                                {!b.consentement_rgpd && <span style={{ fontSize: 10, background: '#FEF2F2', color: '#DC2626', border: '1px solid #FECACA', padding: '2px 7px', borderRadius: 6, fontWeight: 700 }}>RGPD manquant</span>}
                            </div>

                            <div style={{ display: 'flex', justifyContent: 'space-between', paddingTop: 12, borderTop: '1px solid #F8FAFC' }}>
                                <div style={{ textAlign: 'center' }}>
                                    <div style={{ fontSize: 16, fontWeight: 800, color: 'var(--navy)', fontFamily: "'DM Mono', monospace" }}>{b.interventions_ce_mois}</div>
                                    <div style={{ fontSize: 10, color: '#94A3B8' }}>interventions/mois</div>
                                </div>
                                <div style={{ textAlign: 'center' }}>
                                    {b.dernier_incident
                                        ? <><div style={{ fontSize: 12, fontWeight: 700, color: '#EF4444' }}>{b.dernier_incident}</div><div style={{ fontSize: 10, color: '#94A3B8' }}>dernier incident</div></>
                                        : <><div style={{ fontSize: 12, fontWeight: 700, color: '#16A34A' }}>Aucun</div><div style={{ fontSize: 10, color: '#94A3B8' }}>incident récent</div></>
                                    }
                                </div>
                                <Link href={`/beneficiaires/${b.id}`} style={{ alignSelf: 'center', fontSize: 12, color: 'var(--gold)', textDecoration: 'none', fontWeight: 600 }}>Voir →</Link>
                            </div>
                        </div>
                    );
                })}
            </div>
        </DashboardLayout>
    );
}