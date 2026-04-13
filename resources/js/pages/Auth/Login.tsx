import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPass, setShowPass] = useState(false);
    const [focused, setFocused] = useState<string | null>(null);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login');
    };

    const fillDemo = () => {
        setData('email', 'alphonsemvele95@gmail.com');
        setData('password', 'aaaaaaaaaa');
    };

    return (
        <>
            <Head title="Connexion — HS Quality" />

            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap');
                * { box-sizing: border-box; }
                :root {
                    --navy:  #0B1628;
                    --gold:  #F59E0B;
                    --gold2: #FCD34D;
                    --border: rgba(255,255,255,0.07);
                }
                body { font-family: 'DM Sans', sans-serif; }
                .serif { font-family: 'DM Serif Display', serif; }
                .mono  { font-family: 'DM Mono', monospace; }
                @keyframes fadeUp { from { opacity:0; transform:translateY(18px) } to { opacity:1; transform:translateY(0) } }
                @keyframes pulse  { 0%,100%{opacity:1} 50%{opacity:.4} }
                .fade-up { animation: fadeUp .6s ease both; }
                .au-1 { animation-delay:.08s; }
                .au-2 { animation-delay:.18s; }
                .au-3 { animation-delay:.28s; }

                /* champ input focus */
                .field-wrap { position:relative; display:flex; align-items:center; border-radius:14px; border:1.5px solid #E2E8F0; background:#FAFAFA; transition:all .2s; }
                .field-wrap:hover { border-color:#CBD5E1; }
                .field-wrap.focused { border-color:var(--gold); background:#fff; box-shadow:0 0 0 3px rgba(245,158,11,.1); }
                .field-wrap.error   { border-color:#FCA5A5; background:#FFF5F5; }

                /* bouton démo */
                .demo-btn {
                    display:inline-flex; align-items:center; gap:8px;
                    background:rgba(245,158,11,.08); border:1.5px dashed rgba(245,158,11,.4);
                    color:var(--gold); font-size:13px; font-weight:600;
                    padding:10px 18px; border-radius:12px; cursor:pointer;
                    transition:all .2s; width:100%; justify-content:center;
                }
                .demo-btn:hover { background:rgba(245,158,11,.15); border-color:var(--gold); transform:translateY(-1px); }

                /* submit */
                .submit-btn {
                    width:100%; background:var(--navy); color:#fff;
                    font-weight:700; font-size:14px; padding:15px;
                    border-radius:14px; border:none; cursor:pointer;
                    transition:all .2s; display:flex; align-items:center; justify-content:center; gap:8px;
                }
                .submit-btn:hover:not(:disabled) { background:#162440; transform:translateY(-1px); box-shadow:0 8px 24px rgba(11,22,40,.2); }
                .submit-btn:disabled { background:#E2E8F0; color:#94A3B8; cursor:not-allowed; transform:none; }

                @keyframes spin { to { transform:rotate(360deg) } }
                .spin { animation: spin 1s linear infinite; }

                /* right panel */
                .noise {
                    position:absolute; inset:0; pointer-events:none;
                    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
                    opacity:.06;
                }
            `}</style>

            <div style={{ minHeight: '100vh', display: 'flex', overflow: 'hidden', fontFamily: "'DM Sans', sans-serif" }}>

                {/* ══════════════════════════════════════════════════
                    GAUCHE — FORMULAIRE
                ══════════════════════════════════════════════════ */}
                <div style={{ flex: 1, display: 'flex', flexDirection: 'column', background: '#fff', position: 'relative', zIndex: 10 }}>

                    {/* Top bar */}
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '20px 40px', borderBottom: '1px solid #F1F5F9' }}>
                        <Link href="/" style={{ display: 'flex', alignItems: 'center', gap: 12, textDecoration: 'none' }}>
                            <div style={{
                                width: 36, height: 36,
                                background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)',
                                borderRadius: 10, display: 'flex', alignItems: 'center', justifyContent: 'center',
                                fontFamily: "'DM Serif Display', serif", fontWeight: 700, fontSize: 16, color: 'var(--navy)',
                            }}>Q</div>
                            <div>
                                <div style={{ fontWeight: 800, fontSize: 15, color: 'var(--navy)', lineHeight: 1 }}>HS Quality</div>
                                <div style={{ fontSize: 9, fontWeight: 700, letterSpacing: '.12em', textTransform: 'uppercase', color: 'var(--gold)', lineHeight: 1, marginTop: 3 }}>Qualité & QVCT</div>
                            </div>
                        </Link>
                        <span style={{ fontSize: 13, color: '#94A3B8' }}>
                            Pas encore de compte ?{' '}
                            <a href="#" style={{ fontWeight: 600, color: 'var(--navy)', textDecoration: 'none' }}>Contacter l'équipe →</a>
                        </span>
                    </div>

                    {/* Form */}
                    <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '48px 40px' }}>
                        <div style={{ width: '100%', maxWidth: 420 }}>

                            {/* Header */}
                            <div className="fade-up" style={{ marginBottom: 36 }}>
                                <div style={{
                                    display: 'inline-flex', alignItems: 'center', gap: 7,
                                    background: 'rgba(245,158,11,.08)', border: '1px solid rgba(245,158,11,.2)',
                                    color: 'var(--gold)', fontSize: 11, fontWeight: 700, letterSpacing: '.08em',
                                    padding: '6px 12px', borderRadius: 100, marginBottom: 20, textTransform: 'uppercase',
                                }}>
                                    <span style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--gold)', animation: 'pulse 2s infinite' }} />
                                    Accès sécurisé · TLS 1.3
                                </div>
                                <h1 className="serif" style={{ fontSize: 40, color: 'var(--navy)', fontWeight: 400, lineHeight: 1.08, marginBottom: 10 }}>
                                    Bon retour<br />
                                    <em style={{ color: 'var(--gold)' }}>sur HS Quality.</em>
                                </h1>
                                <p style={{ fontSize: 14, color: '#94A3B8', lineHeight: 1.7, fontWeight: 300 }}>
                                    Connectez-vous pour accéder à votre tableau de bord qualité et QVCT.
                                </p>
                            </div>

                            {status && (
                                <div className="fade-up" style={{ marginBottom: 20, display: 'flex', alignItems: 'center', gap: 10, background: '#F0FDF4', border: '1px solid #BBF7D0', borderRadius: 14, padding: '14px 16px' }}>
                                    <div style={{ width: 20, height: 20, borderRadius: '50%', background: '#16A34A', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0 }}>
                                        <svg width={11} height={11} fill="none" stroke="#fff" strokeWidth={3} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                    <p style={{ fontSize: 13, color: '#15803D', fontWeight: 500 }}>{status}</p>
                                </div>
                            )}

                            {/* ── Bouton démo ──────────────────────────────── */}
                            <div className="fade-up au-1" style={{ marginBottom: 24 }}>
                                <button type="button" className="demo-btn" onClick={fillDemo}>
                                    <svg width={15} height={15} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Utiliser les identifiants de démonstration
                                </button>
                            </div>

                            <div className="fade-up au-1" style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 24 }}>
                                <div style={{ flex: 1, height: 1, background: '#F1F5F9' }} />
                                <span style={{ fontSize: 11, color: '#CBD5E1', fontWeight: 600 }}>ou saisir manuellement</span>
                                <div style={{ flex: 1, height: 1, background: '#F1F5F9' }} />
                            </div>

                            <form onSubmit={submit}>
                                {/* Email */}
                                <div className="fade-up au-1" style={{ marginBottom: 16 }}>
                                    <label style={{ display: 'block', fontSize: 11, fontWeight: 700, letterSpacing: '.08em', textTransform: 'uppercase', color: '#64748B', marginBottom: 8 }}>
                                        Adresse email
                                    </label>
                                    <div className={`field-wrap ${focused === 'email' ? 'focused' : ''} ${errors.email ? 'error' : ''}`}>
                                        <div style={{ paddingLeft: 14, color: focused === 'email' ? 'var(--gold)' : '#CBD5E1', transition: 'color .2s' }}>
                                            <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="email" type="email" autoFocus
                                            value={data.email}
                                            onChange={e => setData('email', e.target.value)}
                                            onFocus={() => setFocused('email')}
                                            onBlur={() => setFocused(null)}
                                            placeholder="votre@email.com"
                                            style={{ flex: 1, background: 'transparent', border: 'none', outline: 'none', padding: '13px 12px', fontSize: 14, color: 'var(--navy)' }}
                                        />
                                    </div>
                                    {errors.email && (
                                        <p style={{ marginTop: 6, fontSize: 12, color: '#EF4444', display: 'flex', alignItems: 'center', gap: 5 }}>
                                            <svg width={12} height={12} fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" /></svg>
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                {/* Password */}
                                <div className="fade-up au-2" style={{ marginBottom: 16 }}>
                                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                                        <label style={{ fontSize: 11, fontWeight: 700, letterSpacing: '.08em', textTransform: 'uppercase', color: '#64748B' }}>
                                            Mot de passe
                                        </label>
                                        <a href="#" style={{ fontSize: 12, fontWeight: 600, color: 'var(--navy)', textDecoration: 'none', opacity: .6 }}>
                                            Mot de passe oublié ?
                                        </a>
                                    </div>
                                    <div className={`field-wrap ${focused === 'password' ? 'focused' : ''} ${errors.password ? 'error' : ''}`}>
                                        <div style={{ paddingLeft: 14, color: focused === 'password' ? 'var(--gold)' : '#CBD5E1', transition: 'color .2s' }}>
                                            <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <input
                                            id="password"
                                            type={showPass ? 'text' : 'password'}
                                            value={data.password}
                                            onChange={e => setData('password', e.target.value)}
                                            onFocus={() => setFocused('password')}
                                            onBlur={() => setFocused(null)}
                                            placeholder="••••••••••"
                                            style={{ flex: 1, background: 'transparent', border: 'none', outline: 'none', padding: '13px 12px', fontSize: 14, color: 'var(--navy)' }}
                                        />
                                        <button
                                            type="button" tabIndex={-1}
                                            onClick={() => setShowPass(!showPass)}
                                            style={{ paddingRight: 14, background: 'none', border: 'none', cursor: 'pointer', color: '#CBD5E1' }}
                                        >
                                            {showPass ? (
                                                <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            ) : (
                                                <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                    {errors.password && (
                                        <p style={{ marginTop: 6, fontSize: 12, color: '#EF4444', display: 'flex', alignItems: 'center', gap: 5 }}>
                                            <svg width={12} height={12} fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" /></svg>
                                            {errors.password}
                                        </p>
                                    )}
                                </div>

                                {/* Remember me */}
                                <div className="fade-up au-2" style={{ marginBottom: 24 }}>
                                    <label style={{ display: 'flex', alignItems: 'center', gap: 10, cursor: 'pointer', userSelect: 'none' }}>
                                        <div
                                            onClick={() => setData('remember', !data.remember)}
                                            style={{
                                                width: 18, height: 18, borderRadius: 6, flexShrink: 0,
                                                border: `1.5px solid ${data.remember ? 'var(--gold)' : '#E2E8F0'}`,
                                                background: data.remember ? 'var(--gold)' : 'white',
                                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                                transition: 'all .15s',
                                            }}
                                        >
                                            {data.remember && <svg width={10} height={10} fill="none" stroke="var(--navy)" strokeWidth={3} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>}
                                        </div>
                                        <input type="checkbox" checked={data.remember} onChange={e => setData('remember', e.target.checked)} style={{ display: 'none' }} />
                                        <span style={{ fontSize: 13, color: '#94A3B8', fontWeight: 300 }}>Rester connecté pendant 30 jours</span>
                                    </label>
                                </div>

                                {/* Submit */}
                                <div className="fade-up au-3">
                                    <button type="submit" disabled={processing} className="submit-btn">
                                        {processing ? (
                                            <>
                                                <svg className="spin" width={16} height={16} fill="none" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" strokeOpacity=".25" />
                                                    <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                </svg>
                                                Connexion en cours…
                                            </>
                                        ) : (
                                            <>
                                                Se connecter
                                                <svg width={15} height={15} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                </svg>
                                            </>
                                        )}
                                    </button>
                                </div>
                            </form>

                            {/* SSO */}
                            <div style={{ display: 'flex', alignItems: 'center', gap: 12, margin: '24px 0' }}>
                                <div style={{ flex: 1, height: 1, background: '#F1F5F9' }} />
                                <span style={{ fontSize: 11, color: '#CBD5E1', fontWeight: 600 }}>ou via SSO</span>
                                <div style={{ flex: 1, height: 1, background: '#F1F5F9' }} />
                            </div>

                            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                                {[
                                    { label: 'Google', icon: <svg width={16} height={16} viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg> },
                                    { label: 'Microsoft', icon: <svg width={16} height={16} viewBox="0 0 24 24"><path fill="#F25022" d="M1 1h10v10H1z"/><path fill="#7FBA00" d="M13 1h10v10H13z"/><path fill="#00A4EF" d="M1 13h10v10H1z"/><path fill="#FFB900" d="M13 13h10v10H13z"/></svg> },
                                ].map(p => (
                                    <button key={p.label} type="button" style={{
                                        display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8,
                                        padding: '11px', borderRadius: 12, border: '1.5px solid #E2E8F0',
                                        background: 'white', fontSize: 13, fontWeight: 600, color: '#475569',
                                        cursor: 'pointer', transition: 'all .15s',
                                    }}
                                        onMouseEnter={e => { (e.currentTarget as HTMLButtonElement).style.borderColor = '#CBD5E1'; (e.currentTarget as HTMLButtonElement).style.background = '#FAFAFA'; }}
                                        onMouseLeave={e => { (e.currentTarget as HTMLButtonElement).style.borderColor = '#E2E8F0'; (e.currentTarget as HTMLButtonElement).style.background = 'white'; }}
                                    >
                                        {p.icon}{p.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Bottom bar */}
                    <div style={{ padding: '16px 40px', borderTop: '1px solid #F1F5F9', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <Link href="/" style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 12, color: '#94A3B8', textDecoration: 'none', transition: 'color .15s' }}
                            onMouseEnter={e => (e.currentTarget.style.color = 'var(--gold)')}
                            onMouseLeave={e => (e.currentTarget.style.color = '#94A3B8')}
                        >
                            <svg width={13} height={13} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            Retour à l'accueil
                        </Link>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 6, fontSize: 11, color: '#CBD5E1' }}>
                            <svg width={13} height={13} fill="none" stroke="#F59E0B" strokeWidth={2} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            Connexion chiffrée TLS 1.3 · RGPD · HDS
                        </div>
                    </div>
                </div>

                {/* ══════════════════════════════════════════════════
                    DROITE — PANEL NAVY + OR
                ══════════════════════════════════════════════════ */}
                <div className="hidden lg:flex" style={{ width: '46%', position: 'relative', overflow: 'hidden', flexDirection: 'column', background: 'var(--navy)' }}>
                    <div className="noise" />

                    {/* Grid */}
                    <div style={{ position: 'absolute', inset: 0, opacity: .04, backgroundImage: 'linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px)', backgroundSize: '64px 64px' }} />

                    {/* Glow */}
                    <div style={{ position: 'absolute', top: '-15%', right: '-10%', width: 600, height: 600, borderRadius: '50%', background: 'radial-gradient(circle, rgba(245,158,11,.1) 0%, transparent 65%)', zIndex: 0 }} />
                    <div style={{ position: 'absolute', bottom: '-10%', left: '-5%', width: 400, height: 400, borderRadius: '50%', background: 'radial-gradient(circle, rgba(245,158,11,.06) 0%, transparent 65%)', zIndex: 0 }} />

                    {/* Content */}
                    <div style={{ position: 'relative', zIndex: 2, flex: 1, display: 'flex', flexDirection: 'column', justifyContent: 'space-between', padding: '56px 56px 48px' }}>

                        {/* Badge */}
                        <div style={{ display: 'inline-flex', alignItems: 'center', gap: 8, background: 'rgba(245,158,11,.1)', border: '1px solid rgba(245,158,11,.2)', color: 'var(--gold)', fontSize: 11, fontWeight: 700, letterSpacing: '.08em', padding: '7px 14px', borderRadius: 100, width: 'fit-content', textTransform: 'uppercase' }}>
                            <span style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--gold)', animation: 'pulse 2s infinite' }} />
                            Plateforme active · 50 structures pilotes
                        </div>

                        {/* Quote */}
                        <div>
                            <div style={{ width: 40, height: 3, background: 'var(--gold)', borderRadius: 2, marginBottom: 28 }} />
                            <blockquote className="serif" style={{ fontSize: 'clamp(32px, 3.5vw, 44px)', color: 'white', fontWeight: 400, lineHeight: 1.12, marginBottom: 24 }}>
                                La qualité<br />ne se déclare pas.<br />
                                <em style={{ color: 'var(--gold)' }}>Elle se mesure.</em>
                            </blockquote>
                            <p style={{ fontSize: 15, color: 'rgba(255,255,255,.4)', lineHeight: 1.75, maxWidth: 360, fontWeight: 300 }}>
                                HS Quality structure la démarche qualité et QVCT des structures médico-sociales — du terrain à la direction, en temps réel et en conformité HAS.
                            </p>
                        </div>

                        {/* KPI grid */}
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 12 }}>
                            {[
                                { n: '–15%', l: 'Incidents', s: 'dès l\'An 1' },
                                { n: '>60%', l: 'PAC', s: 'complétés' },
                                { n: '<5j',  l: 'Traitement', s: 'incident' },
                            ].map(({ n, l, s }, i) => (
                                <div key={i} style={{ background: 'rgba(255,255,255,.04)', border: '1px solid rgba(255,255,255,.07)', borderRadius: 14, padding: '16px 14px' }}>
                                    <div className="serif" style={{ fontSize: 26, color: 'var(--gold)', fontWeight: 400, lineHeight: 1 }}>{n}</div>
                                    <div style={{ fontSize: 12, color: 'white', fontWeight: 600, marginTop: 6 }}>{l}</div>
                                    <div style={{ fontSize: 11, color: 'rgba(255,255,255,.3)', marginTop: 2 }}>{s}</div>
                                </div>
                            ))}
                        </div>

                        {/* Testimonial */}
                        <div style={{ background: 'rgba(255,255,255,.04)', border: '1px solid rgba(255,255,255,.08)', borderRadius: 16, padding: '20px 22px' }}>
                            <div style={{ display: 'flex', gap: 3, marginBottom: 12 }}>
                                {[...Array(5)].map((_, i) => (
                                    <svg key={i} width={13} height={13} fill="var(--gold)" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                ))}
                            </div>
                            <p style={{ fontSize: 13, color: 'rgba(255,255,255,.55)', lineHeight: 1.7, fontStyle: 'italic', fontWeight: 300 }}>
                                "Nos coordinatrices déclarent les incidents en moins de 2 minutes. Notre taux de conformité HAS est passé de 61% à 84% en 6 mois."
                            </p>
                            <div style={{ display: 'flex', alignItems: 'center', gap: 10, marginTop: 16, paddingTop: 16, borderTop: '1px solid rgba(255,255,255,.07)' }}>
                                <div style={{ width: 34, height: 34, borderRadius: 10, background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 12, fontWeight: 800, color: 'var(--navy)' }}>
                                    MF
                                </div>
                                <div>
                                    <div style={{ fontSize: 13, color: 'white', fontWeight: 600 }}>Marie-France Essomba</div>
                                    <div style={{ fontSize: 11, color: 'rgba(255,255,255,.3)' }}>Directrice qualité · SAAD Horizon, Douala</div>
                                </div>
                            </div>
                        </div>

                        {/* Certif badges */}
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
                            {['HDS Certifié', 'RGPD', 'HAS', 'ISO 9001', 'TLS 1.3 + AES-256'].map(b => (
                                <span key={b} style={{ fontSize: 11, fontWeight: 600, color: 'rgba(255,255,255,.35)', border: '1px solid rgba(255,255,255,.1)', padding: '5px 10px', borderRadius: 8 }}>{b}</span>
                            ))}
                        </div>
                    </div>
                </div>

            </div>
        </>
    );
}