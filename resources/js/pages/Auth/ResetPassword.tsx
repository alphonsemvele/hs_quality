import { PasswordStrength } from '@/components/ui';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface Props {
    email: string;
    token: string;
}

export default function ResetPassword({ email, token }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const [showPass, setShowPass] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/reset-password');
    };

    return (
        <>
            <Head title="Nouveau mot de passe — HS Quality" />

            <div className="flex min-h-screen items-center justify-center bg-ink-50 px-4 font-sans antialiased dark:bg-ink-900">
                <div className="w-full max-w-md">
                    <div className="mb-8 text-center">
                        <div className="mx-auto flex size-12 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-serif text-lg font-semibold text-white">
                            Q
                        </div>
                        <h1 className="mt-4 text-xl font-bold text-ink-900 dark:text-white">Nouveau mot de passe</h1>
                        <p className="mt-2 text-sm text-ink-500 dark:text-ink-400">
                            Choisissez un nouveau mot de passe securise.
                        </p>
                    </div>

                    <div className="rounded-2xl border border-ink-200 bg-white p-6 shadow-sm dark:border-ink-700/60 dark:bg-ink-800">
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label htmlFor="email" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-600 dark:text-ink-400">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    className="h-11 w-full rounded-lg border border-ink-200 bg-ink-50 px-3.5 text-sm text-ink-900 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-100"
                                    readOnly
                                />
                                {errors.email && <p className="mt-1.5 text-xs text-danger-600 dark:text-danger-400">{errors.email}</p>}
                            </div>

                            <div>
                                <label htmlFor="password" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-600 dark:text-ink-400">
                                    Nouveau mot de passe
                                </label>
                                <div className="relative">
                                    <input
                                        id="password"
                                        type={showPass ? 'text' : 'password'}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        required
                                        autoFocus
                                        className="h-11 w-full rounded-lg border border-ink-200 bg-white px-3.5 pr-10 text-sm text-ink-900 transition-colors hover:border-ink-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-100"
                                    />
                                    <button type="button" onClick={() => setShowPass(!showPass)} className="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-ink-400 hover:text-ink-600 dark:hover:text-ink-300">
                                        {showPass ? 'Masquer' : 'Afficher'}
                                    </button>
                                </div>
                                {errors.password && <p className="mt-1.5 text-xs text-danger-600 dark:text-danger-400">{errors.password}</p>}
                                <PasswordStrength password={data.password} />
                            </div>

                            <div>
                                <label htmlFor="password_confirmation" className="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-ink-600 dark:text-ink-400">
                                    Confirmer le mot de passe
                                </label>
                                <input
                                    id="password_confirmation"
                                    type={showPass ? 'text' : 'password'}
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    required
                                    className="h-11 w-full rounded-lg border border-ink-200 bg-white px-3.5 text-sm text-ink-900 transition-colors hover:border-ink-300 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-100"
                                />
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="flex h-11 w-full items-center justify-center rounded-lg bg-brand-600 text-sm font-semibold text-white transition-colors hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-brand-500 dark:hover:bg-brand-400"
                            >
                                {processing ? 'Reinitialisation...' : 'Reinitialiser le mot de passe'}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
