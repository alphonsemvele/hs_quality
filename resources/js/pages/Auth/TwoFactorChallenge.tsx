import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

export default function TwoFactorChallenge() {
    const [useRecovery, setUseRecovery] = useState(false);

    const codeForm = useForm({ code: '' });
    const recoveryForm = useForm({ recovery_code: '' });

    const submitCode: FormEventHandler = (e) => {
        e.preventDefault();
        codeForm.post('/two-factor-challenge');
    };

    const submitRecovery: FormEventHandler = (e) => {
        e.preventDefault();
        recoveryForm.post('/two-factor-challenge');
    };

    return (
        <>
            <Head title="Verification 2FA — HS Quality" />

            <div className="flex min-h-screen items-center justify-center bg-ink-50 px-4 font-sans antialiased dark:bg-ink-900">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <div className="mx-auto flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-serif text-lg font-semibold text-white">
                            Q
                        </div>
                        <h1 className="mt-4 text-xl font-bold text-ink-900 dark:text-white">Verification a deux facteurs</h1>
                        <p className="mt-2 text-sm text-ink-500 dark:text-ink-400">
                            {useRecovery
                                ? 'Saisissez un de vos codes de recuperation.'
                                : 'Saisissez le code TOTP genere par votre application d\'authentification.'}
                        </p>
                    </div>

                    <div className="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm dark:border-ink-700/60 dark:bg-ink-800">
                        {!useRecovery ? (
                            <form onSubmit={submitCode} className="space-y-4">
                                <div>
                                    <label htmlFor="code" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-600 dark:text-ink-400">
                                        Code TOTP
                                    </label>
                                    <input
                                        id="code"
                                        type="text"
                                        inputMode="numeric"
                                        autoComplete="one-time-code"
                                        value={codeForm.data.code}
                                        onChange={(e) => codeForm.setData('code', e.target.value)}
                                        required
                                        autoFocus
                                        maxLength={6}
                                        className="h-11 w-full rounded-lg border border-ink-200 bg-white px-3.5 text-center font-mono text-lg tracking-[0.3em] text-ink-900 transition-colors hover:border-ink-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-100"
                                        placeholder="000000"
                                    />
                                    {codeForm.errors.code && <p className="mt-1.5 text-xs text-danger-600 dark:text-danger-400">{codeForm.errors.code}</p>}
                                </div>

                                <button
                                    type="submit"
                                    disabled={codeForm.processing}
                                    className="flex h-11 w-full items-center justify-center rounded-lg bg-brand-600 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-brand-500 dark:hover:bg-brand-400"
                                >
                                    {codeForm.processing ? 'Verification...' : 'Verifier'}
                                </button>
                            </form>
                        ) : (
                            <form onSubmit={submitRecovery} className="space-y-4">
                                <div>
                                    <label htmlFor="recovery_code" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-600 dark:text-ink-400">
                                        Code de recuperation
                                    </label>
                                    <input
                                        id="recovery_code"
                                        type="text"
                                        value={recoveryForm.data.recovery_code}
                                        onChange={(e) => recoveryForm.setData('recovery_code', e.target.value)}
                                        required
                                        autoFocus
                                        className="h-11 w-full rounded-lg border border-ink-200 bg-white px-3.5 font-mono text-sm text-ink-900 transition-colors hover:border-ink-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-100"
                                    />
                                    {recoveryForm.errors.recovery_code && <p className="mt-1.5 text-xs text-danger-600 dark:text-danger-400">{recoveryForm.errors.recovery_code}</p>}
                                </div>

                                <button
                                    type="submit"
                                    disabled={recoveryForm.processing}
                                    className="flex h-11 w-full items-center justify-center rounded-lg bg-brand-600 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-brand-500 dark:hover:bg-brand-400"
                                >
                                    {recoveryForm.processing ? 'Verification...' : 'Verifier'}
                                </button>
                            </form>
                        )}

                        <div className="mt-4 text-center">
                            <button
                                type="button"
                                onClick={() => setUseRecovery(!useRecovery)}
                                className="text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300"
                            >
                                {useRecovery ? 'Utiliser le code TOTP' : 'Utiliser un code de recuperation'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
