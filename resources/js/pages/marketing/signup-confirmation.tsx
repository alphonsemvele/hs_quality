import { MarketingPage } from '@/components/marketing/MarketingShell';
import { Head, Link } from '@inertiajs/react';

export default function SignupConfirmation({ email }: { email?: string }) {
    return (
        <MarketingPage>
            <Head title="Bienvenue — HS Quality" />

            <section className="mx-auto max-w-2xl px-5 py-20 text-center sm:px-8">
                <div className="mx-auto inline-flex size-16 items-center justify-center rounded-full bg-sage-100 text-sage-700">
                    <svg className="size-8" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <h1 className="mt-6 text-3xl font-bold tracking-tight text-ink-900">
                    Votre espace est en cours de création
                </h1>

                <p className="mt-4 text-sm leading-relaxed text-ink-600">
                    Nous venons d'envoyer un email{email ? ' à ' : ''}
                    {email && <strong className="text-ink-900">{email}</strong>} avec un lien sécurisé
                    pour choisir votre mot de passe. Le lien est valable 60 minutes.
                </p>

                <div className="mt-8 rounded-2xl border border-ink-100 bg-ink-50/60 p-5 text-left">
                    <h2 className="text-sm font-semibold text-ink-900">Et ensuite ?</h2>
                    <ol className="mt-3 space-y-2 text-sm text-ink-700">
                        <li className="flex items-start gap-2">
                            <Step number={1} />
                            Cliquez sur le lien dans l'email et choisissez votre mot de passe.
                        </li>
                        <li className="flex items-start gap-2">
                            <Step number={2} />
                            Activez votre double authentification — obligatoire pour les rôles dirigeant
                            (CDC §5).
                        </li>
                        <li className="flex items-start gap-2">
                            <Step number={3} />
                            Invitez votre équipe et créez votre premier bénéficiaire.
                        </li>
                    </ol>
                </div>

                <div className="mt-10 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                    <Link
                        href="/login"
                        className="inline-flex h-11 items-center justify-center rounded-full bg-brand-600 px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700"
                    >
                        Aller à la connexion
                    </Link>
                    <Link
                        href="/contact"
                        className="inline-flex h-11 items-center justify-center rounded-full border border-ink-200 bg-white px-6 text-sm font-medium text-ink-700 transition-colors hover:bg-ink-50"
                    >
                        Je n'ai pas reçu d'email
                    </Link>
                </div>

                <p className="mt-10 text-[11px] text-ink-500">
                    Aucune carte bancaire requise pour l'essai — vous disposez de 30 jours pour
                    découvrir QualitéDomicile.
                </p>
            </section>
        </MarketingPage>
    );
}

function Step({ number }: { number: number }) {
    return (
        <span className="mt-0.5 inline-flex size-5 shrink-0 items-center justify-center rounded-full bg-brand-100 text-[11px] font-bold text-brand-700">
            {number}
        </span>
    );
}
