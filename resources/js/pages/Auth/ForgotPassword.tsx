import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <>
            <Head title="Mot de passe oublié — HS Quality" />

            <div className="flex min-h-screen items-center justify-center bg-ink-50 px-4 font-sans antialiased dark:bg-ink-900">
                <div className="w-full max-w-md">
                    {/* Logo */}
                    <div className="mb-8 text-center">
                        <div className="mx-auto flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-serif text-lg font-semibold text-white">
                            Q
                        </div>
                        <h1 className="mt-4 text-xl font-bold text-ink-900 dark:text-white">Mot de passe oublié</h1>
                        <p className="mt-2 text-sm text-ink-500 dark:text-ink-400">
                            Saisissez votre adresse email et nous vous enverrons un lien de reinitialisation.
                        </p>
                    </div>

                    {/* Success message */}
                    {status && (
                        <div className="mb-4 rounded-xl border border-sage-200 bg-sage-50 p-4 text-sm text-sage-700 dark:border-sage-700/50 dark:bg-sage-900/20 dark:text-sage-300">
                            {status}
                        </div>
                    )}

                    <div className="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm dark:border-ink-700/60 dark:bg-ink-800">
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label htmlFor="email" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-600 dark:text-ink-400">
                                    Adresse email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoFocus
                                    className="h-11 w-full rounded-lg border border-ink-200 bg-white px-3.5 text-sm text-ink-900 transition-colors hover:border-ink-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-100 dark:hover:border-ink-500 dark:focus:border-brand-400"
                                    placeholder="votre@email.fr"
                                />
                                {errors.email && <p className="mt-1.5 text-xs text-danger-600 dark:text-danger-400">{errors.email}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="flex h-11 w-full items-center justify-center rounded-lg bg-brand-600 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-brand-500 dark:hover:bg-brand-400"
                            >
                                {processing ? 'Envoi...' : 'Envoyer le lien de reinitialisation'}
                            </button>
                        </form>

                        <div className="mt-4 text-center">
                            <a href="/login" className="text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                Retour a la connexion
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
