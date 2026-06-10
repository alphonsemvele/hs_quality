import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';

export default function Login({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPass, setShowPass] = useState(false);
    const [showDemoPicker, setShowDemoPicker] = useState(false);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login');
    };

    const fillDemo = (email: string) => {
        setData('email', email);
        setData('password', 'password');
        setShowDemoPicker(false);
    };

    const demoProfiles: Array<{
        email: string;
        role: string;
        name: string;
        tagline: string;
        accent: 'brand' | 'sage' | 'warning' | 'ink';
    }> = [
        {
            email: 'admin@platform.fr',
            role: 'Super-admin',
            name: 'Plateforme',
            tagline: 'Console /admin · feature flags · santé système',
            accent: 'ink',
        },
        {
            email: 'dirigeant@demo.fr',
            role: 'Dirigeante',
            name: 'Sophie Martin',
            tagline: 'Accès complet + facturation',
            accent: 'brand',
        },
        {
            email: 'coordinateur@demo.fr',
            role: 'Coordinateur',
            name: 'Thomas Dupont',
            tagline: 'Planning, CRUD bénéficiaires — principal',
            accent: 'sage',
        },
        {
            email: 'qualite@demo.fr',
            role: 'Référente qualité',
            name: 'Claire Bernard',
            tagline: "Audits, plans d'amélioration, journal QVCT",
            accent: 'warning',
        },
        {
            email: 'intervenant@demo.fr',
            role: 'Intervenante',
            name: 'Marie Leclerc',
            tagline: 'Mobile + planning · accès limité',
            accent: 'sage',
        },
    ];

    const accentClass = (a: 'brand' | 'sage' | 'warning' | 'ink'): string => {
        switch (a) {
            case 'brand':
                return 'bg-brand-50 text-brand-700 ring-brand-100';
            case 'sage':
                return 'bg-sage-50 text-sage-700 ring-sage-100';
            case 'warning':
                return 'bg-warning-50 text-warning-700 ring-warning-100';
            case 'ink':
                return 'bg-ink-100 text-ink-700 ring-ink-200';
        }
    };

    const initials = (name: string): string =>
        name
            .split(' ')
            .map((part) => part[0])
            .join('')
            .slice(0, 2)
            .toUpperCase();

    return (
        <>
            <Head title="Connexion — HS Quality" />

            <div className="flex min-h-screen font-sans text-ink-900 antialiased">
                {/* ─── LEFT — Form ─── */}
                <div className="flex flex-1 flex-col bg-white">
                    {/* Top bar */}
                    <div className="flex items-center justify-between border-b border-ink-100 px-6 py-4 sm:px-10">
                        <Link href="/" className="flex items-center gap-2.5">
                            <div className="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold italic text-white">
                                Q
                            </div>
                            <span className="text-[15px] font-semibold tracking-tight text-ink-900">HS Quality</span>
                        </Link>
                        <span className="hidden text-sm text-ink-400 sm:block">
                            Pas de compte ?{' '}
                            <a href="mailto:contact@hsquality.fr" className="font-medium text-ink-700 transition-colors hover:text-brand-600">
                                Contacter l'équipe
                            </a>
                        </span>
                    </div>

                    {/* Form area */}
                    <div className="flex flex-1 items-center justify-center px-6 py-12 sm:px-10">
                        <div className="w-full max-w-[400px]">
                            {/* Header */}
                            <div className="mb-8">
                                <h1 className="text-[28px] font-bold tracking-tight text-ink-900 sm:text-[32px]">
                                    Connexion
                                </h1>
                                <p className="mt-2 text-[15px] text-ink-500">
                                    Accédez à votre tableau de bord qualité et QVCT.
                                </p>
                            </div>

                            {/* Status message */}
                            {status && (
                                <div className="mb-6 flex items-center gap-3 rounded-xl bg-sage-50 px-4 py-3 ring-1 ring-sage-200">
                                    <svg className="size-5 shrink-0 text-sage-600" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p className="text-sm font-medium text-sage-700">{status}</p>
                                </div>
                            )}

                            {/* Demo profile picker */}
                            <div className="mb-6">
                                {!showDemoPicker ? (
                                    <button
                                        type="button"
                                        onClick={() => setShowDemoPicker(true)}
                                        className="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-brand-200 bg-brand-50/50 px-4 py-3 text-sm font-medium text-brand-600 transition-all hover:border-brand-300 hover:bg-brand-50"
                                    >
                                        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                                        </svg>
                                        Remplir avec les identifiants démo
                                    </button>
                                ) : (
                                    <div className="rounded-xl border border-brand-200 bg-brand-50/30 p-3">
                                        <div className="mb-2 flex items-center justify-between px-1">
                                            <p className="text-xs font-semibold uppercase tracking-wider text-brand-700">
                                                Choisir un profil de démo
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() => setShowDemoPicker(false)}
                                                aria-label="Fermer la liste"
                                                className="rounded-full p-1 text-ink-400 transition-colors hover:bg-white hover:text-ink-700"
                                            >
                                                <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <ul className="space-y-1.5">
                                            {demoProfiles.map((profile) => (
                                                <li key={profile.email}>
                                                    <button
                                                        type="button"
                                                        onClick={() => fillDemo(profile.email)}
                                                        className="group flex w-full items-center gap-3 rounded-lg border border-transparent bg-white px-3 py-2.5 text-left transition-all hover:-translate-y-0.5 hover:border-ink-200 hover:shadow-sm"
                                                    >
                                                        <span className={'flex size-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold ring-1 ring-inset ' + accentClass(profile.accent)}>
                                                            {initials(profile.name)}
                                                        </span>
                                                        <span className="min-w-0 flex-1">
                                                            <span className="flex items-baseline gap-2">
                                                                <span className="text-sm font-semibold text-ink-900">{profile.name}</span>
                                                                <span className="text-[11px] font-medium uppercase tracking-wider text-ink-400">
                                                                    {profile.role}
                                                                </span>
                                                            </span>
                                                            <span className="block truncate text-[11px] text-ink-500">{profile.tagline}</span>
                                                            <span className="block truncate font-mono text-[10px] text-ink-400 group-hover:text-brand-600">
                                                                {profile.email}
                                                            </span>
                                                        </span>
                                                        <svg className="size-4 shrink-0 text-ink-300 transition-colors group-hover:text-brand-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                                            <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                        </svg>
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                        <p className="mt-2 px-1 text-[10px] text-ink-400">
                                            Tous comptes — mot de passe <code className="font-mono">password</code>
                                        </p>
                                    </div>
                                )}
                            </div>

                            {/* Divider */}
                            <div className="mb-6 flex items-center gap-3">
                                <div className="h-px flex-1 bg-ink-100" />
                                <span className="text-xs font-medium text-ink-300">ou</span>
                                <div className="h-px flex-1 bg-ink-100" />
                            </div>

                            <form onSubmit={submit} className="space-y-5">
                                {/* Email */}
                                <div>
                                    <label htmlFor="email" className="mb-1.5 block text-sm font-medium text-ink-700">
                                        Adresse email
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        autoFocus
                                        autoComplete="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="vous@structure.fr"
                                        className={
                                            'w-full rounded-xl border bg-ink-50/50 px-4 py-3 text-sm text-ink-900 outline-none transition-all placeholder:text-ink-300 focus:bg-white focus:ring-2 ' +
                                            (errors.email
                                                ? 'border-danger-300 focus:border-danger-400 focus:ring-danger-100'
                                                : 'border-ink-200 focus:border-brand-400 focus:ring-brand-100')
                                        }
                                    />
                                    {errors.email && (
                                        <p className="mt-1.5 text-xs text-danger-600">{errors.email}</p>
                                    )}
                                </div>

                                {/* Password */}
                                <div>
                                    <div className="mb-1.5 flex items-center justify-between">
                                        <label htmlFor="password" className="text-sm font-medium text-ink-700">
                                            Mot de passe
                                        </label>
                                        <a href="#" className="text-xs font-medium text-ink-400 transition-colors hover:text-brand-600">
                                            Oublié ?
                                        </a>
                                    </div>
                                    <div className="relative">
                                        <input
                                            id="password"
                                            type={showPass ? 'text' : 'password'}
                                            autoComplete="current-password"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="••••••••••"
                                            className={
                                                'w-full rounded-xl border bg-ink-50/50 px-4 py-3 pr-11 text-sm text-ink-900 outline-none transition-all placeholder:text-ink-300 focus:bg-white focus:ring-2 ' +
                                                (errors.password
                                                    ? 'border-danger-300 focus:border-danger-400 focus:ring-danger-100'
                                                    : 'border-ink-200 focus:border-brand-400 focus:ring-brand-100')
                                            }
                                        />
                                        <button
                                            type="button"
                                            tabIndex={-1}
                                            onClick={() => setShowPass(!showPass)}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 text-ink-300 transition-colors hover:text-ink-500"
                                        >
                                            {showPass ? (
                                                <svg className="size-[18px]" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                </svg>
                                            ) : (
                                                <svg className="size-[18px]" fill="none" stroke="currentColor" strokeWidth={1.5} viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                    {errors.password && (
                                        <p className="mt-1.5 text-xs text-danger-600">{errors.password}</p>
                                    )}
                                </div>

                                {/* Remember me */}
                                <label className="flex cursor-pointer items-center gap-2.5 select-none">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="size-4 rounded border-ink-300 text-brand-600 focus:ring-brand-500"
                                    />
                                    <span className="text-sm text-ink-500">Rester connecté 30 jours</span>
                                </label>

                                {/* Submit */}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-ink-900 px-6 py-3.5 text-sm font-semibold text-white transition-all hover:-translate-y-px hover:bg-ink-800 hover:shadow-lg disabled:cursor-not-allowed disabled:bg-ink-200 disabled:text-ink-400 disabled:hover:translate-y-0 disabled:hover:shadow-none"
                                >
                                    {processing ? (
                                        <>
                                            <svg className="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                            </svg>
                                            Connexion…
                                        </>
                                    ) : (
                                        <>
                                            Se connecter
                                            <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                        </>
                                    )}
                                </button>
                            </form>

                            {/* SSO */}
                            <div className="mt-6 flex items-center gap-3">
                                <div className="h-px flex-1 bg-ink-100" />
                                <span className="text-xs font-medium text-ink-300">ou continuer avec</span>
                                <div className="h-px flex-1 bg-ink-100" />
                            </div>

                            <div className="mt-4 grid grid-cols-2 gap-3">
                                {[
                                    {
                                        label: 'Google',
                                        icon: (
                                            <svg className="size-4" viewBox="0 0 24 24">
                                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                            </svg>
                                        ),
                                    },
                                    {
                                        label: 'Microsoft',
                                        icon: (
                                            <svg className="size-4" viewBox="0 0 24 24">
                                                <path fill="#F25022" d="M1 1h10v10H1z" />
                                                <path fill="#7FBA00" d="M13 1h10v10H13z" />
                                                <path fill="#00A4EF" d="M1 13h10v10H1z" />
                                                <path fill="#FFB900" d="M13 13h10v10H13z" />
                                            </svg>
                                        ),
                                    },
                                ].map((p) => (
                                    <button
                                        key={p.label}
                                        type="button"
                                        className="flex items-center justify-center gap-2 rounded-xl border border-ink-200 bg-white px-4 py-2.5 text-sm font-medium text-ink-600 transition-all hover:border-ink-300 hover:bg-ink-50"
                                    >
                                        {p.icon}
                                        {p.label}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* Bottom bar */}
                    <div className="flex items-center justify-between border-t border-ink-100 px-6 py-3 sm:px-10">
                        <Link href="/" className="flex items-center gap-1.5 text-xs text-ink-400 transition-colors hover:text-ink-600">
                            <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour
                        </Link>
                        <div className="flex items-center gap-1.5 text-[11px] text-ink-300">
                            <svg className="size-3.5 text-sage-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            Chiffré TLS 1.3 · RGPD · HDS
                        </div>
                    </div>
                </div>

                {/* ─── RIGHT — Brand panel ─── */}
                <div className="relative hidden w-[46%] overflow-hidden bg-ink-900 lg:flex">
                    {/* Background image */}
                    <img
                        src="https://images.unsplash.com/photo-1576765608535-5f04d1e3f289?w=1200&auto=format&fit=crop&q=80"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        className="absolute inset-0 size-full object-cover"
                    />
                    <div className="absolute inset-0" style={{
                        background: 'linear-gradient(135deg, rgba(8,36,67,0.92) 0%, rgba(15,23,42,0.85) 50%, rgba(15,23,42,0.80) 100%)',
                    }} />
                    {/* Noise */}
                    <div
                        className="absolute inset-0 opacity-[0.03]"
                        style={{
                            backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.8'/%3E%3C/svg%3E")`,
                        }}
                    />
                    {/* Gradient orb accents */}
                    <div className="absolute -right-20 -top-20 size-[500px] rounded-full opacity-30"
                        style={{ background: 'radial-gradient(circle, rgba(21,101,172,0.4) 0%, transparent 65%)' }} />
                    <div className="absolute -bottom-20 -left-10 size-[400px] rounded-full opacity-20"
                        style={{ background: 'radial-gradient(circle, rgba(63,150,112,0.3) 0%, transparent 65%)' }} />

                    {/* Content */}
                    <div className="relative z-10 flex flex-1 flex-col justify-between p-12 xl:p-16">
                        {/* Top badge */}
                        <div>
                            <span className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.05] px-3.5 py-1.5 text-xs font-medium text-white/60 backdrop-blur-sm">
                                <span className="size-1.5 rounded-full bg-sage-400" style={{ animation: 'pulse 2s infinite' }} />
                                50+ structures pilotes
                            </span>
                        </div>

                        {/* Main quote */}
                        <div>
                            <h2 className="text-[clamp(28px,3.5vw,44px)] font-bold leading-[1.1] tracking-tight text-white">
                                La qualité ne se{' '}
                                <br className="hidden xl:block" />
                                déclare pas.{' '}
                                <span style={{
                                    background: 'linear-gradient(135deg, #93bcdf 0%, #95c9a9 100%)',
                                    backgroundClip: 'text',
                                    WebkitBackgroundClip: 'text',
                                    color: 'transparent',
                                }}>
                                    Elle se mesure.
                                </span>
                            </h2>
                            <p className="mt-5 max-w-sm text-[15px] leading-relaxed text-white/40">
                                HS Quality structure la démarche qualité et QVCT des structures médico-sociales — du terrain à la direction, en conformité HAS.
                            </p>
                        </div>

                        {/* KPI row */}
                        <div className="grid grid-cols-3 gap-3">
                            {[
                                { n: '–15%', l: 'Incidents', s: 'dès An 1' },
                                { n: '>60%', l: 'PAC', s: 'complétés' },
                                { n: '<5j', l: 'Résolution', s: 'incident' },
                            ].map(({ n, l, s }) => (
                                <div key={l} className="rounded-xl bg-white/[0.04] p-4 ring-1 ring-white/[0.06]">
                                    <div className="font-mono text-xl font-bold text-white">{n}</div>
                                    <div className="mt-1 text-xs font-semibold text-white/70">{l}</div>
                                    <div className="text-[11px] text-white/30">{s}</div>
                                </div>
                            ))}
                        </div>

                        {/* Mini testimonial */}
                        <div className="rounded-xl bg-white/[0.04] p-5 ring-1 ring-white/[0.06]">
                            <p className="text-sm italic leading-relaxed text-white/50">
                                "Notre taux de conformité HAS est passé de 61% à 84% en 6 mois."
                            </p>
                            <div className="mt-4 flex items-center gap-3 border-t border-white/[0.06] pt-4">
                                <div className="flex size-9 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white">
                                    MF
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-white/80">Marie-France Essomba</p>
                                    <p className="text-xs text-white/30">Directrice qualité · SAAD Horizon</p>
                                </div>
                            </div>
                        </div>

                        {/* Bottom badges */}
                        <div className="flex flex-wrap gap-2">
                            {['HDS', 'RGPD', 'HAS', 'ISO 9001', 'TLS 1.3'].map((b) => (
                                <span key={b} className="rounded-md border border-white/[0.08] px-2.5 py-1 text-[10px] font-semibold text-white/30">
                                    {b}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
