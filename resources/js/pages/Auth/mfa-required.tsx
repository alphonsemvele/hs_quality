import { Head, router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

type Stage = 'idle' | 'enrolling' | 'done';

const ROLE_LABELS: Record<string, string> = {
    dirigeant: 'Dirigeant',
    coordinateur: 'Coordinateur',
    referent_qualite: 'Référent qualité',
    rh: 'Ressources humaines',
};

function readXsrfToken(): string {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='));
    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

async function jsonRequest(method: string, url: string, body?: Record<string, unknown>): Promise<Response> {
    return fetch(url, {
        method,
        credentials: 'include',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': readXsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : undefined,
    });
}

export default function MfaRequired({ role }: { role: string }) {
    const roleLabel = ROLE_LABELS[role] ?? role;

    const [stage, setStage] = useState<Stage>('idle');
    const [password, setPassword] = useState('');
    const [code, setCode] = useState('');
    const [qrSvg, setQrSvg] = useState('');
    const [secretKey, setSecretKey] = useState('');
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [error, setError] = useState('');
    const [busy, setBusy] = useState(false);

    const startEnrollment: FormEventHandler = async (e) => {
        e.preventDefault();
        setError('');
        setBusy(true);

        try {
            const confirm = await jsonRequest('POST', '/user/confirm-password', { password });
            if (!confirm.ok) {
                setError('Mot de passe incorrect.');
                return;
            }

            const enable = await jsonRequest('POST', '/user/two-factor-authentication');
            if (!enable.ok) {
                setError("Impossible d'activer la 2FA. Réessayez ou contactez votre administrateur.");
                return;
            }

            const [qrResp, secretResp, codesResp] = await Promise.all([
                jsonRequest('GET', '/user/two-factor-qr-code'),
                jsonRequest('GET', '/user/two-factor-secret-key'),
                jsonRequest('GET', '/user/two-factor-recovery-codes'),
            ]);

            if (!qrResp.ok || !secretResp.ok || !codesResp.ok) {
                setError('Erreur lors de la récupération du QR code. Rechargez la page.');
                return;
            }

            const [qr, secret, codes] = await Promise.all([qrResp.json(), secretResp.json(), codesResp.json()]);
            setQrSvg(qr.svg);
            setSecretKey(secret.secretKey);
            setRecoveryCodes(codes);
            setPassword('');
            setStage('enrolling');
        } finally {
            setBusy(false);
        }
    };

    const confirmCode: FormEventHandler = async (e) => {
        e.preventDefault();
        setError('');
        setBusy(true);

        try {
            const resp = await jsonRequest('POST', '/user/confirmed-two-factor-authentication', { code });
            if (!resp.ok) {
                setError("Code incorrect. Vérifiez l'heure de votre appareil et réessayez.");
                return;
            }
            setStage('done');
            setTimeout(() => router.visit('/dashboard'), 1200);
        } finally {
            setBusy(false);
        }
    };

    const logout = () => {
        router.post('/logout');
    };

    return (
        <>
            <Head title="Authentification à deux facteurs requise — HS Quality" />

            <style>{`
                @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap');
                * { box-sizing: border-box; }
                :root {
                    --navy:  #0B1628;
                    --gold:  #F59E0B;
                    --gold2: #FCD34D;
                }
                body { font-family: 'DM Sans', sans-serif; background: #FAFAFA; }
                .serif { font-family: 'DM Serif Display', serif; }
                .mono  { font-family: 'DM Mono', monospace; }
                @keyframes fadeUp { from { opacity:0; transform:translateY(18px) } to { opacity:1; transform:translateY(0) } }
                @keyframes pulse  { 0%,100%{opacity:1} 50%{opacity:.4} }
                @keyframes spin  { to { transform:rotate(360deg) } }
                .fade-up { animation: fadeUp .5s ease both; }
                .spin    { animation: spin 1s linear infinite; }

                .field-wrap { position:relative; display:flex; align-items:center; border-radius:14px; border:1.5px solid #E2E8F0; background:#FAFAFA; transition:all .2s; }
                .field-wrap:hover  { border-color:#CBD5E1; }
                .field-wrap.focused { border-color:var(--gold); background:#fff; box-shadow:0 0 0 3px rgba(245,158,11,.1); }

                .submit-btn {
                    width:100%; background:var(--navy); color:#fff;
                    font-weight:700; font-size:14px; padding:15px;
                    border-radius:14px; border:none; cursor:pointer;
                    transition:all .2s; display:flex; align-items:center; justify-content:center; gap:8px;
                }
                .submit-btn:hover:not(:disabled) { background:#162440; transform:translateY(-1px); box-shadow:0 8px 24px rgba(11,22,40,.2); }
                .submit-btn:disabled { background:#E2E8F0; color:#94A3B8; cursor:not-allowed; transform:none; }
            `}</style>

            <div style={{ minHeight: '100vh', display: 'flex', flexDirection: 'column', fontFamily: "'DM Sans', sans-serif" }}>
                {/* Top bar */}
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        padding: '20px 40px',
                        borderBottom: '1px solid #F1F5F9',
                        background: '#fff',
                    }}
                >
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        <div
                            style={{
                                width: 36,
                                height: 36,
                                background: 'linear-gradient(135deg, var(--gold) 0%, #D97706 100%)',
                                borderRadius: 10,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                fontFamily: "'DM Serif Display', serif",
                                fontWeight: 700,
                                fontSize: 16,
                                color: 'var(--navy)',
                            }}
                        >
                            Q
                        </div>
                        <div>
                            <div style={{ fontWeight: 800, fontSize: 15, color: 'var(--navy)', lineHeight: 1 }}>HS Quality</div>
                            <div
                                style={{
                                    fontSize: 9,
                                    fontWeight: 700,
                                    letterSpacing: '.12em',
                                    textTransform: 'uppercase',
                                    color: 'var(--gold)',
                                    lineHeight: 1,
                                    marginTop: 3,
                                }}
                            >
                                Sécurité du compte
                            </div>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={logout}
                        style={{ fontSize: 13, color: '#94A3B8', background: 'none', border: 'none', cursor: 'pointer', fontWeight: 500 }}
                    >
                        Se déconnecter
                    </button>
                </div>

                {/* Content */}
                <div style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '48px 24px' }}>
                    <div
                        className="fade-up"
                        style={{
                            width: '100%',
                            maxWidth: 520,
                            background: '#fff',
                            border: '1px solid #F1F5F9',
                            borderRadius: 20,
                            padding: '40px',
                            boxShadow: '0 1px 3px rgba(15,23,42,.04)',
                        }}
                    >
                        {/* Badge */}
                        <div
                            style={{
                                display: 'inline-flex',
                                alignItems: 'center',
                                gap: 7,
                                background: 'rgba(245,158,11,.08)',
                                border: '1px solid rgba(245,158,11,.2)',
                                color: 'var(--gold)',
                                fontSize: 11,
                                fontWeight: 700,
                                letterSpacing: '.08em',
                                padding: '6px 12px',
                                borderRadius: 100,
                                marginBottom: 20,
                                textTransform: 'uppercase',
                            }}
                        >
                            <span style={{ width: 6, height: 6, borderRadius: '50%', background: 'var(--gold)', animation: 'pulse 2s infinite' }} />
                            Sécurité requise · CDC §5
                        </div>

                        {stage === 'idle' && (
                            <IdleStage
                                roleLabel={roleLabel}
                                password={password}
                                setPassword={setPassword}
                                onSubmit={startEnrollment}
                                error={error}
                                busy={busy}
                            />
                        )}

                        {stage === 'enrolling' && (
                            <EnrollingStage
                                qrSvg={qrSvg}
                                secretKey={secretKey}
                                recoveryCodes={recoveryCodes}
                                code={code}
                                setCode={setCode}
                                onSubmit={confirmCode}
                                error={error}
                                busy={busy}
                            />
                        )}

                        {stage === 'done' && <DoneStage />}
                    </div>
                </div>

                <div
                    style={{
                        padding: '16px 40px',
                        borderTop: '1px solid #F1F5F9',
                        display: 'flex',
                        justifyContent: 'center',
                        alignItems: 'center',
                        gap: 6,
                        fontSize: 11,
                        color: '#CBD5E1',
                        background: '#fff',
                    }}
                >
                    <svg width={13} height={13} fill="none" stroke="#F59E0B" strokeWidth={2} viewBox="0 0 24 24">
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                        />
                    </svg>
                    Connexion chiffrée TLS 1.3 · RGPD · HDS
                </div>
            </div>
        </>
    );
}

function IdleStage({
    roleLabel,
    password,
    setPassword,
    onSubmit,
    error,
    busy,
}: {
    roleLabel: string;
    password: string;
    setPassword: (v: string) => void;
    onSubmit: FormEventHandler;
    error: string;
    busy: boolean;
}) {
    const [focused, setFocused] = useState(false);

    return (
        <>
            <h1 className="serif" style={{ fontSize: 32, color: 'var(--navy)', fontWeight: 400, lineHeight: 1.15, marginBottom: 12 }}>
                Activez votre <em style={{ color: 'var(--gold)' }}>authentification</em>
                <br />à deux facteurs.
            </h1>
            <p style={{ fontSize: 14, color: '#64748B', lineHeight: 1.7, fontWeight: 300, marginBottom: 28 }}>
                Votre rôle <strong style={{ color: 'var(--navy)', fontWeight: 600 }}>{roleLabel}</strong> manipule des données de santé sensibles. La
                2FA par TOTP est obligatoire pour accéder à la plateforme (CDC §5). Munissez-vous d'une application comme{' '}
                <strong style={{ color: 'var(--navy)' }}>1Password</strong>, <strong style={{ color: 'var(--navy)' }}>Google Authenticator</strong> ou{' '}
                <strong style={{ color: 'var(--navy)' }}>Authy</strong>.
            </p>

            <form onSubmit={onSubmit}>
                <label
                    style={{
                        display: 'block',
                        fontSize: 11,
                        fontWeight: 700,
                        letterSpacing: '.08em',
                        textTransform: 'uppercase',
                        color: '#64748B',
                        marginBottom: 8,
                    }}
                >
                    Confirmez votre mot de passe
                </label>
                <div className={`field-wrap ${focused ? 'focused' : ''}`} style={{ marginBottom: error ? 8 : 24 }}>
                    <div style={{ paddingLeft: 14, color: focused ? 'var(--gold)' : '#CBD5E1', transition: 'color .2s' }}>
                        <svg width={16} height={16} fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                    </div>
                    <input
                        type="password"
                        autoFocus
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        onFocus={() => setFocused(true)}
                        onBlur={() => setFocused(false)}
                        placeholder="••••••••••"
                        required
                        style={{
                            flex: 1,
                            background: 'transparent',
                            border: 'none',
                            outline: 'none',
                            padding: '13px 12px',
                            fontSize: 14,
                            color: 'var(--navy)',
                        }}
                    />
                </div>

                {error && (
                    <p style={{ marginBottom: 16, fontSize: 12, color: '#EF4444', display: 'flex', alignItems: 'center', gap: 5 }}>
                        <svg width={12} height={12} fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fillRule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clipRule="evenodd"
                            />
                        </svg>
                        {error}
                    </p>
                )}

                <button type="submit" disabled={busy || password.length === 0} className="submit-btn">
                    {busy ? (
                        <>
                            <svg className="spin" width={16} height={16} fill="none" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" strokeOpacity=".25" />
                                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Préparation…
                        </>
                    ) : (
                        <>
                            Continuer
                            <svg width={15} height={15} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </>
                    )}
                </button>
            </form>
        </>
    );
}

function EnrollingStage({
    qrSvg,
    secretKey,
    recoveryCodes,
    code,
    setCode,
    onSubmit,
    error,
    busy,
}: {
    qrSvg: string;
    secretKey: string;
    recoveryCodes: string[];
    code: string;
    setCode: (v: string) => void;
    onSubmit: FormEventHandler;
    error: string;
    busy: boolean;
}) {
    const [focused, setFocused] = useState(false);
    const [showRecovery, setShowRecovery] = useState(false);

    return (
        <>
            <h1 className="serif" style={{ fontSize: 28, color: 'var(--navy)', fontWeight: 400, lineHeight: 1.15, marginBottom: 12 }}>
                Scannez le <em style={{ color: 'var(--gold)' }}>QR code</em>.
            </h1>
            <p style={{ fontSize: 14, color: '#64748B', lineHeight: 1.7, fontWeight: 300, marginBottom: 24 }}>
                Ouvrez votre application d'authentification et scannez ce code, puis saisissez le code à 6 chiffres généré.
            </p>

            <div
                style={{
                    background: '#FAFAFA',
                    border: '1px solid #F1F5F9',
                    borderRadius: 14,
                    padding: 24,
                    marginBottom: 20,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    gap: 16,
                }}
            >
                <div
                    style={{
                        width: 200,
                        height: 200,
                        background: '#fff',
                        padding: 12,
                        borderRadius: 10,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                    }}
                    dangerouslySetInnerHTML={{ __html: qrSvg }}
                />
                <div style={{ textAlign: 'center' }}>
                    <div
                        style={{
                            fontSize: 10,
                            fontWeight: 700,
                            letterSpacing: '.08em',
                            textTransform: 'uppercase',
                            color: '#94A3B8',
                            marginBottom: 4,
                        }}
                    >
                        Saisie manuelle
                    </div>
                    <code className="mono" style={{ fontSize: 12, color: 'var(--navy)', userSelect: 'all', wordBreak: 'break-all' }}>
                        {secretKey}
                    </code>
                </div>
            </div>

            <button
                type="button"
                onClick={() => setShowRecovery(!showRecovery)}
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 6,
                    background: 'none',
                    border: 'none',
                    color: 'var(--gold)',
                    fontSize: 12,
                    fontWeight: 600,
                    cursor: 'pointer',
                    marginBottom: 16,
                    padding: 0,
                }}
            >
                <svg
                    width={12}
                    height={12}
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2}
                    viewBox="0 0 24 24"
                    style={{ transform: showRecovery ? 'rotate(90deg)' : 'none', transition: 'transform .2s' }}
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                {showRecovery ? 'Masquer' : 'Afficher'} les codes de récupération ({recoveryCodes.length})
            </button>

            {showRecovery && (
                <div style={{ background: '#FFF7ED', border: '1px solid #FED7AA', borderRadius: 12, padding: 16, marginBottom: 20 }}>
                    <p style={{ fontSize: 12, color: '#9A3412', marginBottom: 10, fontWeight: 500 }}>
                        ⚠️ Conservez ces codes en lieu sûr. Ils permettent de récupérer l'accès si vous perdez votre appareil.
                    </p>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 6 }}>
                        {recoveryCodes.map((rc) => (
                            <code
                                key={rc}
                                className="mono"
                                style={{
                                    fontSize: 12,
                                    color: 'var(--navy)',
                                    background: '#fff',
                                    padding: '6px 10px',
                                    borderRadius: 6,
                                    border: '1px solid #FED7AA',
                                    userSelect: 'all',
                                }}
                            >
                                {rc}
                            </code>
                        ))}
                    </div>
                </div>
            )}

            <form onSubmit={onSubmit}>
                <label
                    style={{
                        display: 'block',
                        fontSize: 11,
                        fontWeight: 700,
                        letterSpacing: '.08em',
                        textTransform: 'uppercase',
                        color: '#64748B',
                        marginBottom: 8,
                    }}
                >
                    Code à 6 chiffres
                </label>
                <div className={`field-wrap ${focused ? 'focused' : ''}`} style={{ marginBottom: error ? 8 : 20 }}>
                    <input
                        type="text"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        autoFocus
                        value={code}
                        onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                        onFocus={() => setFocused(true)}
                        onBlur={() => setFocused(false)}
                        placeholder="123 456"
                        required
                        className="mono"
                        style={{
                            flex: 1,
                            background: 'transparent',
                            border: 'none',
                            outline: 'none',
                            padding: '13px 18px',
                            fontSize: 18,
                            color: 'var(--navy)',
                            letterSpacing: '.3em',
                            textAlign: 'center',
                        }}
                    />
                </div>

                {error && (
                    <p style={{ marginBottom: 16, fontSize: 12, color: '#EF4444', display: 'flex', alignItems: 'center', gap: 5 }}>
                        <svg width={12} height={12} fill="currentColor" viewBox="0 0 20 20">
                            <path
                                fillRule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clipRule="evenodd"
                            />
                        </svg>
                        {error}
                    </p>
                )}

                <button type="submit" disabled={busy || code.length !== 6} className="submit-btn">
                    {busy ? (
                        <>
                            <svg className="spin" width={16} height={16} fill="none" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" strokeOpacity=".25" />
                                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Vérification…
                        </>
                    ) : (
                        <>
                            Activer la 2FA
                            <svg width={15} height={15} fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </>
                    )}
                </button>
            </form>
        </>
    );
}

function DoneStage() {
    return (
        <div style={{ textAlign: 'center', padding: '20px 0' }}>
            <div
                style={{
                    width: 64,
                    height: 64,
                    borderRadius: '50%',
                    background: '#16A34A',
                    margin: '0 auto 24px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                }}
            >
                <svg width={28} height={28} fill="none" stroke="#fff" strokeWidth={3} viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 className="serif" style={{ fontSize: 28, color: 'var(--navy)', fontWeight: 400, lineHeight: 1.15, marginBottom: 12 }}>
                2FA <em style={{ color: 'var(--gold)' }}>activée</em>.
            </h1>
            <p style={{ fontSize: 14, color: '#64748B', lineHeight: 1.7, fontWeight: 300 }}>Redirection vers votre tableau de bord…</p>
        </div>
    );
}
