import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors } = useForm({ password: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/user/confirm-password');
    };

    return (
        <>
            <Head title="Confirmer le mot de passe — HS Quality" />

            <div className="flex min-h-screen items-center justify-center bg-ink-50 px-4 font-sans antialiased dark:bg-ink-900">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <div className="mx-auto flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-serif text-lg font-semibold text-white">
                            Q
                        </div>
                        <h1 className="mt-4 text-xl font-bold text-ink-900 dark:text-white">Confirmer votre identite</h1>
                        <p className="mt-2 text-sm text-ink-500 dark:text-ink-400">
                            Cette action est sensible. Veuillez confirmer votre mot de passe pour continuer.
                        </p>
                    </div>

                    <div className="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm dark:border-ink-700/60 dark:bg-ink-800">
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label htmlFor="password" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-600 dark:text-ink-400">
                                    Mot de passe
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                    autoFocus
                                    className="h-11 w-full rounded-lg border border-ink-200 bg-white px-3.5 text-sm text-ink-900 transition-colors hover:border-ink-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-100"
                                />
                                {errors.password && <p className="mt-1.5 text-xs text-danger-600 dark:text-danger-400">{errors.password}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="flex h-11 w-full items-center justify-center rounded-lg bg-brand-600 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-brand-500 dark:hover:bg-brand-400"
                            >
                                {processing ? 'Verification...' : 'Confirmer'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
