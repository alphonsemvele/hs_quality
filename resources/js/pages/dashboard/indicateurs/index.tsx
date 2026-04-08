import DashboardLayout from '../layout';

interface KpiData { label: string; value: string | number; cible: string; tendance: 'up' | 'down' | 'stable'; ecart: string; ok: boolean; }
interface EvolutionPoint { mois: string; valeur: number; }

const KPIS: KpiData[] = [
    { label: 'Taux d\'incidents déclarés',    value: '7.6‰',  cible: '< 10‰',   tendance: 'up',    ecart: '−2.4‰',  ok: true  },
    { label: 'Délai moyen traitement incident',value: '4.2j',  cible: '< 5j',    tendance: 'up',    ecart: '−0.8j',  ok: true  },
    { label: 'Score conformité HAS moyen',    value: '78%',   cible: '> 85%',   tendance: 'up',    ecart: '+3%',    ok: false },
    { label: 'Taux complétion PAC',           value: '62%',   cible: '> 60%',   tendance: 'up',    ecart: '+2%',    ok: true  },
    { label: 'NPS utilisateurs',              value: '42',    cible: '> 40',    tendance: 'stable', ecart: '+2',    ok: true  },
    { label: 'Score QVCT moyen',             value: '6.8/10', cible: '> 7/10',  tendance: 'down',  ecart: '−0.3',   ok: false },
    { label: 'Taux absentéisme',             value: '4.1%',  cible: '< 5%',    tendance: 'up',    ecart: '−0.6%',  ok: true  },
    { label: 'Taux de formation',            value: '71%',   cible: '> 80%',   tendance: 'up',    ecart: '+5%',    ok: false },
];

const EVOLUTION: EvolutionPoint[] = [
    { mois: 'Oct', valeur: 68 },
    { mois: 'Nov', valeur: 71 },
    { mois: 'Déc', valeur: 70 },
    { mois: 'Jan', valeur: 73 },
    { mois: 'Fév', valeur: 75 },
    { mois: 'Mar', valeur: 78 },
];

function MiniSparkline({ data, color = 'var(--gold)' }: { data: number[]; color?: string }) {
    const max = Math.max(...data);
    const min = Math.min(...data);
    const range = max - min || 1;
    const W = 80; const H = 32;
    const points = data.map((v, i) => `${(i / (data.length - 1)) * W},${H - ((v - min) / range) * (H - 4) - 2}`).join(' ');
    return (
        <svg width={W} height={H} viewBox={`0 0 ${W} ${H}`}>
            <polyline points={points} fill="none" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
            <circle cx={(data.length - 1) / (data.length - 1) * W} cy={H - ((data[data.length - 1] - min) / range) * (H - 4) - 2} r={3} fill={color} />
        </svg>
    );
}

function TendanceIcon({ t }: { t: 'up' | 'down' | 'stable' }) {
    if (t === 'up')     return <svg width={14} height={14} fill="none" stroke="#16A34A" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M5 15l7-7 7 7"/></svg>;
    if (t === 'down')   return <svg width={14} height={14} fill="none" stroke="#EF4444" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7"/></svg>;
    return <svg width={14} height={14} fill="none" stroke="#94A3B8" strokeWidth={2.5} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14"/></svg>;
}

export default function Indicateurs() {
    const maxVal = Math.max(...EVOLUTION.map(e => e.valeur));
    const minVal = Math.min(...EVOLUTION.map(e => e.valeur));

    return (
        <DashboardLayout title="Indicateurs & KPIs" subtitle="Tableau de bord qualité — vue synthétique">

            {/* KPI grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
                {KPIS.map((k, i) => (
                    <div key={i} style={{ background: 'white', borderRadius: 14, border: `1px solid ${k.ok ? '#F1F5F9' : '#FEF2F2'}`, padding: '18px 18px 14px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 10 }}>
                            <p style={{ fontSize: 12, color: '#94A3B8', fontWeight: 500, flex: 1, paddingRight: 8 }}>{k.label}</p>
                            <TendanceIcon t={k.tendance} />
                        </div>
                        <p style={{ fontSize: 26, fontWeight: 800, color: k.ok ? 'var(--navy)' : '#EF4444', fontFamily: "'DM Mono', monospace", lineHeight: 1, marginBottom: 6 }}>{k.value}</p>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                            <span style={{ fontSize: 11, color: '#94A3B8' }}>Cible : {k.cible}</span>
                            <span style={{ fontSize: 11, fontWeight: 700, color: k.ok ? '#16A34A' : '#EF4444' }}>{k.ecart}</span>
                        </div>
                        <div style={{ marginTop: 10 }}>
                            <MiniSparkline data={[60, 65, 68, 70, 72, parseInt(String(k.value)) || 75]} color={k.ok ? 'var(--gold)' : '#EF4444'} />
                        </div>
                    </div>
                ))}
            </div>

            {/* Évolution score conformité */}
            <div style={{ background: 'white', borderRadius: 16, border: '1px solid #F1F5F9', padding: '24px 28px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
                    <div>
                        <h2 style={{ fontSize: 14, fontWeight: 700, color: 'var(--navy)' }}>Évolution score conformité</h2>
                        <p style={{ fontSize: 12, color: '#94A3B8', marginTop: 3 }}>6 derniers mois — cible 85%</p>
                    </div>
                    <div style={{ display: 'flex', gap: 16 }}>
                        {[{ color: 'var(--gold)', label: 'Score réel' }, { color: '#E2E8F0', label: 'Cible 85%' }].map(l => (
                            <div key={l.label} style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                                <div style={{ width: 20, height: 3, borderRadius: 2, background: l.color }} />
                                <span style={{ fontSize: 11, color: '#94A3B8' }}>{l.label}</span>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Chart bars */}
                <div style={{ display: 'flex', gap: 16, alignItems: 'flex-end', height: 160 }}>
                    {EVOLUTION.map((e, i) => {
                        const pct = (e.valeur - minVal) / (maxVal - minVal + 10);
                        return (
                            <div key={i} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 6 }}>
                                <span style={{ fontSize: 12, fontWeight: 700, color: 'var(--navy)', fontFamily: "'DM Mono', monospace" }}>{e.valeur}%</span>
                                <div style={{ width: '100%', height: 120, background: '#F8FAFC', borderRadius: 8, position: 'relative', overflow: 'hidden' }}>
                                    {/* cible line */}
                                    <div style={{ position: 'absolute', bottom: '72%', left: 0, right: 0, height: 1, background: '#E2E8F0', borderStyle: 'dashed' }} />
                                    <div style={{
                                        position: 'absolute', bottom: 0, left: 0, right: 0,
                                        height: `${40 + pct * 60}%`,
                                        background: e.valeur >= 85 ? 'var(--gold)' : 'var(--navy)',
                                        borderRadius: '6px 6px 0 0',
                                        opacity: i === EVOLUTION.length - 1 ? 1 : 0.6,
                                    }} />
                                </div>
                                <span style={{ fontSize: 11, color: '#94A3B8', fontWeight: 500 }}>{e.mois}</span>
                            </div>
                        );
                    })}
                </div>
            </div>
        </DashboardLayout>
    );
}