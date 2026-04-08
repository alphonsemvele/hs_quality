import { Link } from '@inertiajs/react';
import DashboardLayout from './layout';

// ─── Types ────────────────────────────────────────────────────────────────────

interface Stats {
    interventions_ce_mois:       number;
    interventions_en_cours:      number;
    incidents_declares:          number;
    incidents_en_cours:          number;
    score_conformite:            number;
    taux_completion_pac:         number;
    score_qvct_moyen:            number;
    formations_expirant_bientot: number;
    intervenants_actifs:         number;
    structures_actives:          number;
}

interface IncidentRecent {
    id:          number;
    initials:    string;
    declarant:   string;
    categorie:   string;
    gravite:     'mineur' | 'significatif' | 'grave' | 'critique';
    statut:      'declare' | 'en_analyse' | 'plan_actions' | 'clos';
    structure:   string;
    depuis:      string;
}

interface AlerteQvct {
    id:          number;
    intervenant: string;
    structure:   string;
    score:       number;
    signal:      string;
    depuis:      string;
}

interface AuditRecent {
    id:          number;
    structure:   string;
    type_grille: string;
    score:       number;
    statut:      'planifie' | 'en_cours' | 'finalise';
    date:        string;
}

interface Props {
    stats:            Stats;
    incidents_recents: IncidentRecent[];
    alertes_qvct:     AlerteQvct[];
    audits_recents:   AuditRecent[];
}

// ─── Defaults ─────────────────────────────────────────────────────────────────

const DEFAULT_STATS: Stats = {
    interventions_ce_mois:       1842,
    interventions_en_cours:      23,
    incidents_declares:          14,
    incidents_en_cours:          3,
    score_conformite:            78,
    taux_completion_pac:         62,
    score_qvct_moyen:            6.8,
    formations_expirant_bientot: 5,
    intervenants_actifs:         87,
    structures_actives:          12,
};

const DEFAULT_INCIDENTS: IncidentRecent[] = [
    { id: 1, initials: 'ME', declarant: 'Marie Essomba',    categorie: 'Chute',                gravite: 'significatif', statut: 'en_analyse',   structure: 'SAAD Horizon Douala',   depuis: '2h' },
    { id: 2, initials: 'JK', declarant: 'Jean Koffi',       categorie: 'Erreur médicamenteuse', gravite: 'grave',        statut: 'plan_actions', structure: 'SSIAD Centre Yaoundé',   depuis: '5h' },
    { id: 3, initials: 'AF', declarant: 'Amina Fofana',     categorie: 'Agression',             gravite: 'critique',     statut: 'declare',      structure: 'SPASAD Nord',            depuis: '18 min' },
    { id: 4, initials: 'PB', declarant: 'Paul Biya Jr.',    categorie: 'Maltraitance suspectée', gravite: 'grave',       statut: 'en_analyse',   structure: 'SAAD Sud Littoral',      depuis: '1j' },
    { id: 5, initials: 'FN', declarant: 'Fatima Ndiaye',    categorie: 'Chute',                gravite: 'mineur',       statut: 'clos',         structure: 'SAAD Horizon Douala',   depuis: '3j' },
    { id: 6, initials: 'CT', declarant: 'Clément Touré',    categorie: 'Accident de travail',   gravite: 'significatif', statut: 'plan_actions', structure: 'SSIAD Centre Yaoundé',   depuis: '2j' },
];

const DEFAULT_ALERTES_QVCT: AlerteQvct[] = [
    { id: 1, intervenant: 'Sophie Ateba',   structure: 'SAAD Horizon Douala',  score: 2.8, signal: 'Score bas 2 périodes consécutives', depuis: '7 jours' },
    { id: 2, intervenant: 'Bruno Ngono',    structure: 'SSIAD Centre Yaoundé', score: 3.1, signal: 'Surcharge de travail détectée',      depuis: '3 jours' },
    { id: 3, intervenant: 'Pascaline Eko',  structure: 'SPASAD Nord',          score: 3.4, signal: 'Isolement professionnel signalé',    depuis: '5 jours' },
];

const DEFAULT_AUDITS: AuditRecent[] = [
    { id: 1, structure: 'SAAD Horizon Douala',   type_grille: 'HAS Évaluation externe', score: 84, statut: 'finalise',  date: '05/04/2026' },
    { id: 2, structure: 'SSIAD Centre Yaoundé',  type_grille: 'AFNOR NF X50-056',       score: 71, statut: 'en_cours',  date: '08/04/2026' },
    { id: 3, structure: 'SPASAD Nord',           type_grille: 'ISO 9001',               score: 0,  statut: 'planifie',  date: '15/04/2026' },
    { id: 4, structure: 'SAAD Sud Littoral',     type_grille: 'Caphandeo',              score: 91, statut: 'finalise',  date: '02/04/2026' },
];

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function Dashboard({
    stats             = DEFAULT_STATS,
    incidents_recents = DEFAULT_INCIDENTS,
    alertes_qvct      = DEFAULT_ALERTES_QVCT,
    audits_recents    = DEFAULT_AUDITS,
}: Partial<Props>) {

    const s = stats ?? DEFAULT_STATS;

    const incidentsGraves = incidents_recents.filter(i => i.gravite === 'grave' || i.gravite === 'critique');

    return (
        <DashboardLayout
            title="Tableau de bord"
            subtitle="Pilotage qualité & QVCT — vue consolidée"
        >
            {/* ── BANNER INCIDENTS GRAVES ── */}
            {incidentsGraves.length > 0 && (
                <div style={{
                    marginBottom: 20, borderRadius: 14,
                    background: '#FFF5F5', border: '1px solid #FECACA',
                    padding: '14px 18px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16,
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        <div style={{ width: 36, height: 36, borderRadius: 10, background: '#EF4444', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, animation: 'pulse 2s infinite' }}>
                            <svg width={18} height={18} fill="none" stroke="white" strokeWidth={2} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p style={{ fontSize: 13, fontWeight: 700, color: '#DC2626' }}>
                                {incidentsGraves.length} incident{incidentsGraves.length > 1 ? 's' : ''} grave{incidentsGraves.length > 1 ? 's' : ''} / critique{incidentsGraves.length > 1 ? 's' : ''} en cours
                            </p>
                            <p style={{ fontSize: 12, color: '#EF4444', marginTop: 1 }}>
                                {incidentsGraves.map(i => i.declarant).join(' · ')}
                            </p>
                        </div>
                    </div>
                    <Link href="/incidents" style={{
                        fontSize: 12, fontWeight: 700, color: 'white',
                        background: '#EF4444', padding: '8px 16px', borderRadius: 9,
                        textDecoration: 'none', flexShrink: 0, transition: 'opacity .15s',
                    }}
                        onMouseEnter={e => (e.currentTarget.style.opacity = '.85')}
                        onMouseLeave={e => (e.currentTarget.style.opacity = '1')}
                    >Traiter →</Link>
                </div>
            )}

            {/* ── KPI CARDS ── */}
            <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-5">
                <KpiCard label="Interventions ce mois"  value={s.interventions_ce_mois.toLocaleString('fr-FR')} sub={`${s.interventions_en_cours} en cours`}   subGold icon={<ClipboardIcon />} accent="navy" />
                <KpiCard label="Incidents déclarés"      value={s.incidents_declares}                            sub={`${s.incidents_en_cours} non clôturés`}      subAlert icon={<AlertIcon />}    accent="red" />
                <KpiCard label="Score conformité"        value={`${s.score_conformite}%`}                        sub="vs cible 85%"                                 icon={<BadgeIcon />}    accent="gold" progress={s.score_conformite} />
                <KpiCard label="Taux complétion PAC"     value={`${s.taux_completion_pac}%`}                     sub="cible > 60%"                                  subGold={s.taux_completion_pac >= 60} icon={<CheckListIcon />} accent="teal" progress={s.taux_completion_pac} />
                <KpiCard label="Score QVCT moyen"        value={`${s.score_qvct_moyen}/10`}                      sub={`${alertes_qvct.length} alertes actives`}     subAlert={alertes_qvct.length > 0} icon={<HeartIcon />} accent="violet" progress={s.score_qvct_moyen * 10} />
                <KpiCard label="Intervenants actifs"     value={s.intervenants_actifs}                           sub={`${s.formations_expirant_bientot} formations expirent bientôt`} subAlert={s.formations_expirant_bientot > 0} icon={<UsersIcon />} accent="sky" />
            </div>

            {/* ── BODY ── */}
            <div className="grid grid-cols-1 xl:grid-cols-5 gap-4">

                {/* Incidents récents — 3 cols */}
                <div className="xl:col-span-3" style={{ background: 'white', borderRadius: 16, border: '1px solid #F1F5F9', overflow: 'hidden' }}>
                    <SectionHeader title="Incidents récents" count={incidents_recents.length} link="/incidents" linkLabel="Voir tout" />
                    {incidents_recents.length > 0 ? (
                        <div>
                            {incidents_recents.map(inc => (
                                <IncidentRow key={inc.id} {...inc} />
                            ))}
                        </div>
                    ) : (
                        <EmptyState icon={<AlertIcon />} text="Aucun incident déclaré" sub="Les incidents apparaîtront ici" />
                    )}
                </div>

                {/* Colonne droite — 2 cols */}
                <div className="xl:col-span-2 flex flex-col gap-4">

                    {/* Alertes QVCT */}
                    <div style={{ background: 'white', borderRadius: 16, border: '1px solid #F1F5F9', overflow: 'hidden' }}>
                        <SectionHeader title="Alertes QVCT" count={alertes_qvct.length} link="/qvct" linkLabel="Gérer →" danger />
                        {alertes_qvct.length > 0 ? (
                            <div>
                                {alertes_qvct.map(a => (
                                    <AlerteQvctRow key={a.id} {...a} />
                                ))}
                            </div>
                        ) : (
                            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', padding: '28px 16px', textAlign: 'center' }}>
                                <div style={{ width: 36, height: 36, borderRadius: 10, background: '#F0FDF4', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: 8 }}>
                                    <svg width={18} height={18} fill="none" stroke="#16A34A" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p style={{ fontSize: 12, fontWeight: 600, color: '#64748B' }}>Aucune alerte QVCT active</p>
                            </div>
                        )}
                    </div>

                    {/* Audits récents */}
                    <div style={{ background: 'white', borderRadius: 16, border: '1px solid #F1F5F9', overflow: 'hidden' }}>
                        <SectionHeader title="Audits récents" count={audits_recents.length} link="/audits" linkLabel="Voir tout" />
                        <div>
                            {audits_recents.map(a => (
                                <AuditRow key={a.id} {...a} />
                            ))}
                        </div>
                    </div>

                    {/* Actions rapides */}
                    <div style={{ background: 'white', borderRadius: 16, border: '1px solid #F1F5F9', overflow: 'hidden' }}>
                        <div style={{ padding: '14px 18px', borderBottom: '1px solid #F8FAFC' }}>
                            <h2 style={{ fontSize: 13, fontWeight: 700, color: 'var(--navy)' }}>Actions rapides</h2>
                        </div>
                        <div style={{ padding: 8, display: 'flex', flexDirection: 'column', gap: 2 }}>
                            <QuickAction href="/incidents/create"    icon={<AlertIcon />}     label="Déclarer un incident"          color={{ bg: '#FFF5F5', text: '#EF4444' }} />
                            <QuickAction href="/audits/create"       icon={<BadgeIcon />}     label="Lancer un audit"               color={{ bg: '#F0FDF4', text: '#16A34A' }} />
                            <QuickAction href="/qvct/questionnaire"  icon={<HeartIcon />}     label="Envoyer questionnaire QVCT"    color={{ bg: '#F5F3FF', text: '#7C3AED' }} />
                            <QuickAction href="/formations"          icon={<AcademicIcon />}  label="Gérer les formations"          color={{ bg: '#EFF6FF', text: '#2563EB' }} />
                            <QuickAction href="/indicateurs"         icon={<ChartIcon />}     label="Tableaux de bord KPIs"         color={{ bg: '#FFFBEB', text: 'var(--gold)' }} />
                            <QuickAction href="/rapports"            icon={<DocumentIcon />}  label="Générer un rapport"            color={{ bg: '#F8FAFC', text: '#475569' }} />
                        </div>
                    </div>
                </div>
            </div>
        </DashboardLayout>
    );
}

// ─── Section Header ───────────────────────────────────────────────────────────

function SectionHeader({ title, count, link, linkLabel, danger = false }: {
    title: string; count: number; link: string; linkLabel: string; danger?: boolean;
}) {
    return (
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '14px 18px', borderBottom: '1px solid #F8FAFC' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                {danger && <span style={{ width: 7, height: 7, borderRadius: '50%', background: '#EF4444', animation: 'pulse 2s infinite' }} />}
                <h2 style={{ fontSize: 13, fontWeight: 700, color: 'var(--navy)' }}>{title}</h2>
                <span style={{ fontSize: 11, fontWeight: 600, color: '#94A3B8', background: '#F8FAFC', border: '1px solid #F1F5F9', padding: '1px 7px', borderRadius: 20 }}>{count}</span>
            </div>
            <Link href={link} style={{ fontSize: 12, fontWeight: 600, color: danger ? '#EF4444' : 'var(--gold)', textDecoration: 'none', display: 'flex', alignItems: 'center', gap: 4, transition: 'opacity .15s' }}
                onMouseEnter={e => (e.currentTarget.style.opacity = '.7')}
                onMouseLeave={e => (e.currentTarget.style.opacity = '1')}
            >
                {linkLabel}
                {!danger && <svg width={12} height={12} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7"/></svg>}
            </Link>
        </div>
    );
}

// ─── Incident Row ─────────────────────────────────────────────────────────────

const GRAVITE_STYLE: Record<string, { bg: string; text: string; dot: string }> = {
    mineur:       { bg: '#F8FAFC', text: '#64748B', dot: '#94A3B8' },
    significatif: { bg: '#FFFBEB', text: '#92400E', dot: '#F59E0B' },
    grave:        { bg: '#FFF7ED', text: '#C2410C', dot: '#F97316' },
    critique:     { bg: '#FFF5F5', text: '#DC2626', dot: '#EF4444' },
};

const STATUT_LABEL: Record<string, string> = {
    declare:      'Déclaré',
    en_analyse:   'En analyse',
    plan_actions: 'Plan d\'actions',
    clos:         'Clos',
};

const STATUT_STYLE: Record<string, { bg: string; text: string }> = {
    declare:      { bg: '#FEF2F2', text: '#DC2626' },
    en_analyse:   { bg: '#FFFBEB', text: '#92400E' },
    plan_actions: { bg: '#EFF6FF', text: '#1D4ED8' },
    clos:         { bg: '#F1F5F9', text: '#64748B' },
};

function IncidentRow({ initials, declarant, categorie, gravite, statut, structure, depuis }: Omit<IncidentRecent, 'id'>) {
    const g = GRAVITE_STYLE[gravite];
    const st = STATUT_STYLE[statut];
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '11px 18px', borderBottom: '1px solid #F8FAFC', transition: 'background .15s', cursor: 'default' }}
            onMouseEnter={e => (e.currentTarget as HTMLDivElement).style.background = '#FAFAFA'}
            onMouseLeave={e => (e.currentTarget as HTMLDivElement).style.background = 'transparent'}
        >
            <div style={{ width: 34, height: 34, borderRadius: 10, background: g.bg, display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, fontWeight: 800, color: g.text, flexShrink: 0 }}>
                {initials}
            </div>
            <div style={{ flex: 1, minWidth: 0 }}>
                <p style={{ fontSize: 13, fontWeight: 600, color: 'var(--navy)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{declarant}</p>
                <p style={{ fontSize: 11, color: '#94A3B8', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{categorie} · {structure}</p>
            </div>
            <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4, flexShrink: 0 }}>
                <span style={{ display: 'inline-flex', alignItems: 'center', gap: 5, fontSize: 11, fontWeight: 600, background: g.bg, color: g.text, padding: '3px 8px', borderRadius: 20 }}>
                    <span style={{ width: 5, height: 5, borderRadius: '50%', background: g.dot }} />
                    {gravite.charAt(0).toUpperCase() + gravite.slice(1)}
                </span>
                <span style={{ fontSize: 11, fontWeight: 600, background: st.bg, color: st.text, padding: '2px 7px', borderRadius: 20 }}>
                    {STATUT_LABEL[statut]}
                </span>
            </div>
            <div style={{ fontSize: 10, color: '#CBD5E1', flexShrink: 0, minWidth: 32, textAlign: 'right' }}>{depuis}</div>
        </div>
    );
}

// ─── Alerte QVCT Row ──────────────────────────────────────────────────────────

function AlerteQvctRow({ intervenant, structure, score, signal, depuis }: Omit<AlerteQvct, 'id'>) {
    const scoreColor = score < 3 ? '#EF4444' : score < 4 ? '#F97316' : 'var(--gold)';
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '11px 16px', borderBottom: '1px solid #F8FAFC', transition: 'background .15s' }}
            onMouseEnter={e => (e.currentTarget as HTMLDivElement).style.background = '#FAFAFA'}
            onMouseLeave={e => (e.currentTarget as HTMLDivElement).style.background = 'transparent'}
        >
            <div style={{ width: 32, height: 32, borderRadius: 9, background: '#FFF5F5', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                <svg width={16} height={16} fill="none" stroke="#EF4444" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
            </div>
            <div style={{ flex: 1, minWidth: 0 }}>
                <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--navy)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{intervenant}</p>
                <p style={{ fontSize: 11, color: '#94A3B8', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{signal}</p>
            </div>
            <div style={{ textAlign: 'right', flexShrink: 0 }}>
                <span style={{ fontSize: 14, fontWeight: 800, color: scoreColor, fontFamily: "'DM Mono', monospace" }}>{score}/10</span>
                <p style={{ fontSize: 10, color: '#CBD5E1', marginTop: 1 }}>il y a {depuis}</p>
            </div>
        </div>
    );
}

// ─── Audit Row ────────────────────────────────────────────────────────────────

const AUDIT_STATUT: Record<string, { label: string; bg: string; text: string }> = {
    planifie:  { label: 'Planifié',   bg: '#F8FAFC', text: '#64748B' },
    en_cours:  { label: 'En cours',   bg: '#FFFBEB', text: '#92400E' },
    finalise:  { label: 'Finalisé',   bg: '#F0FDF4', text: '#166534' },
};

function AuditRow({ structure, type_grille, score, statut, date }: Omit<AuditRecent, 'id'>) {
    const st = AUDIT_STATUT[statut];
    const scoreColor = score >= 85 ? '#16A34A' : score >= 70 ? 'var(--gold)' : '#EF4444';
    return (
        <div style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '10px 18px', borderBottom: '1px solid #F8FAFC', transition: 'background .15s' }}
            onMouseEnter={e => (e.currentTarget as HTMLDivElement).style.background = '#FAFAFA'}
            onMouseLeave={e => (e.currentTarget as HTMLDivElement).style.background = 'transparent'}
        >
            <div style={{ flex: 1, minWidth: 0 }}>
                <p style={{ fontSize: 12, fontWeight: 600, color: 'var(--navy)', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{structure}</p>
                <p style={{ fontSize: 11, color: '#94A3B8' }}>{type_grille} · {date}</p>
            </div>
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
                {score > 0 && <span style={{ fontSize: 13, fontWeight: 800, color: scoreColor, fontFamily: "'DM Mono', monospace" }}>{score}%</span>}
                <span style={{ fontSize: 11, fontWeight: 600, background: st.bg, color: st.text, padding: '3px 8px', borderRadius: 20 }}>{st.label}</span>
            </div>
        </div>
    );
}

// ─── Quick Action ─────────────────────────────────────────────────────────────

function QuickAction({ href, icon, label, color }: {
    href: string; icon: React.ReactNode; label: string; color: { bg: string; text: string };
}) {
    return (
        <Link href={href} style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '9px 10px', borderRadius: 10, textDecoration: 'none', transition: 'background .15s' }}
            onMouseEnter={e => (e.currentTarget as HTMLAnchorElement).style.background = '#F8FAFC'}
            onMouseLeave={e => (e.currentTarget as HTMLAnchorElement).style.background = 'transparent'}
        >
            <div style={{ width: 30, height: 30, borderRadius: 8, background: color.bg, color: color.text, display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                {icon}
            </div>
            <span style={{ fontSize: 13, fontWeight: 500, color: '#475569', flex: 1 }}>{label}</span>
            <svg width={14} height={14} fill="none" stroke="#CBD5E1" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7"/></svg>
        </Link>
    );
}

// ─── Empty State ──────────────────────────────────────────────────────────────

function EmptyState({ icon, text, sub }: { icon: React.ReactNode; text: string; sub: string }) {
    return (
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', padding: '48px 24px', textAlign: 'center' }}>
            <div style={{ width: 44, height: 44, borderRadius: 12, background: '#F8FAFC', display: 'flex', alignItems: 'center', justifyContent: 'center', marginBottom: 12, color: '#CBD5E1' }}>{icon}</div>
            <p style={{ fontSize: 13, fontWeight: 600, color: '#64748B', marginBottom: 4 }}>{text}</p>
            <p style={{ fontSize: 12, color: '#94A3B8' }}>{sub}</p>
        </div>
    );
}

// ─── KPI Card ─────────────────────────────────────────────────────────────────

const ACCENT: Record<string, { icon_bg: string; icon_text: string; bar: string }> = {
    navy:   { icon_bg: '#EFF6FF', icon_text: 'var(--navy)', bar: 'var(--navy)'  },
    red:    { icon_bg: '#FEF2F2', icon_text: '#EF4444',     bar: '#EF4444'      },
    gold:   { icon_bg: '#FFFBEB', icon_text: 'var(--gold)', bar: 'var(--gold)'  },
    teal:   { icon_bg: '#F0FDFA', icon_text: '#0D9488',     bar: '#0D9488'      },
    violet: { icon_bg: '#F5F3FF', icon_text: '#7C3AED',     bar: '#7C3AED'      },
    sky:    { icon_bg: '#F0F9FF', icon_text: '#0284C7',     bar: '#0284C7'      },
};

function KpiCard({ label, value, sub, subGold = false, subAlert = false, icon, accent = 'navy', progress }: {
    label: string; value: string | number; sub: string;
    subGold?: boolean; subAlert?: boolean;
    icon: React.ReactNode; accent?: string; progress?: number;
}) {
    const a = ACCENT[accent] ?? ACCENT.navy;
    return (
        <div style={{ background: 'white', borderRadius: 14, border: '1px solid #F1F5F9', padding: '18px 18px 16px', transition: 'box-shadow .2s' }}
            onMouseEnter={e => (e.currentTarget as HTMLDivElement).style.boxShadow = '0 4px 20px rgba(11,22,40,.06)'}
            onMouseLeave={e => (e.currentTarget as HTMLDivElement).style.boxShadow = 'none'}
        >
            <div style={{ display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', marginBottom: 12 }}>
                <div style={{ width: 36, height: 36, borderRadius: 10, background: a.icon_bg, display: 'flex', alignItems: 'center', justifyContent: 'center', color: a.icon_text }}>
                    {icon}
                </div>
                {progress !== undefined && (
                    <span style={{ fontSize: 11, fontWeight: 700, background: a.icon_bg, color: a.icon_text, padding: '3px 8px', borderRadius: 20 }}>{progress}%</span>
                )}
            </div>
            <p style={{ fontSize: 11, fontWeight: 500, color: '#94A3B8', marginBottom: 4 }}>{label}</p>
            <p style={{ fontSize: 22, fontWeight: 800, color: 'var(--navy)', letterSpacing: '-0.02em', fontFamily: "'DM Mono', monospace" }}>{value}</p>
            {progress !== undefined && (
                <div style={{ margin: '10px 0 4px', height: 3, borderRadius: 3, background: '#F1F5F9', overflow: 'hidden' }}>
                    <div style={{ height: '100%', width: `${Math.min(progress, 100)}%`, background: a.bar, borderRadius: 3 }} />
                </div>
            )}
            <p style={{ marginTop: 6, fontSize: 11, fontWeight: 600, color: subAlert ? '#EF4444' : subGold ? 'var(--gold)' : '#94A3B8' }}>{sub}</p>
        </div>
    );
}

// ─── Icons ────────────────────────────────────────────────────────────────────
function ClipboardIcon() { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>; }
function AlertIcon()     { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>; }
function BadgeIcon()     { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>; }
function CheckListIcon() { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>; }
function HeartIcon()     { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>; }
function UsersIcon()     { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>; }
function ChartIcon()     { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>; }
function AcademicIcon()  { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>; }
function DocumentIcon()  { return <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>; }