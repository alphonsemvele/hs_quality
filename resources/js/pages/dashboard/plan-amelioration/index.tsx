import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface PAC {
    id: number;
    titre: string;
    structure: string;
    responsable: string;
    priorite: 'basse' | 'moyenne' | 'haute' | 'critique';
    echeance: string;
    avancement_pct: number;
    statut: 'ouvert' | 'en_cours' | 'realise' | 'verifie' | 'clos';
    source: 'audit' | 'incident' | 'manuel';
}

const DEFAULT: PAC[] = [
    { id: 1, titre: 'Mise en place protocole anti-chute',          structure: 'SAAD Horizon Douala',  responsable: 'Sophie Ateba',   priorite: 'haute',    echeance: '30/04/2026', avancement_pct: 60, statut: 'en_cours', source: 'incident' },
    { id: 2, titre: 'Formation gestion médicamenteuse',            structure: 'SSIAD Centre Yaoundé', responsable: 'Bruno Ngono',    priorite: 'critique', echeance: '15/04/2026', avancement_pct: 30, statut: 'en_cours', source: 'audit'    },
    { id: 3, titre: 'Révision du plan d\'accompagnement type',     structure: 'SPASAD Nord',          responsable: 'Pascaline Eko',  priorite: 'moyenne',  echeance: '31/05/2026', avancement_pct: 10, statut: 'ouvert',   source: 'audit'    },
    { id: 4, titre: 'Audit interne trimestriel automatisé',        structure: 'SAAD Sud Littoral',    responsable: 'Jean Koffi',     priorite: 'basse',    echeance: '30/06/2026', avancement_pct: 0,  statut: 'ouvert',   source: 'manuel'   },
    { id: 5, titre: 'Déploiement mode offline sur smartphones',    structure: 'Toutes structures',    responsable: 'Amina Fofana',   priorite: 'haute',    echeance: '20/04/2026', avancement_pct: 85, statut: 'en_cours', source: 'manuel'   },
    { id: 6, titre: 'Renouvellement certifications DEAS',          structure: 'SSIAD Centre Yaoundé', responsable: 'Bruno Ngono',    priorite: 'haute',    echeance: '01/05/2026', avancement_pct: 100, statut: 'realise', source: 'audit'   },
];

const PRIO = {
    basse:    { bg: '#F8FAFC', text: '#64748B' },
    moyenne:  { bg: '#FFFBEB', text: '#92400E' },
    haute:    { bg: '#FFF7ED', text: '#C2410C' },
    critique: { bg: '#FEF2F2', text: '#DC2626' },
};

const STATUT = {
    ouvert:   { label: 'Ouvert',   bg: '#F1F5F9', text: '#475569' },
    en_cours: { label: 'En cours', bg: '#EFF6FF', text: '#1D4ED8' },
    realise:  { label: 'Réalisé',  bg: '#F0FDF4', text: '#166534' },
    verifie:  { label: 'Vérifié',  bg: '#F0FDF4', text: '#065F46' },
    clos:     { label: 'Clos',     bg: '#F8FAFC', text: '#94A3B8' },
};

const SOURCE = {
    audit:    { label: 'Audit',    color: '#3B82F6' },
    incident: { label: 'Incident', color: '#EF4444' },
    manuel:   { label: 'Manuel',   color: '#94A3B8' },
};

export default function PlansAmelioration({ pacs = DEFAULT }: { pacs?: PAC[] }) {
    const done = pacs.filter(p => p.statut === 'realise' || p.statut === 'clos' || p.statut === 'verifie').length;
    const taux = Math.round(done / pacs.length * 100);

    return (
        <DashboardLayout title="Plans d'amélioration continue" subtitle="PAC issus des audits et des incidents">

            {/* Taux global */}
            <div style={{ background: 'var(--navy)', borderRadius: 14, padding: '20px 24px', marginBottom: 20, display: 'flex', alignItems: 'center', gap: 24 }}>
                <div>
                    <p style={{ fontSize: 11, color: 'rgba(255,255,255,.4)', fontWeight: 600, textTransform: 'uppercase', letterSpacing: '.08em' }}>Taux de complétion global</p>
                    <p style={{ fontSize: 36, fontWeight: 800, color: 'var(--gold)', fontFamily: "'DM Mono', monospace", lineHeight: 1.1 }}>{taux}%</p>
                </div>
                <div style={{ flex: 1 }}>
                    <div style={{ height: 8, background: 'rgba(255,255,255,.1)', borderRadius: 4, overflow: 'hidden' }}>
                        <div style={{ width: `${taux}%`, height: '100%', background: 'var(--gold)', borderRadius: 4 }} />
                    </div>
                    <p style={{ fontSize: 12, color: 'rgba(255,255,255,.4)', marginTop: 8 }}>{done} / {pacs.length} actions complétées</p>
                </div>
                <Link href="/plans-amelioration/create" style={{
                    background: 'var(--gold)', color: 'var(--navy)',
                    fontSize: 13, fontWeight: 700, padding: '10px 20px', borderRadius: 10, textDecoration: 'none', flexShrink: 0,
                }}>+ Nouvelle action</Link>
            </div>

            {/* Liste */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                {pacs.map(p => {
                    const pr = PRIO[p.priorite];
                    const st = STATUT[p.statut];
                    const src = SOURCE[p.source];
                    return (
                        <div key={p.id} style={{ background: 'white', borderRadius: 14, border: '1px solid #F1F5F9', padding: '16px 20px' }}>
                            <div style={{ display: 'flex', alignItems: 'flex-start', gap: 14 }}>
                                <div style={{ flex: 1, minWidth: 0 }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 6, flexWrap: 'wrap' }}>
                                        <span style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)' }}>{p.titre}</span>
                                        <span style={{ fontSize: 11, fontWeight: 700, background: pr.bg, color: pr.text, padding: '2px 8px', borderRadius: 20 }}>{p.priorite}</span>
                                        <span style={{ fontSize: 11, fontWeight: 600, background: st.bg, color: st.text, padding: '2px 8px', borderRadius: 20 }}>{st.label}</span>
                                        <span style={{ fontSize: 10, color: src.color, background: src.color + '15', padding: '2px 7px', borderRadius: 6, fontWeight: 600 }}>{src.label}</span>
                                    </div>
                                    <div style={{ display: 'flex', gap: 16, fontSize: 12, color: '#94A3B8', marginBottom: 10 }}>
                                        <span>{p.structure}</span>
                                        <span>Resp. {p.responsable}</span>
                                        <span style={{ fontFamily: "'DM Mono', monospace" }}>Échéance : {p.echeance}</span>
                                    </div>
                                    {/* Progress */}
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                                        <div style={{ flex: 1, height: 5, background: '#F1F5F9', borderRadius: 3, overflow: 'hidden' }}>
                                            <div style={{
                                                width: `${p.avancement_pct}%`, height: '100%', borderRadius: 3,
                                                background: p.avancement_pct === 100 ? '#16A34A' : p.avancement_pct >= 50 ? 'var(--gold)' : '#94A3B8',
                                            }} />
                                        </div>
                                        <span style={{ fontSize: 12, fontWeight: 700, color: '#475569', fontFamily: "'DM Mono', monospace", minWidth: 36 }}>{p.avancement_pct}%</span>
                                    </div>
                                </div>
                                <Link href={`/plans-amelioration/${p.id}`} style={{ fontSize: 12, color: 'var(--gold)', textDecoration: 'none', fontWeight: 600, flexShrink: 0 }}>Gérer →</Link>
                            </div>
                        </div>
                    );
                })}
            </div>
        </DashboardLayout>
    );
}