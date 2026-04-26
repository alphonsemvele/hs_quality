import { Link } from '@inertiajs/react';
import DashboardLayout from '../layout';

interface Incident {
    id: number;
    initials: string;
    declarant: string;
    categorie: string;
    gravite: 'mineur' | 'significatif' | 'grave' | 'critique';
    statut: 'declare' | 'en_analyse' | 'plan_actions' | 'clos';
    structure: string;
    date_heure: string;
    description: string;
    notifie_responsable: boolean;
    notifie_autorites: boolean;
}

interface Props {
    incidents: Incident[];
    stats: { declare: number; en_analyse: number; plan_actions: number; clos: number; graves: number };
}

// Fallback removed (Wave 1 / C1) — previously contained named individuals
// that would render even when the controller passed empty data, leaking
// fictional-but-realistic personal data to every viewer regardless of tenant.

const GRAVITE = {
    mineur:       { bg: '#F8FAFC', text: '#475569', dot: '#94A3B8', border: '#E2E8F0' },
    significatif: { bg: '#FFFBEB', text: '#92400E', dot: '#F59E0B', border: '#FDE68A' },
    grave:        { bg: '#FFF7ED', text: '#C2410C', dot: '#F97316', border: '#FED7AA' },
    critique:     { bg: '#FEF2F2', text: '#DC2626', dot: '#EF4444', border: '#FECACA' },
};

const STATUT = {
    declare:      { label: 'Déclaré',        bg: '#FEF2F2', text: '#DC2626' },
    en_analyse:   { label: 'En analyse',      bg: '#FFFBEB', text: '#92400E' },
    plan_actions: { label: "Plan d'actions",  bg: '#EFF6FF', text: '#1D4ED8' },
    clos:         { label: 'Clos',            bg: '#F1F5F9', text: '#64748B' },
};

export default function Incidents({ incidents = [], stats = { declare: 0, en_analyse: 0, plan_actions: 0, clos: 0, graves: 0 } }: Partial<Props>) {
    return (
        <DashboardLayout title="Incidents & Événements indésirables" subtitle="Déclaration, analyse et suivi des incidents">

            {/* KPIs */}
            <div className="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5">
                {[
                    { label: 'Déclarés',     v: stats.declare,      color: '#EF4444' },
                    { label: 'En analyse',   v: stats.en_analyse,   color: '#F59E0B' },
                    { label: "Plan d'act.",  v: stats.plan_actions, color: '#3B82F6' },
                    { label: 'Clos',         v: stats.clos,         color: '#94A3B8' },
                    { label: 'Graves/Crit.', v: stats.graves,       color: '#DC2626' },
                ].map(k => (
                    <div key={k.label} style={{ background: 'white', borderRadius: 12, border: '1px solid #F1F5F9', padding: '14px 16px' }}>
                        <div style={{ fontSize: 22, fontWeight: 800, color: k.color, fontFamily: "'DM Mono', monospace" }}>{k.v}</div>
                        <div style={{ fontSize: 11, color: '#94A3B8', marginTop: 3, fontWeight: 500 }}>{k.label}</div>
                    </div>
                ))}
            </div>

            {/* Actions */}
            <div className="flex items-center justify-between mb-4">
                <h2 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)' }}>Liste des incidents ({incidents.length})</h2>
                <Link href="/incidents/create" style={{
                    display: 'inline-flex', alignItems: 'center', gap: 6,
                    background: '#EF4444', color: 'white',
                    fontSize: 13, fontWeight: 700, padding: '9px 18px', borderRadius: 10, textDecoration: 'none',
                }}>
                    <svg width={14} height={14} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M12 5v14M5 12h14"/></svg>
                    Déclarer un incident
                </Link>
            </div>

            {/* Cards */}
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                {incidents.map(inc => {
                    const g = GRAVITE[inc.gravite];
                    const s = STATUT[inc.statut];
                    return (
                        <div key={inc.id} style={{ background: 'white', borderRadius: 14, border: `1px solid ${g.border}`, padding: '16px 20px', display: 'flex', alignItems: 'flex-start', gap: 16 }}>
                            {/* Gravité dot */}
                            <div style={{ width: 40, height: 40, borderRadius: 10, background: g.bg, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, fontSize: 11, fontWeight: 800, color: g.text }}>
                                {inc.initials}
                            </div>
                            <div style={{ flex: 1, minWidth: 0 }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap', marginBottom: 4 }}>
                                    <span style={{ fontSize: 13, fontWeight: 700, color: 'var(--navy)' }}>{inc.categorie}</span>
                                    <span style={{ fontSize: 11, fontWeight: 600, background: g.bg, color: g.text, padding: '2px 8px', borderRadius: 20, display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                                        <span style={{ width: 5, height: 5, borderRadius: '50%', background: g.dot }} />
                                        {inc.gravite.charAt(0).toUpperCase() + inc.gravite.slice(1)}
                                    </span>
                                    <span style={{ fontSize: 11, fontWeight: 600, background: s.bg, color: s.text, padding: '2px 8px', borderRadius: 20 }}>{s.label}</span>
                                    {inc.notifie_autorites && <span style={{ fontSize: 10, background: '#FEF2F2', color: '#DC2626', border: '1px solid #FECACA', padding: '2px 7px', borderRadius: 6, fontWeight: 700 }}>ARS notifiée</span>}
                                </div>
                                <p style={{ fontSize: 12, color: '#64748B', marginBottom: 6, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{inc.description}</p>
                                <div style={{ display: 'flex', gap: 16, fontSize: 11, color: '#94A3B8' }}>
                                    <span>Par {inc.declarant}</span>
                                    <span>{inc.structure}</span>
                                    <span style={{ fontFamily: "'DM Mono', monospace" }}>{inc.date_heure}</span>
                                </div>
                            </div>
                            <Link href={`/incidents/${inc.id}`} style={{ fontSize: 12, color: 'var(--gold)', textDecoration: 'none', fontWeight: 600, flexShrink: 0 }}>Traiter →</Link>
                        </div>
                    );
                })}
            </div>
        </DashboardLayout>
    );
}