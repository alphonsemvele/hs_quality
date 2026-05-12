import { Badge, Button, ConfirmDialog, PageHeader, Wizard, type WizardStep } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import DashboardLayout from '../layout';

const STEPS: WizardStep[] = [
    { id: 'intro', label: 'Pourquoi', description: 'Pourquoi activer la double authentification ?' },
    { id: 'qr', label: 'Scanner', description: 'Scannez le QR code dans votre application TOTP.' },
    { id: 'verify', label: 'Vérifier', description: 'Saisissez le code généré par votre application.' },
    { id: 'codes', label: 'Codes de secours', description: 'Sauvegardez ces codes pour le cas où vous perdriez votre appareil.' },
];

function getCsrf(): string {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') ?? '' : '';
}

async function api(method: string, url: string): Promise<Response> {
    return fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrf(),
        },
    });
}

async function apiJson<T>(method: string, url: string, body?: unknown): Promise<T> {
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrf(),
        },
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    return res.json() as Promise<T>;
}

export default function MfaSetup() {
    const [step, setStep] = useState(0);
    const [enabling, setEnabling] = useState(false);
    const [qrSvg, setQrSvg] = useState<string | null>(null);
    const [secret, setSecret] = useState<string | null>(null);
    const [code, setCode] = useState('');
    const [confirming, setConfirming] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);
    const [showCancel, setShowCancel] = useState(false);

    // ── Step transitions ─────────────────────────────────────────
    const enable = async () => {
        setEnabling(true);
        setError(null);
        try {
            const res = await api('POST', '/user/two-factor-authentication');
            if (!res.ok) throw new Error('Activation échouée');
            const [qrRes, secretRes] = await Promise.all([
                apiJson<{ svg: string }>('GET', '/user/two-factor-qr-code'),
                apiJson<{ secretKey: string }>('GET', '/user/two-factor-secret-key'),
            ]);
            setQrSvg(qrRes.svg);
            setSecret(secretRes.secretKey);
            setStep(1);
        } catch {
            setError("Impossible d'activer le MFA. Réessayez ou contactez le support.");
        } finally {
            setEnabling(false);
        }
    };

    const confirm = async () => {
        if (code.length !== 6) {
            setError('Le code doit faire 6 chiffres.');
            return;
        }
        setConfirming(true);
        setError(null);
        try {
            await apiJson('POST', '/user/confirmed-two-factor-authentication', { code });
            const codes = await apiJson<string[]>('GET', '/user/two-factor-recovery-codes');
            setRecoveryCodes(codes);
            setStep(3);
        } catch {
            setError('Code invalide. Vérifiez votre application et réessayez.');
        } finally {
            setConfirming(false);
        }
    };

    // ── Cancel handler (also revokes the half-enabled 2FA) ───────
    const reallyCancel = async () => {
        try {
            await api('DELETE', '/user/two-factor-authentication');
        } catch {
            // best-effort; still navigate away
        }
        router.visit('/dashboard/profile');
    };

    // ── Re-fetch QR if user clicks step 2 then back to step 1 ────
    useEffect(() => {
        // No-op for now — kept for future re-fetch logic
    }, [step]);

    return (
        <DashboardLayout title="Configurer le MFA" subtitle="Double authentification">
            <PageHeader
                title="Configurer la double authentification"
                subtitle="Protection renforcée — données de santé sous RGPD Art. 9"
                breadcrumb={[
                    { label: 'Tableau de bord', href: '/dashboard' },
                    { label: 'Mon profil', href: '/dashboard/profile' },
                    { label: 'MFA' },
                ]}
            />

            <Wizard
                steps={STEPS}
                currentStep={step}
                onStepChange={(i) => (i < step ? setStep(i) : undefined)}
                onCancel={() => setShowCancel(true)}
                onPrevious={() => setStep((s) => Math.max(0, s - 1))}
                onNext={() => (step === 0 ? enable() : step === 1 ? setStep(2) : undefined)}
                onFinish={() => router.visit('/dashboard/profile')}
                nextLabel={step === 0 ? 'Activer le MFA' : step === 1 ? "J'ai scanné" : 'Suivant'}
                nextLoading={step === 0 ? enabling : false}
                nextDisabled={step === 0 ? false : step === 1 ? !qrSvg : false}
                finishLabel="J'ai sauvegardé mes codes →"
            >
                {step === 0 && <IntroStep />}
                {step === 1 && qrSvg && secret && <QrStep qrSvg={qrSvg} secret={secret} />}
                {step === 2 && (
                    <VerifyStep
                        code={code}
                        onChange={(c) => {
                            setCode(c);
                            setError(null);
                        }}
                        error={error}
                        onSubmit={confirm}
                        confirming={confirming}
                    />
                )}
                {step === 3 && <CodesStep codes={recoveryCodes} />}

                {error && step !== 2 && (
                    <p className="mt-3 rounded-lg border border-danger-200 bg-danger-50/60 px-3 py-2 text-xs text-danger-900 dark:border-danger-700/40 dark:bg-danger-900/20 dark:text-danger-200">
                        {error}
                    </p>
                )}
            </Wizard>

            <ConfirmDialog
                open={showCancel}
                onClose={() => setShowCancel(false)}
                onConfirm={reallyCancel}
                title="Quitter la configuration ?"
                description="Si vous quittez, le MFA ne sera pas activé. Votre rôle nécessite la double authentification — vous serez invité·e à reprendre cette procédure à votre prochaine connexion."
                confirmLabel="Quitter la configuration"
                cancelLabel="Continuer la configuration"
                tone="warning"
            />
        </DashboardLayout>
    );
}

function IntroStep() {
    return (
        <div className="space-y-5">
            <div className="flex items-start gap-4 rounded-xl border border-brand-200 bg-brand-50/40 p-4 dark:border-brand-700/40 dark:bg-brand-900/15">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                    <ShieldIcon />
                </span>
                <div>
                    <p className="text-sm font-semibold text-brand-900 dark:text-brand-200">Pourquoi le MFA ?</p>
                    <p className="mt-1 text-xs leading-relaxed text-brand-900/80 dark:text-brand-200/80">
                        Votre compte donne accès à des données de santé sensibles. Le MFA empêche un attaquant qui aurait
                        votre mot de passe (phishing, fuite) de se connecter — il faut aussi votre téléphone.
                    </p>
                </div>
            </div>

            <div className="space-y-3">
                <Feature title="3 minutes à configurer" desc="Une seule fois. Ensuite : un code à 6 chiffres à chaque connexion." />
                <Feature title="Application gratuite recommandée" desc="Google Authenticator, Microsoft Authenticator, 1Password, Bitwarden, Aegis…" />
                <Feature title="Codes de secours" desc="Si vous perdez votre téléphone, des codes à usage unique vous permettent toujours d'accéder." />
            </div>

            <p className="text-[11px] text-ink-500 dark:text-ink-400">
                Conformité : ANSSI / NIST SP 800-63B. <strong className="font-semibold">SMS et email rejetés</strong> (risque
                SIM-swap, deprecated pour données de santé).
            </p>
        </div>
    );
}

function Feature({ title, desc }: { title: string; desc: string }) {
    return (
        <div className="flex items-start gap-3">
            <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-700 dark:bg-sage-900/40 dark:text-sage-300">
                <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={3} viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
            </span>
            <div className="min-w-0">
                <p className="text-sm font-medium text-ink-900 dark:text-white">{title}</p>
                <p className="mt-0.5 text-xs text-ink-500 dark:text-ink-400">{desc}</p>
            </div>
        </div>
    );
}

function QrStep({ qrSvg, secret }: { qrSvg: string; secret: string }) {
    const [copied, setCopied] = useState(false);

    const copySecret = async () => {
        try {
            await navigator.clipboard.writeText(secret);
            setCopied(true);
            setTimeout(() => setCopied(false), 2500);
        } catch {
            // ignore
        }
    };

    return (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 sm:items-center">
            <div className="flex flex-col items-center">
                <div
                    className="rounded-2xl border border-ink-200 bg-white p-4 dark:border-ink-700 dark:bg-white"
                    dangerouslySetInnerHTML={{ __html: qrSvg }}
                />
                <p className="mt-3 text-center text-[11px] text-ink-500 dark:text-ink-400">
                    Scannez avec votre application TOTP
                </p>
            </div>

            <div className="space-y-4">
                <div>
                    <p className="text-sm font-medium text-ink-900 dark:text-white">Pas de scanner ?</p>
                    <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                        Tapez ce code manuellement dans votre application :
                    </p>
                    <div className="mt-2 flex items-center gap-2">
                        <code className="flex-1 select-all rounded-lg border border-ink-200 bg-ink-50 px-3 py-2 font-mono text-sm tracking-wider text-ink-900 dark:border-ink-700 dark:bg-ink-900/60 dark:text-white">
                            {secret}
                        </code>
                        <Button variant="secondary" size="sm" onClick={copySecret}>
                            {copied ? '✓ Copié' : 'Copier'}
                        </Button>
                    </div>
                </div>

                <div className="rounded-xl border border-warning-200 bg-warning-50/40 px-3 py-2.5 text-xs dark:border-warning-700/40 dark:bg-warning-900/15">
                    <p className="font-semibold text-warning-900 dark:text-warning-200">⚠ Ne pas partager</p>
                    <p className="mt-0.5 text-warning-900/80 dark:text-warning-200/80">
                        Ce secret est strictement personnel. Quiconque y a accès peut générer vos codes MFA.
                    </p>
                </div>
            </div>
        </div>
    );
}

function VerifyStep({
    code,
    onChange,
    error,
    onSubmit,
    confirming,
}: {
    code: string;
    onChange: (c: string) => void;
    error: string | null;
    onSubmit: () => void;
    confirming: boolean;
}) {
    return (
        <div className="mx-auto max-w-sm space-y-4">
            <p className="text-sm text-ink-700 dark:text-ink-200">
                Saisissez le code à 6 chiffres affiché par votre application TOTP.
            </p>

            <input
                type="text"
                inputMode="numeric"
                pattern="[0-9]{6}"
                maxLength={6}
                autoComplete="one-time-code"
                value={code}
                onChange={(e) => onChange(e.target.value.replace(/\D/g, '').slice(0, 6))}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' && code.length === 6) onSubmit();
                }}
                placeholder="123456"
                className={cn(
                    'h-14 w-full rounded-xl border bg-white text-center font-mono text-2xl tracking-[0.5em] tabular-nums text-ink-900 focus:outline-none focus:ring-2 dark:bg-ink-900/40 dark:text-white',
                    error
                        ? 'border-danger-400 focus:border-danger-500 focus:ring-danger-100 dark:focus:ring-danger-900/30'
                        : 'border-ink-300 focus:border-brand-400 focus:ring-brand-100 dark:border-ink-600 dark:focus:ring-brand-900/30',
                )}
            />

            {error && (
                <p className="rounded-lg border border-danger-200 bg-danger-50/60 px-3 py-2 text-xs text-danger-900 dark:border-danger-700/40 dark:bg-danger-900/20 dark:text-danger-200">
                    {error}
                </p>
            )}

            <Button onClick={onSubmit} disabled={code.length !== 6} loading={confirming} className="w-full">
                Vérifier le code
            </Button>

            <p className="text-center text-[11px] text-ink-400 dark:text-ink-500">
                Le code change toutes les 30 secondes — patientez si nécessaire.
            </p>
        </div>
    );
}

function CodesStep({ codes }: { codes: string[] }) {
    const [copied, setCopied] = useState(false);
    const [acked, setAcked] = useState(false);

    const copyAll = async () => {
        try {
            await navigator.clipboard.writeText(codes.join('\n'));
            setCopied(true);
            setTimeout(() => setCopied(false), 2500);
        } catch {
            // ignore
        }
    };

    const download = () => {
        const blob = new Blob([`HS Quality — Codes de secours MFA\n\n${codes.join('\n')}\n\nGénéré le ${new Date().toLocaleString('fr-FR')}\nConservez ce fichier en lieu sûr.\n`], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'hs-quality-mfa-recovery-codes.txt';
        a.click();
        URL.revokeObjectURL(url);
    };

    return (
        <div className="space-y-5">
            <div className="rounded-xl border border-sage-200 bg-sage-50/40 p-4 dark:border-sage-700/40 dark:bg-sage-900/15">
                <p className="text-sm font-semibold text-sage-900 dark:text-sage-100">
                    ✓ MFA activé avec succès
                </p>
                <p className="mt-1 text-xs text-sage-900/80 dark:text-sage-200/80">
                    À votre prochaine connexion, vous saisirez d'abord votre mot de passe puis votre code TOTP.
                </p>
            </div>

            <div>
                <div className="flex items-center justify-between gap-2">
                    <p className="text-sm font-semibold text-ink-900 dark:text-white">Codes de secours à usage unique</p>
                    <Badge tone="warning" size="xs">À sauvegarder</Badge>
                </div>
                <p className="mt-1 text-xs text-ink-500 dark:text-ink-400">
                    Chaque code peut être utilisé <strong className="font-semibold">une seule fois</strong> pour vous
                    connecter si vous perdez votre téléphone.
                </p>

                <div className="mt-3 grid grid-cols-2 gap-2 rounded-xl border border-ink-200 bg-ink-50/40 p-4 sm:grid-cols-2 dark:border-ink-700 dark:bg-ink-900/30">
                    {codes.map((c, i) => (
                        <code
                            key={i}
                            className="select-all rounded-md bg-white px-2 py-1.5 text-center font-mono text-xs tabular-nums tracking-wider text-ink-900 dark:bg-ink-800 dark:text-white"
                        >
                            {c}
                        </code>
                    ))}
                </div>

                <div className="mt-3 flex flex-wrap gap-2">
                    <Button variant="secondary" size="sm" onClick={copyAll}>
                        {copied ? '✓ Copiés' : 'Copier tout'}
                    </Button>
                    <Button variant="secondary" size="sm" onClick={download}>
                        Télécharger (.txt)
                    </Button>
                </div>
            </div>

            <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-warning-200 bg-warning-50/40 p-3 dark:border-warning-700/40 dark:bg-warning-900/15">
                <input
                    type="checkbox"
                    checked={acked}
                    onChange={(e) => setAcked(e.target.checked)}
                    className="mt-0.5 size-4 rounded border-warning-300 text-warning-700 focus:ring-warning-400 dark:border-warning-700 dark:bg-warning-900/30"
                />
                <span className="text-xs text-warning-900 dark:text-warning-200">
                    Je confirme avoir sauvegardé ces codes dans un endroit sûr (gestionnaire de mots de passe, coffre-fort
                    numérique, impression dans un classeur sécurisé). <strong className="font-semibold">Ils ne seront plus
                    affichés.</strong>
                </span>
            </label>

            {!acked && (
                <p className="text-center text-[11px] text-ink-400 dark:text-ink-500">
                    Cochez la case ci-dessus pour activer le bouton final.
                </p>
            )}

            <input type="hidden" disabled={!acked} />

            <Link
                href="/dashboard/profile"
                className={cn(
                    'flex justify-center text-xs',
                    acked ? 'text-brand-600 hover:underline dark:text-brand-400' : 'pointer-events-none text-ink-400 dark:text-ink-500',
                )}
            >
                Je termine plus tard {acked ? '' : '(cochez la case)'}
            </Link>
        </div>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-5" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0110 0v4" />
        </svg>
    );
}
